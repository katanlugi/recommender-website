<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->bigIncrements('id');
            $table->string('username')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // to avoid conflicts with imported ratings, we start the user ID at 1000
        // this may hav to be adjusted based on the number of users 
        // that are present in the imported ratings.
        if (env('DB_CONNECTION') === 'sqlite') {
          DB::update("UPDATE SQLITE_SEQUENCE SET seq = 1000000 WHERE name = 'users'");
        } else {
          DB::update("ALTER TABLE users AUTO_INCREMENT = 1000000;");
        }
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
