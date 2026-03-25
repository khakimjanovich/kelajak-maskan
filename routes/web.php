<?php

use App\Support\Dashboard\KelajakMaskanDashboardData;
use Illuminate\Support\Facades\Route;

Route::get('/', function (KelajakMaskanDashboardData $dashboard) {
    return view('dashboard', $dashboard->build());
});
