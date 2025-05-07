<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->date('date_in');
            $table->integer('quantity')->unsigned();
            $table->integer('stocks')->unsigned();
            $table->string('brand_name');
            $table->string('utils');
            $table->string('generic_name');
            $table->string('lot_number')->nullable(); // or batch number
            $table->date('expiration_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
}

