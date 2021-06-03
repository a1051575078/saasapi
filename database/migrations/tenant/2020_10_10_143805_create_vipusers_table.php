<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVipusersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vipusers', function (Blueprint $table) {
            $table->id();
            $table->string('user');
            $table->string('name')->nullable();
            $table->tinyInteger('sex')->default(1);
            $table->string('phone')->nullable();
            $table->integer('age')->nullable();
            $table->string('qq')->nullable();
            $table->string('wechat')->nullable();
            $table->string('ip')->nullable();
            $table->string('address')->nullable();
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('vipusers');
    }
}
