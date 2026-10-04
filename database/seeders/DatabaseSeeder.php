<?php

namespace Database\Seeders;

use App\Services\ImportService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Importa os XMLs de database/xml/ (como `php artisan import:xml`) e cria o mapa de exemplo. */
    public function run(ImportService $importService): void
    {
        $importService->importAll(
            database_path('xml/hotels.xml'),
            database_path('xml/rooms.xml'),
            database_path('xml/reserves.xml'),
        );

        $this->call(CityGraphSeeder::class);
    }
}
