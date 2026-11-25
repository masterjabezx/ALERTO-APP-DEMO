<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_boundaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->longText('geojson_boundary');
            $table->decimal('bounding_min_lat', 10, 8)->nullable()->index();
            $table->decimal('bounding_max_lat', 10, 8)->nullable()->index();
            $table->decimal('bounding_min_lng', 11, 8)->nullable()->index();
            $table->decimal('bounding_max_lng', 11, 8)->nullable()->index();
            $table->string('source_information')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::table('barangay_boundaries', function (Blueprint $table) {
            $table->string('source_information')->nullable()->after('bounding_max_lng');
            $table->boolean('is_verified')->default(false)->after('source_information');
            $table->timestamp('imported_at')->nullable()->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('barangay_boundaries', function (Blueprint $table) {
            $table->dropColumn(['source_information', 'is_verified', 'imported_at']);
        });

        Schema::dropIfExists('municipality_boundaries');
    }
};
