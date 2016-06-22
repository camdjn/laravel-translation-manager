<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStmTranslationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('stm_translations', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('group_id');
            $table->integer('locale_id');
            $table->string('status');
            $table->string('key');
            $table->text('value')->nullable();
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
        Schema::drop('stm_translations');
	}

}
