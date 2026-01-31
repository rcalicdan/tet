<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('users');
            $table->foreignUuid('contractor_id')->constrained('users');
            $table->foreignUuid('listing_id')->nullable()->constrained('service_listings')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps(); 
            
            $table->index('last_message_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversations');
    }
};