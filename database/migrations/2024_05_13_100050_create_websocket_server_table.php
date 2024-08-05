<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsocketServerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('websocket_server', function (Blueprint $table) {
            $table->id();
            $table->string('url', 255)->nullable();
            $table->string('host', 255)->nullable();
            $table->string('port', 255)->nullable();
            $table->integer('status')->default(0);
            $table->text('message')->nullable();
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
        Schema::dropIfExists('websocket_server');
    }
}
