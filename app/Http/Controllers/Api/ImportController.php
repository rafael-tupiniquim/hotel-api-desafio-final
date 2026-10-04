<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * Dispara a importação dos XMLs via HTTP. É o equivalente do comando
 * "php artisan import:xml" (que é o que o agendador executa) e serve
 * para testes manuais pelo Postman ou pelo painel administrativo.
 */
class ImportController extends Controller
{
    public function __construct(private readonly ImportService $importService)
    {
    }

    public function hotels(): JsonResponse
    {
        return $this->run(fn () => [
            'hotels' => $this->importService->importHotels(database_path('xml/hotels.xml')),
        ]);
    }

    public function rooms(): JsonResponse
    {
        return $this->run(fn () => [
            'rooms' => $this->importService->importRooms(database_path('xml/rooms.xml')),
        ]);
    }

    public function reserves(): JsonResponse
    {
        return $this->run(fn () => [
            'reserves' => $this->importService->importReserves(database_path('xml/reserves.xml')),
        ]);
    }

    public function all(): JsonResponse
    {
        return $this->run(fn () => $this->importService->importAll(
            database_path('xml/hotels.xml'),
            database_path('xml/rooms.xml'),
            database_path('xml/reserves.xml'),
        ));
    }

    private function run(callable $callback): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Importação concluída com sucesso.',
                'imported' => $callback(),
            ]);
        } catch (RuntimeException $e) {
            // Arquivo ausente ou XML inválido: problema com a entrada, não do servidor.
            return response()->json(['message' => 'Falha ao importar os dados.', 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Erro inesperado ao importar os dados.'], 500);
        }
    }
}
