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
        Schema::create('instagram_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('meta_user_id', 100);
            $table->string('meta_user_name', 150)->nullable();
            $table->string('page_id', 100);
            $table->string('page_name', 150)->nullable();
            $table->string('instagram_account_id', 100);
            $table->string('instagram_username', 150)->nullable();
            $table->text('user_access_token');
            $table->text('page_access_token');
            $table->text('scopes')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique('workspace_id');
            $table->unique('page_id');
            $table->unique('instagram_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_connections');
    }
};
