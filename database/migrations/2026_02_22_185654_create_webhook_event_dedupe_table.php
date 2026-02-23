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
        Schema::create('webhook_event_dedupe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('event_key', 80);
            $table->string('event_scope', 20);
            $table->string('event_ref', 191)->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'event_key'], 'uk_event_key_per_workspace');
            $table->index(['workspace_id', 'created_at'], 'idx_dedupe_workspace_created');
            $table->index(['event_scope', 'created_at'], 'idx_scope_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_event_dedupe');
    }
};
