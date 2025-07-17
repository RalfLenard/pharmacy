<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockTypeToInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->enum('stock_type', ['LGU Procured', 'DOH', 'Trust Funds', 'Donations'])->default('LGU Procured')->after('stocks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('stock_type');
        });
    }
}
