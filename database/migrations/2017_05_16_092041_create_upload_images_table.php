<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUploadImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upload_images', function (Blueprint $table) {
            $table->string('key')->unique()->index();
            $table->integer('width');
            $table->integer('height');
            $table->integer('size');
            $table->string('path');
            $table->string('extend');
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
        Schema::drop('upload_images');
    }
}
