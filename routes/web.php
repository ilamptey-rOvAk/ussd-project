<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UssdController;

Route::get('/', function () {
    return response()->json([
        'service' => 'Nalo Solutions USSD Application (Laravel)',
        'status' => 'online',
        'version' => '1.0.0',
        'ussd_endpoint' => url('/ussd'),
        'api_ussd_endpoint' => url('/api/ussd'),
    ]);
});

Route::match(['get', 'post'], '/ussd', [UssdController::class, 'handle']);
