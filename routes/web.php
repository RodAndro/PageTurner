<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\AuditCompatibilityController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchasedBooksController;
use App\Http\Controllers\User\DataPortabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'get_books'])->name('guest_books');
Route::get('/search', [BookController::class, 'search'])->name('books.search');
Route::get('/category/{categoryId}', [BookController::class, 'books_by_category'])->name('books.by_category');


Route::get('/dashboard', [BookController::class, 'logged_in_get_books'])
->middleware(['auth', 'verified', 'role_redirect:customer'])
->name('dashboard');

Route::get('/my-dashboard', [CustomerDashboardController::class, 'index'])
->middleware(['auth', 'verified', 'access_control:customer'])
->name('customer.dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware('access_control:admin')->group(function () {
 Route::get('/admin_home', [AdminController::class, 'admin_home'])
->name('admin_home');
 Route::post('/admin/books', [AdminController::class, 'storeBook'])->name('admin.books.store');
 Route::post('/admin/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');

  Route::get('/manage_books', [AdminController::class, 'manage_books'])->name('admin.manage_books');
  Route::put('/admin/books/{book}', [AdminController::class, 'updateBook'])->name('admin.books.update');
  Route::delete('/admin/books/{book}', [AdminController::class, 'deleteBook'])->name('admin.books.delete');

  Route::get('/manage_categories', [AdminController::class, 'manage_categories'])->name('admin.manage_categories');
  Route::put('/admin/categories/{category}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
  Route::delete('/admin/categories/{category}', [AdminController::class, 'deleteCategory'])->name('admin.categories.delete');

  // Advanced Dashboard Routes
  Route::get('/admin/dashboard/advanced', [AdminDashboardController::class, 'index'])->name('admin.dashboard.advanced');

Route::get('/customer_orders', [AdminController::class, 'customer_orders'])->name('admin.customer_orders');
Route::patch('/admin/orders/{order}', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.update');

  // Audit Log Routes
  Route::get('/audit-logs/dashboard', [AuditLogController::class, 'dashboard'])->name('admin.audit-logs.dashboard');
  Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
  Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->name('admin.audit-logs.show');
  Route::post('/audit-logs/save-search', [AuditLogController::class, 'saveSearch'])->name('admin.audit-logs.save-search');
  Route::post('/audit-logs/load-search/{id}', [AuditLogController::class, 'loadSearch'])->name('admin.audit-logs.load-search');
  Route::post('/audit-logs/export', [AuditLogController::class, 'export'])->name('admin.audit-logs.export');
  Route::post('/audit-logs/archive', [AuditLogController::class, 'archiveLogs'])->name('admin.audit-logs.archive');
  Route::get('/audit-logs/archived', [AuditLogController::class, 'archived'])->name('admin.audit-logs.archived');
  Route::post('/audit-logs/verify-integrity', [AuditLogController::class, 'verifyIntegrity'])->name('admin.audit-logs.verify-integrity');


});


Route::middleware(['access_control:customer', 'verified'])->group(function () {
 Route::get('/cart', [CartController::class, 'cart_view'])->name('cart');
Route::patch('/cart/update/{orderItem}', [CartController::class, 'update_quantity'])->name('cart.update');
Route::delete('/cart/remove/{orderItem}', [CartController::class, 'remove_from_cart'])->name('cart.remove');
Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

Route::get('/orders', [OrderController::class, 'show_orders'])->name('orders.show');
Route::patch('/orders/cancel/{order}', [OrderController::class, 'cancel'])->name('orders.cancel');

Route::get('/purchased-books', [PurchasedBooksController::class, 'index'])->name('purchased-books.show');
Route::post('/purchased-books/{book}/review', [PurchasedBooksController::class, 'storeReview'])->name('purchased-books.review');

// Data Export Routes (Customer Dashboard)
Route::post('/dashboard/export/personal', [CustomerDashboardController::class, 'exportPersonalData'])->name('dashboard.export.personal');
Route::post('/dashboard/export/orders', [CustomerDashboardController::class, 'exportOrderHistory'])->name('dashboard.export.orders');
Route::post('/dashboard/export/reading', [CustomerDashboardController::class, 'exportReadingHistory'])->name('dashboard.export.reading');

// Data Portability Routes (GDPR Compliance)
Route::get('/data-portability', [DataPortabilityController::class, 'index'])->name('user.data-portability.index');
Route::post('/data-portability/export-personal', [DataPortabilityController::class, 'exportPersonalData'])->name('user.data-portability.export-personal');
Route::post('/data-portability/export-orders', [DataPortabilityController::class, 'exportOrderHistory'])->name('user.data-portability.export-orders');
Route::post('/data-portability/export-reading', [DataPortabilityController::class, 'exportReadingHistory'])->name('user.data-portability.export-reading');
Route::get('/data-portability/download/{exportLog}', [DataPortabilityController::class, 'downloadExport'])->name('user.data-portability.download');
Route::delete('/data-portability/delete/{exportLog}', [DataPortabilityController::class, 'deleteExport'])->name('user.data-portability.delete');

Route::post('/add_to_cart', [CartController::class, 'add_to_cart' ])->name('add-to-cart');

});





Route::get('/book_details/{id}', [BookController::class, 'books_details'])->name('get_books_details');

Route::prefix('admin/backups')->group(function () {
    Route::post('/create', [BackupController::class, 'create']);
    Route::post('/verify', [BackupController::class, 'verify']);
    Route::post('/restore', [BackupController::class, 'restore']);
    Route::post('/verify-integrity', [BackupController::class, 'verifyIntegrity']);
    Route::get('/analyze-sizes', [BackupController::class, 'analyzeSizes']);
    Route::post('/restore-to-point', [BackupController::class, 'restoreToPoint']);
});

Route::get('/admin/audit-logs/export', [AuditCompatibilityController::class, 'export']);
Route::get('/admin/audit-logs', function (\Illuminate\Http\Request $request, AuditCompatibilityController $controller) {
    if (!auth()->check() && !$request->query()) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    if (auth()->check() && auth()->user()->role !== 'admin') {
        auth()->logout();
        return response()->json(['message' => 'Forbidden'], 403);
    }

    return $controller->index($request);
});

// Import/Export Routes
require __DIR__.'/import-export.php';

// Backup & Maintenance Routes
require __DIR__.'/backup-maintenance.php';

require __DIR__.'/auth.php';
