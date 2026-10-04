<?php

namespace Database\Seeders;

use App\Models\CityEdge;
use App\Models\CityNode;
use App\Models\Hotel;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

/**
 * Mapa de exemplo: liga os 3 hotéis importados dos XMLs a 5 restaurantes por
 * ruas de distâncias variadas, de forma que o caminho mais curto às vezes
 * passa por mais de uma rua. Requer os hotéis já importados.
 *
 * php artisan db:seed --class=CityGraphSeeder (também executado pelo DatabaseSeeder)
 */
class CityGraphSeeder extends Seeder
{
    public function run(): void
    {
        // Pontos do mapa (nós do grafo)
        $nodes = [
            1 => ['name' => 'Hotel Foco Prime', 'type' => 'hotel'],
            2 => ['name' => 'Hotel Foco Beach', 'type' => 'hotel'],
            3 => ['name' => 'Hotel Foco Privillege', 'type' => 'hotel'],
            4 => ['name' => 'Praça Central', 'type' => 'intersection'],
            5 => ['name' => 'Avenida Litoral', 'type' => 'intersection'],
            6 => ['name' => 'Rua do Comércio', 'type' => 'intersection'],
            7 => ['name' => 'Restaurante Sabor Baiano', 'type' => 'restaurant'],
            8 => ['name' => 'Restaurante Vista Mar', 'type' => 'restaurant'],
            9 => ['name' => 'Cantina Toscana', 'type' => 'restaurant'],
            10 => ['name' => 'Boteco do Porto', 'type' => 'restaurant'],
            11 => ['name' => 'Restaurante Oriental', 'type' => 'restaurant'],
        ];

        foreach ($nodes as $id => $node) {
            CityNode::updateOrCreate(['id' => $id], $node);
        }

        // Vincula cada hotel importado ao seu ponto no mapa
        Hotel::whereKey(1)->update(['city_node_id' => 1]);
        Hotel::whereKey(2)->update(['city_node_id' => 2]);
        Hotel::whereKey(3)->update(['city_node_id' => 3]);

        // Ruas de mão dupla: [origem, destino, distância em km]
        $edges = [
            [1, 4, 1.2],
            [2, 5, 0.8],
            [3, 6, 1.5],
            [4, 5, 0.9],
            [4, 6, 0.6],
            [4, 7, 0.7],
            [5, 8, 0.4],
            [5, 6, 1.1],
            [6, 9, 0.5],
            [6, 10, 0.8],
            [7, 9, 0.3],
            [8, 11, 1.0],
            [9, 11, 0.6],
            [10, 11, 1.3],
        ];

        foreach ($edges as [$from, $to, $distance]) {
            CityEdge::updateOrCreate(
                ['from_node_id' => $from, 'to_node_id' => $to],
                ['distance_km' => $distance, 'bidirectional' => true]
            );
        }

        // Restaurantes, cada um associado a um ponto do tipo "restaurant"
        $restaurants = [
            ['city_node_id' => 7, 'name' => 'Restaurante Sabor Baiano', 'cuisine' => 'baiana', 'rating' => 4.7, 'price_range' => '$$'],
            ['city_node_id' => 8, 'name' => 'Restaurante Vista Mar', 'cuisine' => 'frutos do mar', 'rating' => 4.5, 'price_range' => '$$$'],
            ['city_node_id' => 9, 'name' => 'Cantina Toscana', 'cuisine' => 'italiana', 'rating' => 4.3, 'price_range' => '$$'],
            ['city_node_id' => 10, 'name' => 'Boteco do Porto', 'cuisine' => 'brasileira', 'rating' => 4.1, 'price_range' => '$'],
            ['city_node_id' => 11, 'name' => 'Restaurante Oriental', 'cuisine' => 'japonesa', 'rating' => 4.6, 'price_range' => '$$$'],
        ];

        foreach ($restaurants as $restaurant) {
            Restaurant::updateOrCreate(['city_node_id' => $restaurant['city_node_id']], $restaurant);
        }
    }
}
