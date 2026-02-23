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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained('workspaces')->nullOnDelete();
            $table->string('meta_user_id', 100)->nullable()->after('email');
            $table->string('meta_name', 150)->nullable()->after('meta_user_id');
            $table->string('avatar_url', 500)->nullable()->after('meta_name');
            $table->boolean('is_active')->default(true)->after('avatar_url');

            $table->unique('meta_user_id');
            $table->index('workspace_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropUnique(['meta_user_id']);
            $table->dropColumn(['meta_user_id', 'meta_name', 'avatar_url', 'is_active']);
        });
    }
};
