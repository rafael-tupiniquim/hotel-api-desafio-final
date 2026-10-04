<?php

namespace Tests\Feature;

use App\Models\CityNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityGraphManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_city_node(): void
    {
        $response = $this->postJson('/api/city-graph/nodes', [
            'name' => 'Rua Nova',
            'type' => 'intersection',
        ]);

        $response->assertCreated()->assertJsonPath('name', 'Rua Nova');
        $this->assertDatabaseHas('city_nodes', ['name' => 'Rua Nova', 'type' => 'intersection']);
    }

    public function test_it_creates_an_edge_connecting_two_nodes(): void
    {
        $a = CityNode::create(['name' => 'Ponto A', 'type' => 'intersection']);
        $b = CityNode::create(['name' => 'Ponto B', 'type' => 'intersection']);

        $response = $this->postJson('/api/city-graph/edges', [
            'from_node_id' => $a->id,
            'to_node_id' => $b->id,
            'distance_km' => 1.5,
            'bidirectional' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('city_edges', ['from_node_id' => $a->id, 'to_node_id' => $b->id, 'distance_km' => 1.5]);
    }

    public function test_it_creates_a_restaurant_with_a_new_city_node_in_one_call(): void
    {
        $response = $this->postJson('/api/restaurants', [
            'node_name' => 'Restaurante Novo',
            'name' => 'Restaurante Novo',
            'cuisine' => 'brasileira',
            'rating' => 4.2,
            'price_range' => '$$',
        ]);

        $response->assertCreated()->assertJsonPath('name', 'Restaurante Novo');
        $this->assertDatabaseHas('city_nodes', ['name' => 'Restaurante Novo', 'type' => 'restaurant']);
        $this->assertDatabaseHas('restaurants', ['name' => 'Restaurante Novo', 'cuisine' => 'brasileira']);
    }

    public function test_it_creates_a_restaurant_linked_to_an_existing_node(): void
    {
        $node = CityNode::create(['name' => 'Ponto Existente', 'type' => 'restaurant']);

        $response = $this->postJson('/api/restaurants', [
            'city_node_id' => $node->id,
            'name' => 'Restaurante Ligado',
            'cuisine' => 'japonesa',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('restaurants', ['city_node_id' => $node->id, 'name' => 'Restaurante Ligado']);
        // Não deve ter criado um segundo CityNode.
        $this->assertDatabaseCount('city_nodes', 1);
    }

    public function test_a_newly_created_restaurant_appears_in_recommendations_once_connected(): void
    {
        $this->postJson('/api/import/hotels')->assertOk();
        $hotelNode = CityNode::create(['name' => 'Hotel Foco Prime', 'type' => 'hotel']);
        \App\Models\Hotel::whereKey(1)->update(['city_node_id' => $hotelNode->id]);

        $restaurant = $this->postJson('/api/restaurants', [
            'node_name' => 'Cantinho Novo',
            'name' => 'Cantinho Novo',
            'cuisine' => 'vegetariana',
        ])->json();

        // Sem nenhuma rua ligada a ele, o restaurante não é alcançável.
        $before = $this->getJson('/api/hotels/1/restaurants/recommendations')->json();
        $this->assertEmpty(collect($before)->where('restaurant.name', 'Cantinho Novo'));

        $this->postJson('/api/city-graph/edges', [
            'from_node_id' => $hotelNode->id,
            'to_node_id' => $restaurant['city_node_id'],
            'distance_km' => 0.5,
        ])->assertCreated();

        $after = $this->getJson('/api/hotels/1/restaurants/recommendations')->json();
        $this->assertNotEmpty(collect($after)->where('restaurant.name', 'Cantinho Novo'));
    }
}
