<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
        'docs' => 'Veja o README.md para a lista de endpoints (/api/*).',
    ]);
});
