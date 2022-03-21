<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSeaweedfsMappings extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('seaweedfs_mappings', function(Blueprint $table){
            $table->increments('id');
            $table->string('path', 256);
            $table->string('fid', 64);
            $table->string('mimeType', 64);
            $table->bigInteger('size');
            $table->timestamps();

            $table->index('path');
            $table->index('fid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('seaweedfs_mappings');
    }
}
