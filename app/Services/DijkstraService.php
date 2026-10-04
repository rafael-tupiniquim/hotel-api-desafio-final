<?php

namespace App\Services;

use App\Models\CityEdge;
use SplPriorityQueue;

/**
 * Algoritmo de Dijkstra sobre o mapa da cidade (hotéis, restaurantes e
 * cruzamentos ligados por ruas com distância em km).
 *
 * Complexidade: O((V + E) log V), com fila de prioridade (heap).
 * A classe não depende do banco: o grafo pode ser montado com addEdge()
 * ou carregado de city_edges com loadFromDatabase().
 */
class DijkstraService
{
    /** @var array<int, list<array{to:int,distance:float}>> lista de adjacência */
    private array $adjacency = [];

    /**
     * Substitui o grafo atual pelo conteúdo de city_edges. Chamado a cada
     * cálculo para que ruas recém-cadastradas sejam consideradas, mesmo
     * com a instância reaproveitada entre requisições.
     */
    public function loadFromDatabase(): static
    {
        $this->adjacency = [];

        foreach (CityEdge::all() as $edge) {
            $from = (int) $edge->from_node_id;
            $to = (int) $edge->to_node_id;
            $distance = (float) $edge->distance_km;

            $this->addEdge($from, $to, $distance);

            if ($edge->bidirectional) {
                $this->addEdge($to, $from, $distance);
            }
        }

        return $this;
    }

    /** Adiciona uma aresta de mão única. Para ruas de mão dupla, adicione as duas direções. */
    public function addEdge(int $from, int $to, float $distance): static
    {
        $this->adjacency[$from][] = ['to' => $to, 'distance' => $distance];

        return $this;
    }

    /**
     * Menor distância da origem até todos os nós alcançáveis.
     *
     * @return array{distances: array<int,float>, previous: array<int,int|null>}
     */
    public function shortestPathsFrom(int $sourceNodeId): array
    {
        $distances = [$sourceNodeId => 0.0];
        $previous = [$sourceNodeId => null];
        $visited = [];

        // SplPriorityQueue extrai a MAIOR prioridade; usamos a distância
        // negativa para obter sempre o nó mais próximo (min-heap).
        $queue = new SplPriorityQueue();
        $queue->insert($sourceNodeId, 0);

        while (! $queue->isEmpty()) {
            $current = $queue->extract();

            // Um nó pode entrar na fila várias vezes (cada vez que uma
            // distância menor é encontrada); só a primeira extração conta.
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            foreach ($this->adjacency[$current] ?? [] as $edge) {
                $newDistance = $distances[$current] + $edge['distance'];

                if ($newDistance < ($distances[$edge['to']] ?? INF)) {
                    $distances[$edge['to']] = $newDistance;
                    $previous[$edge['to']] = $current;
                    $queue->insert($edge['to'], -$newDistance);
                }
            }
        }

        return ['distances' => $distances, 'previous' => $previous];
    }

    /**
     * Distância e caminho até um único destino.
     *
     * @return array{distance: float|null, path: list<int>} distance é null se inalcançável
     */
    public function shortestPathTo(int $sourceNodeId, int $targetNodeId): array
    {
        $result = $this->shortestPathsFrom($sourceNodeId);
        $distance = $result['distances'][$targetNodeId] ?? null;

        return [
            'distance' => $distance,
            'path' => $distance === null ? [] : $this->buildPath($result['previous'], $targetNodeId),
        ];
    }

    /**
     * Reconstrói a sequência de nós da origem até o destino a partir do
     * mapa de predecessores de shortestPathsFrom().
     *
     * @param  array<int,int|null>  $previous
     * @return list<int>
     */
    public function buildPath(array $previous, int $targetNodeId): array
    {
        $path = [];
        $current = $targetNodeId;

        while ($current !== null) {
            array_unshift($path, $current);
            $current = $previous[$current] ?? null;
        }

        return $path;
    }
}
