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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->unique()->constrained()->onDelete('cascade');
            // L'agent qui empoche la commission
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('buyer_name');
            $table->bigInteger('final_price'); // Prix final négocié en FCFA
            $table->date('sale_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
