<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('fromid')->nullable();
            $table->string('ip')->nullable();
            $table->integer('recordnumber')->default(0);
            $table->string('address')->nullable();
            $table->tinyInteger('blacklist')->nullable();
            $table->timestamps();
            $table->index('fromid');
            $table->index('ip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('contacts');
    }
}
