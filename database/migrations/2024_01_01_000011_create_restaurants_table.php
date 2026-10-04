<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_node_id')->constrained('city_nodes')->cascadeOnDelete();
            $table->string('name');
            $table->string('cuisine');
            $table->decimal('rating', 2, 1)->default(0);
            $table->string('price_range', 3)->default('$$'); // $, $$, $$$
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
