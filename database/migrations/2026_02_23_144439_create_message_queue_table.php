<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('source_log_id')->nullable()->constrained('message_log')->nullOnDelete();
            $table->foreignId('trigger_id')->nullable()->constrained('triggers')->nullOnDelete();
            $table->string('recipient_id', 100);
            $table->string('sender_username', 100)->nullable();
            $table->string('event_type', 50);
            $table->text('incoming_text')->nullable();
            $table->text('response_text')->nullable();
            $table->string('response_media_url', 500)->nullable();
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(10);
            $table->timestamp('next_attempt_at')->useCurrent();
            $table->timestamp('locked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status', 'next_attempt_at'], 'idx_msg_queue_workspace_status_next');
            $table->index('created_at', 'idx_msg_queue_created');
            $table->unique('source_log_id', 'uk_msg_queue_source_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_queue');
    }
};
