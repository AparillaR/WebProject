<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   // In the migration file
public function up()
{
    Schema::table('items', function (Blueprint $table) {
        $table->enum('category', ['general', 'computer_lab', 'science_lab', 'speech_lab'])->default('general')->after('model');
    });
}

public function down()
{
    Schema::table('items', function (Blueprint $table) {
        $table->dropColumn('category');
    });
}
  
};
