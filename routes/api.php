<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\InventoryController;
use App\Http\Controllers\API\KDSController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\StaffController;
use App\Http\Controllers\API\TableController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/pin-login', [AuthController::class, 'pinLogin']);

// Sanctum CSRF cookie (needed for SPA)
Route::get('/sanctum/csrf-cookie', function () {
    return response()->noContent();
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:menu.categories');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:menu.categories');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:menu.categories');

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store'])->middleware('permission:menu.create');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('permission:menu.edit');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:menu.delete');
    Route::patch('/products/{product}/toggle', [ProductController::class, 'toggleAvailability']);

    // Tables
    Route::get('/tables', [TableController::class, 'index']);
    Route::get('/tables/{table}', [TableController::class, 'show']);
    Route::post('/tables', [TableController::class, 'store'])->middleware('permission:tables.manage');
    Route::put('/tables/{table}', [TableController::class, 'update'])->middleware('permission:tables.manage');
    Route::patch('/tables/{table}/status', [TableController::class, 'updateStatus']);
    Route::delete('/tables/{table}', [TableController::class, 'destroy'])->middleware('permission:tables.manage');
    Route::post('/table-sections', [TableController::class, 'storeSection'])->middleware('permission:tables.manage');

    // Reservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store'])->middleware('permission:orders.create');
    Route::post('/orders/{order}/items', [OrderController::class, 'addItems'])->middleware('permission:orders.edit');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/discount', [OrderController::class, 'applyDiscount'])->middleware('permission:orders.discount');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->middleware('permission:orders.delete');

    // Payments
    Route::post('/orders/{order}/payment', [PaymentController::class, 'process'])->middleware('permission:payments.process');
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->middleware('permission:payments.refund');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/find-by-phone', [CustomerController::class, 'findByPhone']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);

    // KDS (Kitchen Display System)
    Route::get('/kds', [KDSController::class, 'index']);
    Route::patch('/kds/items/{item}/status', [KDSController::class, 'updateItemStatus']);
    Route::patch('/kds/orders/{order}/status', [KDSController::class, 'updateOrderStatus']);

    // Staff
    Route::get('/staff', [StaffController::class, 'index']);
    Route::post('/staff', [StaffController::class, 'store'])->middleware('permission:staff.manage');
    Route::put('/staff/{user}', [StaffController::class, 'update'])->middleware('permission:staff.manage');
    Route::get('/roles', [StaffController::class, 'roles']);
    Route::get('/permissions', [StaffController::class, 'permissions']);
    Route::put('/roles/{role}/permissions', [StaffController::class, 'updateRolePermissions'])->middleware('permission:settings.manage');

    // Inventory
    Route::get('/inventory/items', [InventoryController::class, 'items']);
    Route::post('/inventory/items', [InventoryController::class, 'storeItem'])->middleware('permission:inventory.manage');
    Route::post('/inventory/items/{item}/adjust', [InventoryController::class, 'adjustStock'])->middleware('permission:inventory.manage');
    Route::get('/inventory/suppliers', [InventoryController::class, 'suppliers']);
    Route::post('/inventory/suppliers', [InventoryController::class, 'storeSupplier'])->middleware('permission:inventory.manage');
    Route::get('/inventory/purchase-orders', [InventoryController::class, 'purchaseOrders']);

    // Reports
    Route::get('/reports/sales', [ReportController::class, 'sales'])->middleware('permission:reports.view');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->middleware('permission:inventory.view');

    // Settings
    Route::get('/settings/restaurant', [SettingsController::class, 'restaurant']);
    Route::put('/settings/restaurant', [SettingsController::class, 'updateRestaurant'])->middleware('permission:settings.manage');
    Route::get('/settings/tax-categories', [SettingsController::class, 'taxCategories']);
    Route::post('/settings/tax-categories', [SettingsController::class, 'storeTaxCategory'])->middleware('permission:settings.taxes');
});
