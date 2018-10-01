<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecommenderPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recommender_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('force_train')->default(true);
            $table->boolean('evaluate_model')->default(true);
            $table->boolean('implicit_pref')->default(true);
            $table->boolean('set_non_negative')->default(false);
            $table->boolean('save')->default(true);
            $table->integer('num_iterations')->default(20);
            $table->integer('num_features')->default(20);

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
        Schema::dropIfExists('recommender_preferences');
    }
}
