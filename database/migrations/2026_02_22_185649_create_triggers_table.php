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
        Schema::create('triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['comment', 'dm_keyword', 'story_mention', 'story_reply'])->default('comment');
            $table->text('keywords')->comment('Palavras-chave separadas por vírgula');
            $table->text('response_text')->comment('Mensagem de resposta');
            $table->string('response_media_url', 500)->nullable()->comment('URL de mídia opcional');
            $table->boolean('is_active')->default(true);
            $table->boolean('match_exact')->default(false)->comment('1=exata, 0=contém');
            $table->timestamps();
            $table->index(['workspace_id', 'type', 'is_active'], 'idx_triggers_workspace_type_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triggers');
    }
};
