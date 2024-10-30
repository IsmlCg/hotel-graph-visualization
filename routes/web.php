<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HotelController;
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

// Route for displaying site access
Route::get('/hotels', [HotelController::class, 'index'])->name('home');
Route::post('/get-hotels-room', [HotelController::class, 'getHotelRoom'])->name('hotels.data');
