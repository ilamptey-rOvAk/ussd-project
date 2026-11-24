<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UssdController;

Route::match(['get', 'post'], '/ussd', [UssdController::class, 'handle'])->name('ussd.callback');
