<?php

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\Auth\AdminController;
use App\Http\Controllers\Api\Auth\CustomerController;
use App\Http\Controllers\Api\Auth\DeliveryController;
use App\Http\Controllers\Api\OrderMangementController;

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');


    Route::prefix('/customer')->group(function () {
        Route::post('/register', [CustomerController::class, 'Register']);
        Route::post('/login', [CustomerController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [CustomerController::class, 'logout']);
    });
});

    Route::prefix('delivery')->group(function () {
        Route::post('/register', [DeliveryController::class, 'Register']);
        Route::post('/login', [DeliveryController::class, 'login']);

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/logout', [DeliveryController::class, 'logout']);
    });
});
    Route::prefix('admin')->group(function () {
        Route::post('/register', [AdminController::class, 'Register']);
        Route::post('/login', [AdminController::class, 'login']);

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/logout', [AdminController::class, 'logout']);
    });
    });


    Route::middleware(['auth:sanctum','permission:create products'])->group(function () {
        Route::resource('Products', ProductController::class);
        Route::delete('Products/forceDelete/{id}',[ProductController::class,'forceDelete']);
        Route::PATCH('Products/restore/{id}',[ProductController::class,'restore']);
        Route::post('Products/filter', [ProductController::class, 'filter']);
    });

    Route::middleware(['auth:sanctum','permission:create categories'])->group(function () {
        Route::resource('Categories', CategoryController::class);
        Route::delete('Categories/forceDelete/{id}',[CategoryController::class,'forceDelete']);
        Route::Post('Categories/restore/{id}',[CategoryController::class,'restore']);


    });


    Route::middleware(['auth:sanctum','permission:create orders'])->group(function () {
        Route::resource('Carts', CartController::class);
        Route::delete('delete-all-Carts', [CartController::class,'undo']);
    });

    Route::get('Products/Products_by_Category/{Category_id}', [ProductController::class, 'Products_by_Category'])->middleware(['auth:sanctum']);

    Route::middleware(['auth:sanctum','permission:create orders'])->group(function () {

        Route::post('checkout', [CheckoutController::class,'checkout']);
        Route::get('order-history', [CheckoutController::class,'orderHistory']);
        Route::get('order/{orderid}', [CheckoutController::class,'orderDetails']);
        Route::post('create-payment/{order}', [PaymentController::class,'createPayment']);

});
    Route::post('webhook', [PaymentController::class,'webhook'])->middleware('auth:sanctum');



    Route::middleware(['auth:sanctum','permission:create orders'])->group(function () {

        Route::get('order-management', [OrderMangementController::class,'index']);
        Route::get('order-management/{order}', [OrderMangementController::class,'show']);
        Route::PUT('order-management/update-status/{id}', [OrderMangementController::class,'updateStatus']);

    });
    Route::middleware(['auth:sanctum','permission:create products'])->group(function () {

        Route::resource('Favorites', FavoriteController::class);
    });
