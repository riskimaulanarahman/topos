<?php

use App\Http\Controllers\Admin\PaymentSubmissionController;
use App\Http\Controllers\Admin\PaymentAccountController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GrafikSalesController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    // Jika sudah login, redirect ke home; jika belum, tampilkan halaman login
    return auth()->check()
        ? redirect()->route('home')
        : view('pages.auth.login');
})->middleware('guest');

Route::get('/run-worker', function () {
    try {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true, // langsung berhenti kalau kosong
            '--queue' => 'mail',
        ]);

        return response('Executed 1 job from queue.', 200);
    } catch (\Exception $e) {
        return response('Error: ' . $e->getMessage(), 500);
    }
});

Route::get('/run-worker2', function () {
    try {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true, // langsung berhenti kalau kosong
        ]);

        return response('Executed 1 job from queue.', 200);
    } catch (\Exception $e) {
        return response('Error: ' . $e->getMessage(), 500);
    }
});

Route::middleware(['auth'])->group(function () {
    Route::middleware(['subscription.active', 'outlet.context'])->group(function () {
        Route::get('home', [DashboardController::class, 'index'])->name('home');

        // Convenience redirect: /products -> /product
        Route::get('/products', function () { return redirect()->route('product.index'); })->name('products.redirect');

        // Admin-only web modules
        Route::middleware('role:admin,user')->group(function () {
            // Inventory (web)
            Route::get('/raw-materials', [\App\Http\Controllers\RawMaterialWebController::class, 'index'])->name('raw-materials.index');
            Route::get('/raw-materials/create', [\App\Http\Controllers\RawMaterialWebController::class, 'create'])->name('raw-materials.create');
            Route::post('/raw-materials', [\App\Http\Controllers\RawMaterialWebController::class, 'store'])->name('raw-materials.store');
            Route::get('/raw-materials/send-stock-alert', [\App\Http\Controllers\RawMaterialWebController::class, 'sendStockAlert'])->name('raw-materials.send-stock-alert');
            Route::get('/raw-materials/{raw_material}/edit', [\App\Http\Controllers\RawMaterialWebController::class, 'edit'])->name('raw-materials.edit');
            Route::put('/raw-materials/{raw_material}', [\App\Http\Controllers\RawMaterialWebController::class, 'update'])->name('raw-materials.update');
            Route::delete('/raw-materials/{raw_material}', [\App\Http\Controllers\RawMaterialWebController::class, 'destroy'])->name('raw-materials.destroy');
            Route::get('/raw-materials/{raw_material}/adjust', [\App\Http\Controllers\RawMaterialWebController::class, 'adjustForm'])->name('raw-materials.adjust-form');
            Route::post('/raw-materials/{raw_material}/adjust', [\App\Http\Controllers\RawMaterialWebController::class, 'adjust'])->name('raw-materials.adjust');
            Route::get('/raw-materials/{raw_material}/transfer', [\App\Http\Controllers\RawMaterialTransferController::class, 'create'])->name('raw-materials.transfer-form');
            Route::post('/raw-materials/{raw_material}/transfer', [\App\Http\Controllers\RawMaterialTransferController::class, 'store'])->name('raw-materials.transfer');

            Route::get('/products/{product}/recipe', [\App\Http\Controllers\ProductRecipeWebController::class, 'edit'])->name('product-recipes.edit');
            Route::post('/products/{product}/recipe', [\App\Http\Controllers\ProductRecipeWebController::class, 'update'])->name('product-recipes.update');

            Route::resource('product-options', \App\Http\Controllers\ProductOptionController::class)
                ->except(['show'])
                ->parameters(['product-options' => 'productOption']);

            Route::get('/catalog-duplication', [\App\Http\Controllers\CatalogDuplicationController::class, 'index'])->name('catalog-duplication.index');
            Route::get('/catalog-duplication/create', [\App\Http\Controllers\CatalogDuplicationController::class, 'create'])->name('catalog-duplication.create');
            Route::get('/catalog-duplication/source-data', [\App\Http\Controllers\CatalogDuplicationController::class, 'sourceData'])->name('catalog-duplication.source-data');
            Route::post('/catalog-duplication', [\App\Http\Controllers\CatalogDuplicationController::class, 'store'])->name('catalog-duplication.store');
            Route::get('/catalog-duplication/jobs/{job}', [\App\Http\Controllers\CatalogDuplicationController::class, 'show'])->name('catalog-duplication.jobs.show');
        });
        Route::get('home/filter', [DashboardController::class, 'filter'])->name('dashboard_grafik.filter');
        Route::get('dashboard/sales-series', [GrafikSalesController::class, 'series'])->name('dashboard.sales_series');
        Route::get('dashboard/sales-series.csv', [GrafikSalesController::class, 'seriesCsv'])->name('dashboard.sales_series_csv');

        Route::resource('user', UserController::class)->middleware('role:admin');
        Route::resource('units', UnitController::class)->middleware('role:admin');
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/subscriptions', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'index'])->name('admin.subscriptions.index');
            Route::put('/admin/subscriptions/{user}', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'update'])->name('admin.subscriptions.update');

            Route::get('/admin/billing/payments', [PaymentSubmissionController::class, 'index'])->name('admin.billing.payments.index');
            Route::get('/admin/billing/payments/{submission}', [PaymentSubmissionController::class, 'show'])->name('admin.billing.payments.show');
            Route::post('/admin/billing/payments/{submission}/approve', [PaymentSubmissionController::class, 'approve'])->name('admin.billing.payments.approve');
            Route::post('/admin/billing/payments/{submission}/reject', [PaymentSubmissionController::class, 'reject'])->name('admin.billing.payments.reject');
            Route::resource('/admin/payment-accounts', PaymentAccountController::class)
                ->except(['show'])
                ->names('admin.billing.accounts');
        });

        Route::get('product/{product}/option-presets', [\App\Http\Controllers\ProductController::class, 'optionPresets'])->name('product.option-presets');
        Route::resource('product', \App\Http\Controllers\ProductController::class);
        Route::post('product/request', [\App\Http\Controllers\ProductController::class, 'requestCreate'])->name('product.request');
        Route::get('product/{product}/request-edit', [\App\Http\Controllers\ProductController::class, 'requestEdit'])->name('product.request.edit');
        Route::post('product/{product}/request-update', [\App\Http\Controllers\ProductController::class, 'requestUpdate'])->name('product.request.update');
        Route::post('product/{product}/request-delete', [\App\Http\Controllers\ProductController::class, 'requestDelete'])->name('product.request.delete');
        // Removed product wizard routes (no wizard/recipe/review forms)
        Route::resource('order', \App\Http\Controllers\OrderController::class);
        Route::get('/order/{id}/details-json', [\App\Http\Controllers\OrderController::class, 'showJson'])->name('order.details_json');
        Route::resource('category', \App\Http\Controllers\CategoryController::class);
        Route::post('category/request', [\App\Http\Controllers\CategoryController::class, 'requestStore'])->name('category.request');
        Route::get('discounts/product-search', [\App\Http\Controllers\DiscountController::class, 'searchProducts'])
            ->name('discount.products.search');
        Route::resource('discount', \App\Http\Controllers\DiscountController::class);
        Route::resource('additional_charge', \App\Http\Controllers\AdditionalChargeController::class);
        Route::get('/report', [\App\Http\Controllers\ReportController::class, 'index'])->name('report.index');
        Route::get('/report/filter', [ReportController::class, 'filter'])->name('filter.index');
        Route::get('/report/download', [ReportController::class, 'download'])->name('report.download');
        Route::get('/report/by-category', [ReportController::class, 'byCategory'])->name('report.byCategory');
        Route::get('/report/by-category/items', [ReportController::class, 'categoryItems'])->name('report.byCategory.items');
        Route::get('/report/by-category/download', [ReportController::class, 'downloadByCategory'])->name('report.byCategory.download');
        Route::get('/report/detail', [ReportController::class, 'detail'])->name('report.detail');
        Route::get('/report/detail/download', [ReportController::class, 'downloadDetail'])->name('report.detail.download');
        // New report menus
        Route::get('/report/payments', [ReportController::class, 'payments'])->name('report.payments');
        Route::get('/report/time', [ReportController::class, 'timeAnalysis'])->name('report.time');
        Route::get('/report/refunds', [ReportController::class, 'refunds'])->name('report.refunds');

        Route::get('/summary', [\App\Http\Controllers\SummaryController::class, 'index'])->name('summary.index');
        Route::get('/summary/filter-summary', [\App\Http\Controllers\SummaryController::class, 'filterSummary'])->name('filterSummary.index');

        Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

        // Product Sales Report - Simplified
        Route::get('/report/product-sales', [\App\Http\Controllers\ProductSalesController::class, 'index'])->name('report.product-sales');
        Route::get('/report/product-sales/filter', [\App\Http\Controllers\ProductSalesController::class, 'filter'])->name('report.product-sales.filter');
        Route::get('/report/product-sales/download', [\App\Http\Controllers\ProductSalesController::class,'download'])->name('report.product-sales.download');

        // Route::get('/finance-masuk', [\App\Http\Controllers\FinanceController::class, 'index'])->name('finance.masuk');
        // Income & Expenses are available to authenticated users (web-only module)
        Route::resource('income', \App\Http\Controllers\IncomeController::class);
        Route::resource('expenses', \App\Http\Controllers\ExpenseWebController::class)->except(['show']);
        Route::post('expenses/{expense}/duplicate', [\App\Http\Controllers\ExpenseWebController::class, 'duplicate'])->name('expenses.duplicate');
        // Route::resource('finance-keluar', \\App\\Http\\Controllers\\FinanceController::class);
        // Route::get('/finance-keluar', [\\App\\Http\\Controllers\\FinanceController::class, 'index'])->name('finance.keluar');

        Route::post('outlet/switch', [\App\Http\Controllers\OutletSwitchController::class, 'update'])->name('outlets.switch');

        // Outlets & partners
        Route::resource('outlets', \App\Http\Controllers\OutletController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);

        Route::middleware('outlet.access')->group(function () {
            Route::put('outlets/{outlet}/pin', [\App\Http\Controllers\OutletPinController::class, 'update'])->name('outlets.pin.update');
            Route::get('outlets/{outlet}/partners', [\App\Http\Controllers\OutletPartnerController::class, 'index'])->name('outlets.partners.index');
            Route::post('outlets/{outlet}/partners', [\App\Http\Controllers\OutletPartnerController::class, 'store'])->name('outlets.partners.store');
            Route::put('outlets/{outlet}/partners/{member}/permissions', [\App\Http\Controllers\OutletPartnerController::class, 'updatePermissions'])->name('outlets.partners.permissions');
            Route::delete('outlets/{outlet}/partners/{member}', [\App\Http\Controllers\OutletPartnerController::class, 'destroy'])->name('outlets.partners.destroy');

            Route::post('outlets/{outlet}/partners/{member}/category-requests', [\App\Http\Controllers\PartnerCategoryRequestController::class, 'store'])->name('outlets.partners.categories.request');
            Route::get('outlets/{outlet}/category-requests', [\App\Http\Controllers\PartnerCategoryRequestController::class, 'index'])->name('outlets.category-requests.index');
            Route::post('outlets/{outlet}/category-requests/{changeRequest}/approve', [\App\Http\Controllers\PartnerCategoryRequestController::class, 'approve'])->name('outlets.category-requests.approve');
            Route::post('outlets/{outlet}/category-requests/{changeRequest}/reject', [\App\Http\Controllers\PartnerCategoryRequestController::class, 'reject'])->name('outlets.category-requests.reject');
        });
    });

    Route::get('/billing', [BillingController::class, 'index'])
        ->middleware('outlet.context')
        ->name('billing.index');
    Route::post('/billing/payments', [BillingController::class, 'store'])
        ->middleware('outlet.context')
        ->name('billing.payments.store');
});

Route::get('/partner-invitations/{token}', [\App\Http\Controllers\PartnerInvitationController::class, 'show'])
    ->middleware('auth')
    ->name('partner-invitations.show');
Route::post('/partner-invitations/{token}', [\App\Http\Controllers\PartnerInvitationController::class, 'accept'])
    ->middleware('auth')
    ->name('partner-invitations.accept');
