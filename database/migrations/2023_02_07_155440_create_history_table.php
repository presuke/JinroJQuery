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
        Schema::create('histories', function (Blueprint $table) {
            $table->string('room_name', 20)->nullable(false);
            $table->string('player_name', 20)->nullable(false);
            $table->integer('date')->default(0);
            $table->integer('time_zone')->default(0);
            $table->string('action',25)->nullable(false)->default('');
            $table->string('target',20)->nullable(false)->default('');
            $table->timestamps();
            $table->primary(['room_name', 'player_name', 'date', 'time_zone']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('history');
    }
};
