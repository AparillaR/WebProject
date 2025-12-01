<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_room_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomTable extends Migration
{
    public function up()
    {
        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->string('rm_name', 50);
            $table->string('rm_code', 20)->unique();
            $table->boolean('rm_status')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('room');
    }
}