<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('contractor_id')->constrained('users')->cascadeOnDelete();
            $table->string('service_type', 100);
            $table->text('description');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('service_city', 100);
            $table->integer('service_radius_km');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('contact_phone', 20);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index('service_type');
            $table->index('service_city');
            $table->index('status');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_listings');
    }
};
