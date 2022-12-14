<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('volunteer_campaigns', function (Blueprint $table) {

            $table->id();
            $table->foreignId('volunteer_campaign_request_id');
            $table->foreignId('location_id');
            $table->string('name')->unique();
            $table->text('image');
            $table->longText('details');
            $table->enum('type',['natural','human','pets','others']);
            $table->integer('age');
            $table->enum('study',['Primary School','Middle School','High School'
                ,'Bachelors Degree','Master Degree','phD Degree','No Studies']);
            $table->integer('volunteer_number');
            $table->integer('current_volunteer_number')->default(0);
            $table->decimal('longitude', 10, 8);
            $table->decimal('latitude', 10, 8);
            $table->date('maxDate');
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
        Schema::dropIfExists('volunteer_campaigns');
    }
};
