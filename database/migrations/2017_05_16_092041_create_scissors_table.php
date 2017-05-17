<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScissorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scissors', function (Blueprint $table) {
            $table->string('key')->unique()->index();
            $table->integer('width');
            $table->integer('height');
            $table->integer('filesize');
            $table->string('dir')->nullable();
            $table->string('filepath')->nullable();
            $table->string('filename')->nullable();
            $table->string('origin_url')->nullable();
            $table->string('mime');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('scissors');
    }
}
