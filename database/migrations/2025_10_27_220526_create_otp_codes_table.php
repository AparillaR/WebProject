<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

public function up()
{
    Schema::create('otp', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('email');
        $table->enum('status', ['pending', 'processed', 'expired'])->default('pending');
        $table->timestamp('expires_at');
        $table->timestamps();
        
        $table->index(['status', 'expires_at']);
    });
}

    public function down()
    {
        Schema::dropIfExists('otp_codes');
    }
};