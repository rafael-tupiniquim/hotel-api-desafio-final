<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_edges', function (Blueprint $table) {
            // Rua entre dois pontos, com distância em km (aresta do grafo do Dijkstra).
            $table->id();
            $table->foreignId('from_node_id')->constrained('city_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('city_nodes')->cascadeOnDelete();
            $table->decimal('distance_km', 6, 2);
            $table->boolean('bidirectional')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_edges');
    }
};
