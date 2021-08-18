<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBothQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('both_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question_type');
            $table->string('question_text')->nullable();
            $table->string('input_type');
            $table->integer('indicator');
            $table->string('special');
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
        Schema::dropIfExists('both_questions');
    }
}
