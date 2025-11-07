<?php

use App\Http\Controllers\Api\SubscriptionStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Row;

// Ping endpoint for connection testing (no auth required)
Route::get('/ping', function () {
    return response()->json([
        'message' => 'pong',
        'timestamp' => time(),
        'server_time' => now()->toISOString()
    ]);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum', 'subscription.active'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/subscription/status', SubscriptionStatusController::class);
});

Route::middleware(['auth:sanctum', 'subscription.active'])->group(function () {
    Route::get('/cashier/status', [\App\Http\Controllers\Api\CashierSessionController::class, 'status']);
    Route::post('/cashier/open', [\App\Http\Controllers\Api\CashierSessionController::class, 'open']);
    Route::post('/cashier/close', [\App\Http\Controllers\Api\CashierSessionController::class, 'close']);
    Route::get('/cashier/reports', [\App\Http\Controllers\Api\CashierSessionController::class, 'reports']);
    Route::post('/cashier/reports/{report}/resend-email', [\App\Http\Controllers\Api\CashierSessionController::class, 'resendEmail']);
    Route::get('/cashier/outflows', [\App\Http\Controllers\Api\CashierOutflowController::class, 'index']);
    Route::get('/cashier/outflows/history', [\App\Http\Controllers\Api\CashierOutflowController::class, 'history']);
    Route::post('/cashier/outflows', [\App\Http\Controllers\Api\CashierOutflowController::class, 'store']);
    Route::post('/cashier/outflows/sync', [\App\Http\Controllers\Api\CashierOutflowController::class, 'sync']);
    Route::post('/raw-materials/stock-summary/email', [\App\Http\Controllers\Api\RawMaterialController::class, 'sendStockSummaryEmail']);
    Route::get('/outlets/{outlet}/printer-settings', [\App\Http\Controllers\Api\PrinterSettingController::class, 'show']);
    Route::post('/outlets/{outlet}/printer-settings', [\App\Http\Controllers\Api\PrinterSettingController::class, 'update']);
    Route::put('/outlets/{outlet}/printer-settings', [\App\Http\Controllers\Api\PrinterSettingController::class, 'update']);

    Route::get('/users/me', [\App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::post('/users/me', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::put('/users/me', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
});

Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/email/resend', [\App\Http\Controllers\Api\AuthController::class, 'resendVerification'])->name('verification.resend');

Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Api\AuthController::class, 'verify'])
    ->name('api.verification.verify')
    ->middleware(['signed', 'throttle:6,1']); // validasi signature & rate limit

// post login
Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// post logout
Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

// Employee PIN login removed (attendance module disabled)
// Route::post('/auth/pin-login', [\App\Http\Controllers\Api\EmployeeAuthController::class, 'pinLogin'])->middleware('throttle:attendance');

Route::post('/outlets/verify-pin', [\App\Http\Controllers\Api\OutletPinController::class, 'verify'])->middleware(['auth:sanctum', 'subscription.active']);

// api resource product
// Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class)->middleware('auth:sanctum');
Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index'])->middleware(['auth:sanctum', 'subscription.active']);
Route::get('/products/by-category/{categoryId}', [App\Http\Controllers\Api\ProductController::class, 'getByCategory'])->middleware(['auth:sanctum', 'subscription.active']);
Route::get('/products/with-stock', [App\Http\Controllers\Api\ProductController::class, 'getWithStock'])->middleware(['auth:sanctum', 'subscription.active']);
Route::post('/products', [App\Http\Controllers\Api\ProductController::class, 'store'])->middleware(['auth:sanctum', 'subscription.active']);
Route::post('/products/edit', [App\Http\Controllers\Api\ProductController::class, 'update'])->middleware(['auth:sanctum', 'subscription.active']);
Route::delete('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'destroy'])->middleware(['auth:sanctum', 'subscription.active']);

// categories
Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index'])->middleware(['auth:sanctum', 'subscription.active']);
Route::get('/categories/{id}', [App\Http\Controllers\Api\CategoryController::class, 'show'])->middleware(['auth:sanctum', 'subscription.active']);
Route::post('/categories', [App\Http\Controllers\Api\CategoryController::class, 'store'])->middleware(['auth:sanctum', 'subscription.active']);
Route::post('/categories/edit', [App\Http\Controllers\Api\CategoryController::class, 'update'])->middleware(['auth:sanctum', 'subscription.active']);
Route::delete('/categories/{id}', [App\Http\Controllers\Api\CategoryController::class, 'destroy'])->middleware(['auth:sanctum', 'subscription.active']);

// Product recipe & production endpoints
Route::middleware(['auth:sanctum', 'subscription.active'])->group(function () {
    Route::get('/products/{id}/recipe', [App\Http\Controllers\Api\ProductRecipeController::class, 'showRecipe']);
    Route::post('/products/{id}/recipe', [App\Http\Controllers\Api\ProductRecipeController::class, 'storeRecipe']);
    Route::get('/products/{id}/cogs', [App\Http\Controllers\Api\ProductRecipeController::class, 'cogs']);
});

// api resource order

Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store'])->middleware(['auth:sanctum', 'subscription.active']);
Route::post('/orders/bulk', [App\Http\Controllers\Api\OrderController::class, 'bulkStore'])->middleware(['auth:sanctum', 'subscription.active']);
Route::get('/orders/date', [App\Http\Controllers\Api\OrderController::class, 'index'])->middleware(['auth:sanctum', 'subscription.active']);
Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'getAllOrder'])->middleware(['auth:sanctum', 'subscription.active']);
Route::post('orders/{id}/refund', [App\Http\Controllers\Api\OrderController::class, 'refund'])->middleware(['auth:sanctum', 'subscription.active']);

/* Disabled finance API routes — web-only module
// Finance: incomes & expenses (with categories)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/incomes', [App\Http\Controllers\Api\IncomeController::class, 'index']);
    Route::post('/incomes', [App\Http\Controllers\Api\IncomeController::class, 'store']);
    Route::get('/incomes/{income}', [App\Http\Controllers\Api\IncomeController::class, 'show']);
    Route::put('/incomes/{income}', [App\Http\Controllers\Api\IncomeController::class, 'update']);
    Route::delete('/incomes/{income}', [App\Http\Controllers\Api\IncomeController::class, 'destroy']);

    Route::get('/expenses', [App\Http\Controllers\Api\ExpenseController::class, 'index']);
    Route::post('/expenses', [App\Http\Controllers\Api\ExpenseController::class, 'store']);
    Route::get('/expenses/{expense}', [App\Http\Controllers\Api\ExpenseController::class, 'show']);
    Route::put('/expenses/{expense}', [App\Http\Controllers\Api\ExpenseController::class, 'update']);
    Route::delete('/expenses/{expense}', [App\Http\Controllers\Api\ExpenseController::class, 'destroy']);

    Route::get('/income-categories', [App\Http\Controllers\Api\IncomeCategoryController::class, 'index']);
    Route::post('/income-categories', [App\Http\Controllers\Api\IncomeCategoryController::class, 'store']);
    Route::put('/income-categories/{income_category}', [App\Http\Controllers\Api\IncomeCategoryController::class, 'update']);
    Route::delete('/income-categories/{income_category}', [App\Http\Controllers\Api\IncomeCategoryController::class, 'destroy']);

    Route::get('/expense-categories', [App\Http\Controllers\Api\ExpenseCategoryController::class, 'index']);
    Route::post('/expense-categories', [App\Http\Controllers\Api\ExpenseCategoryController::class, 'store']);
    Route::put('/expense-categories/{expense_category}', [App\Http\Controllers\Api\ExpenseCategoryController::class, 'update']);
    Route::delete('/expense-categories/{expense_category}', [App\Http\Controllers\Api\ExpenseCategoryController::class, 'destroy']);
});
*/

// Raw materials & inventory endpoints
Route::middleware(['auth:sanctum', 'subscription.active'])->group(function () {
    Route::get('/raw-materials', [App\Http\Controllers\Api\RawMaterialController::class, 'index']);
    Route::post('/raw-materials', [App\Http\Controllers\Api\RawMaterialController::class, 'store']);
    Route::put('/raw-materials/{id}', [App\Http\Controllers\Api\RawMaterialController::class, 'update']);
    Route::delete('/raw-materials/{id}', [App\Http\Controllers\Api\RawMaterialController::class, 'destroy']);
    Route::post('/raw-materials/{id}/adjust', [App\Http\Controllers\Api\RawMaterialController::class, 'adjustStock']);
    Route::post('/raw-materials/{id}/purchase', [App\Http\Controllers\Api\RawMaterialController::class, 'purchase']);
    Route::post('/raw-materials/{id}/stock-out', [App\Http\Controllers\Api\RawMaterialController::class, 'stockOut']);
    Route::post('/raw-materials/{id}/opname', [App\Http\Controllers\Api\RawMaterialController::class, 'opname']);
    Route::post('/raw-materials/{id}/transfer', [App\Http\Controllers\Api\RawMaterialController::class, 'transfer']);

    Route::post('/catalog-duplication', [\App\Http\Controllers\Api\CatalogDuplicationController::class, 'store']);
    Route::get('/catalog-duplication/jobs/{job}', [\App\Http\Controllers\Api\CatalogDuplicationController::class, 'show']);
});

// Inventory summary report
Route::get('/inventory/summary', [App\Http\Controllers\Api\InventoryReportController::class, 'summary'])->middleware(['auth:sanctum', 'subscription.active']);

// Employee management endpoints removed (employee module disabled)

// Attendance API removed (attendance module disabled)

// Attendance report API removed

// Batch sync endpoints for offline support
Route::prefix('sync')->middleware(['auth:sanctum', 'subscription.active'])->group(function () {
    Route::post('/categories/batch', [App\Http\Controllers\Api\SyncController::class, 'batchSyncCategories']);
    Route::post('/products/batch', [App\Http\Controllers\Api\SyncController::class, 'batchSyncProducts']);
    Route::post('/orders/batch', [App\Http\Controllers\Api\SyncController::class, 'batchSyncOrders']);
    Route::get('/status', [App\Http\Controllers\Api\SyncController::class, 'getSyncStatus']);
    Route::post('/resolve-conflicts', [App\Http\Controllers\Api\SyncController::class, 'resolveConflicts']);
});

//report summary page
Route::middleware(['auth:sanctum', 'subscription.active'])->group(function () {
    Route::get('/reports/summary', [\App\Http\Controllers\Api\ReportController::class, 'summary']);
    Route::get('/reports/product-sales', [\App\Http\Controllers\Api\ReportController::class, 'productSales']);
});
