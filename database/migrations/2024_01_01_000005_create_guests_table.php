<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserve_id');
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->foreign('reserve_id')->references('id')->on('reserves')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
