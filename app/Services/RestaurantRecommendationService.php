<?php

namespace App\Services;

use App\Exceptions\HotelNotMappedException;
use App\Models\Hotel;
use App\Models\Restaurant;

/**
 * Recomenda restaurantes a partir de um hotel, ordenados pela menor
 * distância percorrendo as ruas do mapa (Dijkstra).
 */
class RestaurantRecommendationService
{
    public function __construct(private readonly DijkstraService $dijkstra)
    {
    }

    /**
     * @param  array{cuisine?:string, max_distance_km?:float|int|string, min_rating?:float|int|string}  $filters
     * @return list<array{restaurant: Restaurant, distance_km: float, route: list<int>}>
     *
     * @throws HotelNotMappedException
     */
    public function recommendFor(Hotel $hotel, array $filters = []): array
    {
        if ($hotel->city_node_id === null) {
            throw new HotelNotMappedException();
        }

        // Um único Dijkstra a partir do hotel já fornece a distância até
        // todos os restaurantes. O grafo é recarregado a cada chamada para
        // refletir ruas e restaurantes cadastrados depois da instanciação.
        $result = $this->dijkstra->loadFromDatabase()->shortestPathsFrom($hotel->city_node_id);

        return Restaurant::query()
            ->with('cityNode')
            ->when($filters['cuisine'] ?? null, fn ($q, $cuisine) => $q->where('cuisine', $cuisine))
            ->when($filters['min_rating'] ?? null, fn ($q, $rating) => $q->where('rating', '>=', $rating))
            ->get()
            ->map(function (Restaurant $restaurant) use ($result) {
                $distance = $result['distances'][$restaurant->city_node_id] ?? null;

                return [
                    'restaurant' => $restaurant,
                    'distance_km' => $distance !== null ? round($distance, 2) : null,
                    'route' => $distance !== null
                        ? $this->dijkstra->buildPath($result['previous'], $restaurant->city_node_id)
                        : [],
                ];
            })
            // Restaurantes sem rota a partir do hotel não são recomendados.
            ->filter(fn (array $r) => $r['distance_km'] !== null)
            ->when(
                isset($filters['max_distance_km']),
                fn ($collection) => $collection->filter(fn (array $r) => $r['distance_km'] <= $filters['max_distance_km'])
            )
            ->sortBy('distance_km')
            ->values()
            ->all();
    }
}
