<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->nullable();
            $table->integer('frequency')->default(0)->comment('访客访问的次数');
            $table->string('address')->nullable()->comment('地理位置');
            $table->string('isphone')->default('电脑设备')->comment('访问的设备');
            $table->string('origin')->nullable()->comment('访客的来路');
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
        Schema::dropIfExists('visitors');
    }
}
