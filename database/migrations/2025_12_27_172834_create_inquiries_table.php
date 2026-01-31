<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('users');
            $table->foreignUuid('contractor_id')->constrained('users');
            $table->foreignUuid('listing_id')->constrained('service_listings');
            $table->foreignUuid('conversation_id')->nullable()->constrained('conversations');
            $table->text('initial_message');
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('inquiries');
    }
};
