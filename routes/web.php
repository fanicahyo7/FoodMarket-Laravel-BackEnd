<?php

use App\Http\Controllers\API\MidtransController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-sentry', function () {
    throw new Exception('My first Sentry error!');
});
Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');


//midtrans
Route::get('midtrans/success', [MidtransController::class, 'success']);
Route::get('midtrans/error', [MidtransController::class, 'error']);
Route::get('midtrans/unfinish', [MidtransController::class, 'unfinish']);