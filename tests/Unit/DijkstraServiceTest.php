<?php

namespace Tests\Unit;

use App\Services\DijkstraService;
use PHPUnit\Framework\TestCase;

/** Testa o algoritmo isoladamente, com grafos montados à mão (sem banco de dados). */
class DijkstraServiceTest extends TestCase
{
    public function test_finds_the_direct_shortest_path_when_it_is_the_best_option(): void
    {
        $dijkstra = new DijkstraService();
        $dijkstra->addEdge(1, 2, 5.0);

        $result = $dijkstra->shortestPathTo(1, 2);

        $this->assertEquals(5.0, $result['distance']);
        $this->assertEquals([1, 2], $result['path']);
    }

    public function test_prefers_a_longer_number_of_roads_when_the_total_distance_is_smaller(): void
    {
        // 1 --10km--> 2
        // 1 --2km-->  3 --2km--> 2   (o caminho por 3 é mais curto: 4 km)
        $dijkstra = new DijkstraService();
        $dijkstra->addEdge(1, 2, 10.0);
        $dijkstra->addEdge(1, 3, 2.0);
        $dijkstra->addEdge(3, 2, 2.0);

        $result = $dijkstra->shortestPathTo(1, 2);

        $this->assertEquals(4.0, $result['distance']);
        $this->assertEquals([1, 3, 2], $result['path']);
    }

    public function test_returns_null_distance_when_target_is_unreachable(): void
    {
        $dijkstra = new DijkstraService();
        $dijkstra->addEdge(1, 2, 5.0);
        // O nó 99 não está ligado a nenhum outro.

        $result = $dijkstra->shortestPathTo(1, 99);

        $this->assertNull($result['distance']);
        $this->assertEquals([], $result['path']);
    }

    public function test_shortest_paths_from_computes_distances_to_every_reachable_node_at_once(): void
    {
        $dijkstra = new DijkstraService();
        $dijkstra->addEdge(1, 2, 1.0);
        $dijkstra->addEdge(2, 3, 1.0);
        $dijkstra->addEdge(1, 3, 5.0); // rota direta, pior que 1->2->3

        $result = $dijkstra->shortestPathsFrom(1);

        $this->assertEquals(0.0, $result['distances'][1]);
        $this->assertEquals(1.0, $result['distances'][2]);
        $this->assertEquals(2.0, $result['distances'][3]); // passa pelo nó 2, não pela rota direta
    }

    public function test_does_not_traverse_edges_in_the_wrong_direction_when_one_way(): void
    {
        $dijkstra = new DijkstraService();
        // addEdge() cria uma rua de mão única; a ida não implica a volta.
        $dijkstra->addEdge(1, 2, 3.0);

        $result = $dijkstra->shortestPathTo(2, 1);

        $this->assertNull($result['distance']);
    }
}
