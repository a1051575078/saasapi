<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name',191);
            $table->string('music',191)->nullable();
            $table->text('token')->nullable();
            $table->string('avatar',191)->nullable();
            $table->string('email',191)->unique()->nullable();
            $table->text('content')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password',191);
            $table->tinyInteger('is_server')->nullable()->comment('客服是否挂起 0为挂起 1为未挂起');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
