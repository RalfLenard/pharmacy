<?php

use App\Http\Controllers\DistributingController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\ReportConroller;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\InventoryController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/dashboard', [ReportController::class, 'index'])->name('dashboard');

// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

Route::middleware(['auth', 'admin'])->group(function () {

    
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{id}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory-delete/{id}', [InventoryController::class, 'destroy']);

    Route::get('/pharmacy', [DistributingController::class, 'index']);
    Route::post('/distribute/{id}', [DistributingController::class, 'distribute'])->name('inventory.distribute');
    Route::delete('/distribution/delete/{id}', [DistributingController::class, 'delete'])->name('distribution.delete');

    Route::get('/recipients', [RecipientController::class, 'index'])->name('recipients');
    Route::get('/medicines', [RecipientController::class, 'medicines'])->name('medicines');

    Route::post('/recipients/store-medicine', [RecipientController::class, 'storeRecipientWithMedicine']);
    Route::post('/recipients/store-medicine-only', [RecipientController::class, 'storeMedicineOnly']);
    Route::put('/recipients/update-medicine/{id}', [RecipientController::class, 'updateRecipientDistribution']);
    // Ensure this is in your `routes/api.php` file if you're using an API route
    Route::get('/recipients/{id}', [RecipientController::class, 'show']);

    Route::get('/report/recipient-distributions/pdf', [ReportController::class, 'generateFilteredPDF']);
    Route::get('/reports/distribution/remarks/{remarks}', [ReportController::class, 'generateByRemarks'])->name('reports.distribution.remarks');

    Route::get('/inventory/report', [ReportController::class, 'generateInventoryReport'])->name('reports.inventory.pdf');

    Route::get('/reports/inventory/check', [ReportController::class, 'checkInventoryLot'])->name('reports.inventory.check');


   // In routes/web.php
    Route::get('/reports/distribution/remarks/{remarks}', [ReportController::class, 'generateByRemarks'])->name('reports.distribution.generate');
    Route::get('/reports/distribution/check', [ReportController::class, 'checkDistributionByRemarks'])->name('reports.distribution.check');

    Route::get('/report/recipient-distributions/check', [ReportController::class, 'checkFilteredPDF'])
    ->name('report.recipient-distributions.check');

    Route::get('/available-months', [ReportController::class, 'getAvailableMonths']);


});








