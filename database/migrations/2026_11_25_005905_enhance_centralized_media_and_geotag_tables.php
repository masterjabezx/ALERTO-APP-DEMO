<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_attachments', function (Blueprint $table): void {
            $table->string('uploader_account_type', 32)->nullable()->after('uploader_id')->index();
            $table->string('source_channel', 64)->default('citizen_portal')->after('uploader_account_type')->index();
            $table->string('media_type', 32)->default('photo')->after('mime_type')->index();
            $table->string('stored_filename', 255)->nullable()->after('file_name');
            $table->string('extension', 16)->nullable()->after('stored_filename');

            // Polymorphic & Direct Operational Context Links
            $table->nullableMorphs('attachable');
            $table->foreignId('incident_id')->nullable()->after('attachable_id')->constrained('incidents')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->after('incident_id')->constrained('conversations')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->after('conversation_id')->constrained('messages')->nullOnDelete();

            // Processing, Previews & Dimensions
            $table->enum('processing_status', ['uploading', 'uploaded', 'processing', 'ready', 'failed'])
                ->default('uploaded')
                ->after('checksum_sha256')
                ->index();
            $table->text('processing_error')->nullable()->after('processing_status');
            $table->string('thumbnail_path', 512)->nullable()->after('processing_error');
            $table->string('preview_path', 512)->nullable()->after('thumbnail_path');
            $table->unsignedInteger('width')->nullable()->after('preview_path');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->decimal('duration_seconds', 8, 2)->nullable()->after('height');

            // Evidence Integrity & Retention Governance
            $table->boolean('is_locked_evidence')->default(false)->after('duration_seconds')->index();
            $table->boolean('is_archived')->default(false)->after('is_locked_evidence')->index();
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            $table->timestamp('retention_until')->nullable()->after('archived_by');

            $table->timestamp('updated_at')->nullable()->after('created_at');
            $table->softDeletes();
        });

        Schema::table('media_geo_metadata', function (Blueprint $table): void {
            $table->decimal('accuracy_meters', 8, 2)->nullable()->change();
            $table->string('location_source', 64)->default('LOCATION_UNAVAILABLE')->after('gps_source')->index();

            // Distinct capture vs upload vs inherited coordinates
            $table->decimal('exif_latitude', 10, 8)->nullable()->after('is_inherited');
            $table->decimal('exif_longitude', 11, 8)->nullable()->after('exif_latitude');
            $table->decimal('upload_latitude', 10, 8)->nullable()->after('exif_longitude');
            $table->decimal('upload_longitude', 11, 8)->nullable()->after('upload_latitude');
            $table->decimal('upload_accuracy_meters', 8, 2)->nullable()->after('upload_longitude');
            $table->decimal('inherited_latitude', 10, 8)->nullable()->after('upload_accuracy_meters');
            $table->decimal('inherited_longitude', 11, 8)->nullable()->after('inherited_latitude');

            $table->foreignId('barangay_id')->nullable()->after('inherited_longitude')->constrained('barangays')->nullOnDelete();
            $table->string('verification_status', 32)->default('unverified')->after('barangay_id')->index();
            $table->timestamp('uploaded_at')->nullable()->after('captured_at')->index();
            $table->json('raw_metadata')->nullable()->after('uploaded_at');

            $table->index(['latitude', 'longitude'], 'media_geo_coords_idx');
        });

        // Backfill media_type & location_source for existing seeded/test records non-destructively
        DB::table('media_attachments')->where('mime_type', 'like', 'video/%')->update(['media_type' => 'video']);
        DB::table('media_attachments')->where('mime_type', 'like', 'application/%')->update(['media_type' => 'document']);

        DB::table('media_geo_metadata')->where('gps_source', 'exif_embedded')->update(['location_source' => 'EXIF_CAPTURE']);
        DB::table('media_geo_metadata')->where('gps_source', 'device_live_upload')->update(['location_source' => 'GPS_CAPTURE']);
        DB::table('media_geo_metadata')->where('gps_source', 'incident_inherited')->update(['location_source' => 'INCIDENT_INHERITED_LOCATION']);
        DB::table('media_geo_metadata')->where('gps_source', 'none')->update([
            'location_source' => 'LOCATION_UNAVAILABLE',
            'verification_status' => 'unavailable',
        ]);
    }

    public function down(): void
    {
        Schema::table('media_geo_metadata', function (Blueprint $table): void {
            $table->dropIndex('media_geo_coords_idx');
            $table->dropConstrainedForeignId('barangay_id');
            $table->dropColumn([
                'location_source',
                'exif_latitude',
                'exif_longitude',
                'upload_latitude',
                'upload_longitude',
                'upload_accuracy_meters',
                'inherited_latitude',
                'inherited_longitude',
                'verification_status',
                'uploaded_at',
                'raw_metadata',
            ]);
        });

        Schema::table('media_attachments', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('archived_by');
            $table->dropConstrainedForeignId('message_id');
            $table->dropConstrainedForeignId('conversation_id');
            $table->dropConstrainedForeignId('incident_id');
            $table->dropMorphs('attachable');
            $table->dropColumn([
                'uploader_account_type',
                'source_channel',
                'media_type',
                'stored_filename',
                'extension',
                'processing_status',
                'processing_error',
                'thumbnail_path',
                'preview_path',
                'width',
                'height',
                'duration_seconds',
                'is_locked_evidence',
                'is_archived',
                'archived_at',
                'retention_until',
                'updated_at',
            ]);
        });
    }
};
