<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMoviesSmallTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movies_small', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('imdbId')->unique();
            $table->string('tmdbId')->unique();
            $table->string('title');
            $table->string('genres');
            $table->date('releaseDate');
            $table->string('metascore')->nullable();
            $table->string('imdbRating');
            $table->float('imdbVotes');
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
        Schema::dropIfExists('movies_small');
    }
}
