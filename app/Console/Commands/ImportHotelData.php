<?php

namespace App\Console\Commands;

use App\Services\ImportService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Importa os XMLs de database/xml/ (hotéis, quartos e reservas).
 * Agendado em routes/console.php; também pode ser executado manualmente:
 *
 *   php artisan import:xml [all|hotels|rooms|reserves]
 */
class ImportHotelData extends Command
{
    protected $signature = 'import:xml {entity=all : all|hotels|rooms|reserves}';

    protected $description = 'Importa hotéis, quartos e reservas dos arquivos XML em database/xml/';

    public function handle(ImportService $importService): int
    {
        $entity = $this->argument('entity');
        $hotelsPath = database_path('xml/hotels.xml');
        $roomsPath = database_path('xml/rooms.xml');
        $reservesPath = database_path('xml/reserves.xml');

        try {
            $result = match ($entity) {
                'hotels' => ['hotels' => $importService->importHotels($hotelsPath)],
                'rooms' => ['rooms' => $importService->importRooms($roomsPath)],
                'reserves' => ['reserves' => $importService->importReserves($reservesPath)],
                'all' => $importService->importAll($hotelsPath, $roomsPath, $reservesPath),
                default => null,
            };

            if ($result === null) {
                $this->error("Entidade inválida: {$entity}. Use all, hotels, rooms ou reserves.");

                return self::INVALID;
            }

            foreach ($result as $name => $count) {
                $this->info("{$count} registro(s) importado(s) em {$name}.");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Falha na importação: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
