<?php

/*
 * This file is part of the hyn/multi-tenant package.
 *
 * (c) Daniël Klabbers <daniel@klabbers.email>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://tenancy.dev
 * @see https://github.com/hyn/multi-tenant
 */

use Hyn\Tenancy\Abstracts\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TenancyHostnames extends AbstractMigration
{
    protected $system = true;

    public function up()
    {
        Schema::create('hostnames', function(Blueprint $table){
            $table->bigIncrements('id');
            $table->string('numbering')->nullable();
            $table->boolean('ishttp')->default(false);
            $table->string('jumplink')->nullable();
            $table->integer('deleteday')->nullable();
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->string('certificate')->nullable();
            $table->string('type')->nullable();
            $table->string('password')->nullable();
            $table->integer('seat')->default(0);
            $table->string('fqdn')->unique();
            $table->string('vue')->unique();
            $table->string('redirect_to')->nullable();
            $table->boolean('force_https')->default(false);
            $table->timestamp('under_maintenance_since')->nullable();
            $table->bigInteger('website_id')->unsigned()->nullable();
            $table->timestamp('expiry_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('website_id')->references('id')->on('websites')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hostnames');
    }
}
