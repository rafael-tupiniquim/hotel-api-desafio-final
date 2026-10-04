<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            // Código numérico do método de pagamento, como vem no XML.
            $table->id();
            $table->unsignedBigInteger('reserve_id');
            $table->unsignedInteger('method');
            $table->decimal('value', 10, 2);
            $table->timestamps();

            $table->foreign('reserve_id')->references('id')->on('reserves')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
