<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportExportController;

Route::middleware(['auth', 'verified', 'access_control:admin'])->group(function () {
    Route::prefix('admin/import-export')->name('admin.import-export.')->group(function () {
        // Book Import
        Route::get('/books/import', [ImportExportController::class, 'showBookImportForm'])->name('books-import');
        Route::post('/books/import', [ImportExportController::class, 'storeBookImport'])->name('books-import-store');
        Route::get('/books/template', [ImportExportController::class, 'downloadBookTemplate'])->name('books-template');
        Route::get('/books/export', [ImportExportController::class, 'showBookExportForm'])->name('books-export');
        Route::post('/books/export', [ImportExportController::class, 'exportBooks'])->name('books-export-store');

        // Order Export
        Route::get('/orders/export', [ImportExportController::class, 'showOrderExportForm'])->name('orders-export');
        Route::post('/orders/export', [ImportExportController::class, 'exportOrders'])->name('orders-export-store');

        // User Import
        Route::get('/users/import', [ImportExportController::class, 'showUserImportForm'])->name('users-import');
        Route::post('/users/import', [ImportExportController::class, 'storeUserImport'])->name('users-import-store');

        // User Export
        Route::get('/users/export', [ImportExportController::class, 'showUserExportForm'])->name('users-export');
        Route::post('/users/export', [ImportExportController::class, 'exportUsers'])->name('users-export-store');

        // Status and Download
        Route::get('/status', [ImportExportController::class, 'showStatus'])->name('status');
        Route::get('/download/{export_log_id}', [ImportExportController::class, 'downloadExport'])->name('download-export');
    });
});
