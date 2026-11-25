<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 32)->default('private');
            $table->string('file_path', 512);
            $table->string('file_name', 255);
            $table->string('mime_type', 128)->index();
            $table->unsignedBigInteger('file_size_bytes');
            $table->char('checksum_sha256', 64)->index();
            $table->timestamp('created_at')->index();
        });

        Schema::create('media_geo_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->unique()->constrained('media_attachments')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8)->nullable()->index();
            $table->decimal('longitude', 11, 8)->nullable()->index();
            $table->decimal('accuracy_meters', 6, 2)->nullable();
            $table->enum('gps_source', ['exif_embedded', 'device_live_upload', 'incident_inherited', 'none'])->default('none');
            $table->boolean('is_inherited')->default(false);
            $table->string('camera_make', 128)->nullable();
            $table->string('camera_model', 128)->nullable();
            $table->timestamp('captured_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_geo_metadata');
        Schema::dropIfExists('media_attachments');
    }
};
