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
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author')
            ->constrained('admins')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('item_id')
            ->constrained('inventory_items')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('from_office')
            ->constrained('offices')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('to_office')
            ->constrained('offices')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->integer('quantity');
            $table->date('date_transferred');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
