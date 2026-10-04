<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Services\ReserveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(private readonly ReserveService $reserveService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(Room::with('hotel')->get());
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = Room::create($request->validated());

        return response()->json($room, 201);
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json($room->load('hotel'));
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $room->update($request->validated());

        return response()->json($room);
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return response()->json(null, 204);
    }

    /** GET /api/rooms/{room}/availability?check_in=YYYY-MM-DD&check_out=YYYY-MM-DD */
    public function availability(Request $request, Room $room): JsonResponse
    {
        $data = $request->validate([
            'check_in' => ['required', 'date', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date', 'date_format:Y-m-d', 'after:check_in'],
        ]);

        $available = $this->reserveService->isRoomAvailable(
            $room->id,
            $data['check_in'],
            $data['check_out']
        );

        return response()->json([
            'room_id' => $room->id,
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'available' => $available,
        ]);
    }
}
