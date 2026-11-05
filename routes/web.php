<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', DashboardController::class)->name('dashboard');
Route::resource('printers', PrinterController::class)->except('show');
Route::post('printers/{printer}/test', [PrinterController::class, 'test'])->name('printers.test');
Route::resource('recipes', RecipeController::class)->except('show');
Route::get('operator/recipes/{recipe}', [OperatorController::class, 'show'])->name('operator.show');
Route::post('operator/recipes/{recipe}/send', [OperatorController::class, 'send'])->name('operator.send');
