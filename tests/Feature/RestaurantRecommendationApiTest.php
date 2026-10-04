<?php

namespace Tests\Feature;

use Database\Seeders\CityGraphSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantRecommendationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // O mapa vincula hotéis já existentes, então eles são importados primeiro.
        $this->postJson('/api/import/hotels')->assertOk();
        $this->seed(CityGraphSeeder::class);
    }

    public function test_it_recommends_restaurants_sorted_by_distance_from_the_hotel(): void
    {
        $response = $this->getJson('/api/hotels/1/restaurants/recommendations');

        $response->assertOk();
        $names = collect($response->json())->pluck('restaurant.name');

        // A partir do hotel 1, o mais próximo no mapa de exemplo é o Sabor Baiano
        // (hotel -> Praça Central -> restaurante = 1,9 km).
        $this->assertEquals('Restaurante Sabor Baiano', $names->first());

        // A lista deve estar em ordem crescente de distância.
        $distances = collect($response->json())->pluck('distance_km');
        $sorted = $distances->sort()->values();
        $this->assertEquals($sorted->all(), $distances->all());
    }

    public function test_it_filters_recommendations_by_cuisine(): void
    {
        $response = $this->getJson('/api/hotels/1/restaurants/recommendations?cuisine=italiana');

        $response->assertOk();
        $names = collect($response->json())->pluck('restaurant.cuisine')->unique();

        $this->assertEquals(['italiana'], $names->all());
    }

    public function test_it_filters_recommendations_by_max_distance(): void
    {
        $response = $this->getJson('/api/hotels/1/restaurants/recommendations?max_distance_km=2');

        $response->assertOk();

        foreach ($response->json() as $recommendation) {
            $this->assertLessThanOrEqual(2, $recommendation['distance_km']);
        }
    }

    public function test_it_fails_gracefully_when_hotel_has_no_city_node(): void
    {
        // Hotel sem ponto no mapa.
        \App\Models\Hotel::create(['id' => 999, 'name' => 'Hotel Sem Mapa']);

        $response = $this->getJson('/api/hotels/999/restaurants/recommendations');

        $response->assertStatus(422)->assertJsonStructure(['message']);
    }
}
