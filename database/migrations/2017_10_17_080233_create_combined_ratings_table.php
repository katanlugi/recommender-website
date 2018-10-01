<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCombinedRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('combined_ratings', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('movie_id')->unsigned();
            $table->integer('rating_sum')->unsigned()->default(0)->nullable();
            $table->integer('number_of_ratings')->unsigned()->default(0);
            $table->float('average_rating', 10, 8)->default(0.0)->nullable();
            $table->timestamps();

            $table->unique(['id', 'movie_id']);
            $table->index('movie_id');
            $table->foreign('movie_id')->references('id')->on('movies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('combined_ratings');
    }
}
