<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionsTable extends Migration
{
    public function up()
    {
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id'); // Link to inventory table
            $table->date('date_distribute'); // Date when the item was distributed
            $table->integer('quantity'); // Quantity distributed
            $table->string('remarks')->nullable(); // Any additional remarks
            $table->integer('stocks');
            // Foreign key constraint to link to the inventory table
            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');

            // Timestamps for created_at and updated_at
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('distributions');
    }
}

