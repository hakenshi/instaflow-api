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
        Schema::create('message_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('trigger_id')->nullable()->constrained('triggers')->nullOnDelete();
            $table->string('sender_ig_id', 100);
            $table->string('sender_username', 100)->nullable();
            $table->string('event_type', 50);
            $table->text('incoming_text')->nullable();
            $table->text('response_text');
            $table->enum('status', ['sent', 'failed', 'rate_limited', 'queued'])->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'created_at'], 'idx_msg_log_workspace_created');
            $table->index('sender_ig_id', 'idx_msg_log_sender');
            $table->index('status', 'idx_msg_log_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_log');
    }
};
