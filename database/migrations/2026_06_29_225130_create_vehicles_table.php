<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->year('year');
            $table->integer('mileage');
            $table->decimal('price', 12, 2);
            $table->enum('fuel_type', ['Essence', 'Diesel', 'Électrique', 'Hybride']);
            $table->enum('transmission', ['Manuelle', 'Automatique']);
            $table->enum('condition', ['Neuf', 'Occasion']);
            $table->enum('status', ['Disponible', 'Réservé', 'Vendu'])->default('Disponible');
            $table->text('description');
            $table->string('color', 50);
            $table->string('engine', 50)->nullable();
            $table->tinyInteger('doors');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
