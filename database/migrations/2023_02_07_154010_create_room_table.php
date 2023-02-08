<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->string('name',20)->primary();
            $table->integer('date')->default(0);
            $table->integer('time_zone')->default(0);
            $table->string('voted',200)->nullable(false)->default('');
            $table->string('killed',20)->nullable(false)->default('');
            $table->string('winner',20)->nullable(false)->default('');
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
        Schema::dropIfExists('room');
    }
};
