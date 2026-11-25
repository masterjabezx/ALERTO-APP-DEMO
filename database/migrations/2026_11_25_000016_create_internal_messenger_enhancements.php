<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('created_by')->index();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('incident_id')->nullable()->after('conversation_id')->constrained('incidents')->nullOnDelete();
            $table->foreignId('reply_to_message_id')->nullable()->after('incident_id')->constrained('messages')->nullOnDelete();
            $table->string('message_type', 32)->default('text')->change();
            $table->timestamp('edited_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('incident_id');
            $table->dropConstrainedForeignId('reply_to_message_id');
            $table->dropColumn('edited_at');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });
    }
};
