<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            // Ponto do mapa que serve de origem das rotas até os restaurantes.
            $table->foreignId('city_node_id')->nullable()->after('name')
                ->constrained('city_nodes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_node_id');
        });
    }
};
