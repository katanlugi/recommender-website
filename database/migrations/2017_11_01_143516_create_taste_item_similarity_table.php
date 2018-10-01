<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasteItemSimilarityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taste_item_similarity', function (Blueprint $table) {
            $table->bigInteger('item_id_a');
            $table->bigInteger('item_id_b');
            $table->float('similarity', 8, 7);
            $table->primary(['item_id_a', 'item_id_b']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taste_item_similarity');
    }
}
