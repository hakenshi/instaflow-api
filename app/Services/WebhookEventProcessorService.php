<?php

namespace App\Services;

use App\Jobs\SendInstagramMessageJob;
use App\Models\InstagramConnection;
use App\Models\MessageLog;
use App\Models\Setting;
use App\Models\Trigger;
use App\Models\WebhookEventDedupe;

class WebhookEventProcessorService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function process(array $payload): void
    {
        $entries = $payload['entry'] ?? [];

        if (! is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $pageId = (string) ($entry['id'] ?? '');

            if ($pageId === '') {
                continue;
            }

            $connection = InstagramConnection::query()
                ->where('page_id', $pageId)
                ->first();

            if (! $connection) {
                continue;
            }

            $workspaceId = (int) $connection->workspace_id;

            if (! $this->isAutoReplyEnabled($workspaceId)) {
                continue;
            }

            $messagingEvents = $entry['messaging'] ?? [];

            if (is_array($messagingEvents)) {
                foreach ($messagingEvents as $event) {
                    if (! is_array($event)) {
                        continue;
                    }

                    if (! $this->claimEvent($workspaceId, 'messaging', $entry, $event)) {
                        continue;
                    }

                    $this->processMessagingEvent($workspaceId, $event);
                }
            }

            $changeEvents = $entry['changes'] ?? [];

            if (is_array($changeEvents)) {
                foreach ($changeEvents as $change) {
                    if (! is_array($change)) {
                        continue;
                    }

                    if (! $this->claimEvent($workspaceId, 'change', $entry, $change)) {
                        continue;
                    }

                    $this->processChangeEvent($workspaceId, $change);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>  $event
     */
    private function claimEvent(int $workspaceId, string $scope, array $entry, array $event): bool
    {
        $eventKey = hash('sha256', json_encode([
            'scope' => $scope,
            'entry' => $entry,
            'event' => $event,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: serialize([$scope, $entry, $event]));

        $existing = WebhookEventDedupe::query()->firstOrCreate(
            ['workspace_id' => $workspaceId, 'event_key' => $eventKey],
            [
                'event_scope' => $scope,
                'event_ref' => (string) ($event['timestamp'] ?? $event['field'] ?? ''),
            ]
        );

        return $existing->wasRecentlyCreated;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function processMessagingEvent(int $workspaceId, array $event): void
    {
        if (! empty($event['message']['is_echo'])) {
            return;
        }

        $senderId = (string) ($event['sender']['id'] ?? '');

        if ($senderId === '') {
            return;
        }

        $messageText = trim((string) ($event['message']['text'] ?? ''));
        $isStoryReply = is_array($event['message']['reply_to']['story'] ?? null);

        if (! $isStoryReply && $messageText === '') {
            return;
        }

        $trigger = null;
        $eventType = 'dm_keyword';

        if ($isStoryReply) {
            if ($messageText !== '') {
                $trigger = Trigger::findMatch($workspaceId, $messageText, 'story_reply');
            }

            if (! $trigger) {
                $trigger = Trigger::findMatch($workspaceId, 'story_reply', 'story_reply');
            }

            if ($trigger) {
                $eventType = 'story_reply';
            } elseif ($messageText !== '') {
                $trigger = Trigger::findMatch($workspaceId, $messageText, 'dm_keyword');
            }
        } else {
            $trigger = Trigger::findMatch($workspaceId, $messageText, 'dm_keyword');
        }

        if (! $trigger) {
            return;
        }

        $this->enqueueTriggerResponse(
            workspaceId: $workspaceId,
            trigger: $trigger,
            senderId: $senderId,
            eventType: $eventType,
            incomingText: $messageText !== '' ? $messageText : 'Respondeu ao Story',
            senderUsername: null,
        );
    }

    /**
     * @param  array<string, mixed>  $change
     */
    private function processChangeEvent(int $workspaceId, array $change): void
    {
        $field = (string) ($change['field'] ?? '');
        $value = $change['value'] ?? [];

        if (! is_array($value)) {
            return;
        }

        if ($field === 'comments') {
            $commentText = trim((string) ($value['text'] ?? ''));
            $commenterId = (string) ($value['from']['id'] ?? '');
            $commenterUsername = (string) ($value['from']['username'] ?? '');

            if ($commentText === '' || $commenterId === '') {
                return;
            }

            $trigger = Trigger::findMatch($workspaceId, $commentText, 'comment');

            if (! $trigger) {
                return;
            }

            $this->enqueueTriggerResponse(
                workspaceId: $workspaceId,
                trigger: $trigger,
                senderId: $commenterId,
                eventType: 'comment',
                incomingText: $commentText,
                senderUsername: $commenterUsername !== '' ? $commenterUsername : null,
            );

            return;
        }

        if (! in_array($field, ['mentions', 'story_insights'], true)) {
            return;
        }

        $senderId = (string) ($value['sender']['id'] ?? ($value['from']['id'] ?? ''));
        $senderUsername = (string) ($value['sender']['username'] ?? ($value['from']['username'] ?? ''));

        if ($senderId === '') {
            return;
        }

        $trigger = Trigger::findMatch($workspaceId, 'story_mention', 'story_mention');

        if (! $trigger) {
            return;
        }

        $this->enqueueTriggerResponse(
            workspaceId: $workspaceId,
            trigger: $trigger,
            senderId: $senderId,
            eventType: 'story_mention',
            incomingText: 'Mencionou no Story',
            senderUsername: $senderUsername !== '' ? $senderUsername : null,
        );
    }

    private function enqueueTriggerResponse(
        int $workspaceId,
        Trigger $trigger,
        string $senderId,
        string $eventType,
        ?string $incomingText,
        ?string $senderUsername
    ): void {
        $logId = MessageLog::log([
            'workspace_id' => $workspaceId,
            'trigger_id' => $trigger->id,
            'sender_ig_id' => $senderId,
            'sender_username' => $senderUsername,
            'event_type' => $eventType,
            'incoming_text' => $incomingText,
            'response_text' => $trigger->response_text,
            'status' => 'queued',
            'error_message' => null,
        ]);

        SendInstagramMessageJob::dispatch(
            workspaceId: $workspaceId,
            logId: $logId,
            triggerId: (int) $trigger->id,
            recipientId: $senderId,
            eventType: $eventType,
            incomingText: $incomingText,
            responseText: (string) $trigger->response_text,
            responseMediaUrl: $trigger->response_media_url,
        );
    }

    private function isAutoReplyEnabled(int $workspaceId): bool
    {
        return Setting::getValue($workspaceId, 'auto_reply_enabled', '1') === '1';
    }
}
