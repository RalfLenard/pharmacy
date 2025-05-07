<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipientsTable extends Migration
{
    public function up()
    {
        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->date('birthdate');
            $table->string('barangay');
            $table->string('gender')->nullable();
            $table->timestamps();

            $table->unique(['full_name', 'birthdate', 'barangay']); // Prevent duplicates
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipients');
    }
}
