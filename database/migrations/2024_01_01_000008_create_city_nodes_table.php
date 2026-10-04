<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_nodes', function (Blueprint $table) {
            // Ponto do mapa da cidade: hotel, restaurante ou cruzamento.
            $table->id();
            $table->string('name');
            $table->enum('type', ['hotel', 'restaurant', 'intersection'])->default('intersection');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_nodes');
    }
};
