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
        Schema::create('registration_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('step_id')
            ->constrained('registration_steps')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('item_id')
            ->constrained('inventory_items')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_requirements');
    }
};
