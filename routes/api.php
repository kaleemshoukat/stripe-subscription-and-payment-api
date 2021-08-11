<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SubscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix'=> 'v1'], function () {
    Route::post('/login', [UserController::class, 'login']);

    Route::group(['prefix'=> 'user', 'middleware' => 'auth:user'], function () {
        Route::get('/logout', [UserController::class, 'logout']);

        //stripe subscription
        Route::post('/create-product', [SubscriptionController::class, 'createProduct']);
        Route::post('/create-price', [SubscriptionController::class, 'createPrice']);

        Route::post("/create-subscription", [SubscriptionController::class, 'createSubscription']);
        Route::get("/cancel-subscription", [SubscriptionController::class, 'cancelSubscription']);

        //stripe payment
        Route::post('/deduct-payment', [PaymentController::class, 'deductPayment']);
        Route::get('/refund-payment', [PaymentController::class, 'refundPayment']);
    });
});
