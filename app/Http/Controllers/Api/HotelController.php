<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;

class HotelController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Hotel::with('rooms')->get());
    }

    public function store(StoreHotelRequest $request): JsonResponse
    {
        $hotel = Hotel::create($request->validated());

        return response()->json($hotel, 201);
    }

    public function show(Hotel $hotel): JsonResponse
    {
        return response()->json($hotel->load('rooms'));
    }

    public function update(UpdateHotelRequest $request, Hotel $hotel): JsonResponse
    {
        $hotel->update($request->validated());

        return response()->json($hotel);
    }

    public function destroy(Hotel $hotel): JsonResponse
    {
        $hotel->delete();

        return response()->json(null, 204);
    }
}
