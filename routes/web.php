<?php

use App\Support\Dashboard\KelajakMaskanDashboardData;
use App\Support\Dashboard\KelajakMaskanWantDetailData;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function (KelajakMaskanDashboardData $dashboard) {
    return view('dashboard', $dashboard->build());
});

Route::get('/wants/{want}', function (int $want, KelajakMaskanWantDetailData $detail) {
    $payload = $detail->build($want);

    abort_if($payload === null, Response::HTTP_NOT_FOUND);

    return view('wants.show', $payload);
})->name('wants.show');
