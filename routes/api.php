<?php

use App\Http\Controllers\Api\CityGraphController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\ReserveController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;

/*
| Rotas da API. O prefixo /api é aplicado em bootstrap/app.php (withRouting).
|
| A importação dos XMLs é feita pelo comando "php artisan import:xml" (agendado
| em routes/console.php); as rotas /import são um atalho HTTP para o mesmo fluxo.
*/

Route::prefix('import')->group(function () {
    Route::post('/all', [ImportController::class, 'all']);
    Route::post('/hotels', [ImportController::class, 'hotels']);
    Route::post('/rooms', [ImportController::class, 'rooms']);
    Route::post('/reserves', [ImportController::class, 'reserves']);
});

// Hotéis
Route::apiResource('hotels', HotelController::class);

// Quartos e consulta de disponibilidade
Route::get('rooms/{room}/availability', [RoomController::class, 'availability']);
Route::apiResource('rooms', RoomController::class);

// Reservas (a criação valida a disponibilidade do quarto)
Route::apiResource('reserves', ReserveController::class)->except(['update']);

// Restaurantes e recomendação por proximidade (Dijkstra)
Route::get('restaurants', [RestaurantController::class, 'index']);
Route::post('restaurants', [RestaurantController::class, 'store']);
Route::get('restaurants/{restaurant}', [RestaurantController::class, 'show']);
Route::get('hotels/{hotel}/restaurants/recommendations', [RestaurantController::class, 'recommendations']);

// Mapa da cidade: pontos e ruas
Route::get('city-graph', [CityGraphController::class, 'index']);
Route::post('city-graph/nodes', [CityGraphController::class, 'storeNode']);
Route::post('city-graph/edges', [CityGraphController::class, 'storeEdge']);
