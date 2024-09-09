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
        Schema::create('penalty_clearance_process', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penalty_id')
            ->constrained('student_penalties')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('office_id')
            ->constrained('offices')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->integer('step');
            $table->text('comment')->nullable();
            $table->enum('status', ['pending', 'active', 'completed']);
            $table->date('date_completed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalty_clearance_process');
    }
};
