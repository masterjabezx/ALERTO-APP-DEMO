<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE user_presences MODIFY COLUMN status ENUM('online', 'idle', 'offline', 'connection_lost') NOT NULL DEFAULT 'offline'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE user_presences MODIFY COLUMN status ENUM('online', 'idle', 'offline') NOT NULL DEFAULT 'offline'");
    }
};
