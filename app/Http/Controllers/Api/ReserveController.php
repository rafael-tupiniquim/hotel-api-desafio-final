<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\RoomNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReserveRequest;
use App\Models\Reserve;
use App\Services\ReserveService;
use Illuminate\Http\JsonResponse;

class ReserveController extends Controller
{
    public function __construct(private readonly ReserveService $reserveService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(Reserve::with('guests', 'dailies', 'payments')->get());
    }

    public function show(Reserve $reserve): JsonResponse
    {
        return response()->json($reserve->load('guests', 'dailies', 'payments'));
    }

    /** Cria a reserva; responde 409 se o quarto estiver ocupado no período. */
    public function store(StoreReserveRequest $request): JsonResponse
    {
        try {
            $reserve = $this->reserveService->createReserve($request->validated());

            return response()->json($reserve, 201);
        } catch (RoomNotAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function destroy(Reserve $reserve): JsonResponse
    {
        $reserve->delete();

        return response()->json(null, 204);
    }
}
