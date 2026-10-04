<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\HotelNotMappedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Models\CityNode;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Services\RestaurantRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantController extends Controller
{
    public function __construct(private readonly RestaurantRecommendationService $recommendationService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(Restaurant::with('cityNode')->get());
    }

    public function show(Restaurant $restaurant): JsonResponse
    {
        return response()->json($restaurant->load('cityNode'));
    }

    /**
     * Cadastra um restaurante. Sem "city_node_id", cria também o ponto do
     * mapa (tipo "restaurant") com o nome de "node_name".
     *
     * O novo ponto não nasce ligado a nenhuma rua: cadastre ao menos uma
     * aresta em POST /api/city-graph/edges, senão o restaurante não é
     * alcançável e não aparece nas recomendações.
     */
    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $data = $request->validated();

        $restaurant = DB::transaction(function () use ($data) {
            $cityNodeId = $data['city_node_id'] ?? CityNode::create([
                'name' => $data['node_name'],
                'type' => 'restaurant',
            ])->id;

            return Restaurant::create([
                'city_node_id' => $cityNodeId,
                'name' => $data['name'],
                'cuisine' => $data['cuisine'],
                'rating' => $data['rating'] ?? 0,
                'price_range' => $data['price_range'] ?? '$$',
            ]);
        });

        return response()->json($restaurant->load('cityNode'), 201);
    }

    /**
     * Restaurantes ordenados pela menor rota (Dijkstra) a partir do hotel.
     *
     * GET /api/hotels/{hotel}/restaurants/recommendations
     *     ?cuisine=italiana&max_distance_km=2&min_rating=4
     */
    public function recommendations(Request $request, Hotel $hotel): JsonResponse
    {
        $filters = $request->validate([
            'cuisine' => ['nullable', 'string'],
            'max_distance_km' => ['nullable', 'numeric', 'min:0'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ]);

        try {
            $recommendations = $this->recommendationService->recommendFor($hotel, $filters);
        } catch (HotelNotMappedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(array_map(fn (array $r) => [
            'restaurant' => $r['restaurant'],
            'distance_km' => $r['distance_km'],
            'route_node_ids' => $r['route'],
        ], $recommendations));
    }
}
