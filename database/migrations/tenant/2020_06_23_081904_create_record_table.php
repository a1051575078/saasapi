<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('record', function (Blueprint $table) {
            $table->id();
            $table->string('fromid',191)->comment('发送者的标识,纯数字是客服,访客+{ip}是用户');
            $table->string('toid',191)->comment('被发送者标识,同上');
            $table->foreignId('user_id')->nullable()->comment('哪个客服id发送得')->constrained('users','id')->onDelete('cascade')->onUpdate('restrict');
            $table->string('rand',191)->comment('发送的内容')->nullable();
            $table->text('content')->comment('发送的内容');
            $table->tinyInteger('withdraw')->comment('撤回,1是撤回')->nullable();
            $table->tinyInteger('isread')->comment('消息读取状态 0为未读 1为已读');
            $table->tinyInteger('type')->comment('消息格式 1为文字 2为图片');
            $table->timestamps();
            $table->index('fromid');
            $table->index('toid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('record');
    }
}
