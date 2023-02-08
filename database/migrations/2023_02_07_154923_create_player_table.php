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
        Schema::create('players', function (Blueprint $table) {
            $table->string('room_name', 20)->primary();
            $table->string('name', 20)->primary();
            $table->string('pass', 20)->nullable(false);
            $table->string('icon', 100)->nullable(false);
            $table->string('role', 100)->nullable(false);
            $table->integer('date');
            $table->integer('time_zone');
            $table->string('killed');
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
        Schema::dropIfExists('player');
    }
};
