<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dailies', function (Blueprint $table) {
            // Valor cobrado em cada diária da estadia.
            $table->id();
            $table->unsignedBigInteger('reserve_id');
            $table->date('date');
            $table->decimal('value', 10, 2);
            $table->timestamps();

            $table->foreign('reserve_id')->references('id')->on('reserves')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dailies');
    }
};
