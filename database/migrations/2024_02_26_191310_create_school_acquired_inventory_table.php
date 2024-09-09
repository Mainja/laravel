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
        Schema::create('school_acquired_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author')
            ->constrained('admins')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('item_id')
            ->constrained('inventory_items')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('office_id')
            ->constrained('offices')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->date('date_submitted');
            $table->integer('quantity');
            $table->enum('source', ['acquisition', 'donations']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_acquired_inventory');
    }
};
