<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignUuid('sender_id')->constrained('users');
            $table->text('message_text');
            $table->boolean('is_read')->default(false);
            
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
