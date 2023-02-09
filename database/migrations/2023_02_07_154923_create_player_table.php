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
            $table->string('room_name', 20)->nullable(false);
            $table->string('name', 20)->nullable(false);
            $table->string('pass', 20)->nullable(false)->default('');
            $table->string('icon', 100)->nullable(false)->default('');
            $table->string('role', 100)->nullable(false)->default('');
            $table->integer('date')->default(0);
            $table->integer('time_zone')->default(0);
            $table->string('killed')->default('');
            $table->timestamps();
            $table->primary(['room_name', 'name']);
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
