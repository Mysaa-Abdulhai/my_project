<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('name');
            $table->string('gender');
            $table->date('birth_date');
            $table->string('nationality')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->string('study')->nullable();
            $table->string('skills')->nullable();
            $table->integer('phoneNumber')->nullable();
            $table->boolean('leaderInFuture')->default(false);
            $table->text('image');
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
        Schema::dropIfExists('profiles');
    }
};
