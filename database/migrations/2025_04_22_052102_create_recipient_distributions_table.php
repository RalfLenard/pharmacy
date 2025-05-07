<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipientDistributionsTable extends Migration
{
    public function up()
    {
        Schema::create('recipient_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->constrained('recipients')->onDelete('cascade');
            $table->foreignId('distribution_id')->constrained('distributions')->onDelete('cascade');
            
            $table->integer('quantity');
            $table->date('date_given');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipient_distributions');
    }
}
