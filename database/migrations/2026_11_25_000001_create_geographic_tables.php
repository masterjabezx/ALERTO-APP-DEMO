<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('province', 128);
            $table->string('region', 64);
            $table->string('postal_code', 16);
            $table->decimal('center_latitude', 10, 8);
            $table->decimal('center_longitude', 11, 8);
            $table->timestamps();
        });

        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->restrictOnDelete();
            $table->string('name', 128)->index();
            $table->string('contact_number', 32)->nullable();
            $table->decimal('center_latitude', 10, 8);
            $table->decimal('center_longitude', 11, 8);
            $table->timestamps();
        });

        Schema::create('barangay_boundaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barangay_id')->unique()->constrained('barangays')->cascadeOnDelete();
            $table->longText('geojson_boundary');
            $table->decimal('bounding_min_lat', 10, 8)->index();
            $table->decimal('bounding_max_lat', 10, 8)->index();
            $table->decimal('bounding_min_lng', 11, 8)->index();
            $table->decimal('bounding_max_lng', 11, 8)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangay_boundaries');
        Schema::dropIfExists('barangays');
        Schema::dropIfExists('municipalities');
    }
};
