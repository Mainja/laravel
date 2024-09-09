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
        Schema::create('exam_registration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')
            ->constrained('programs')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('intake_id')
            ->constrained('intakes')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('year_id')
            ->constrained('training_years')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('semester_id')
            ->constrained('training_semesters')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            // $table->integer('year_number');
            // $table->integer('semester_number');
            $table->enum('exam_mode', ['first_attempt', 'resit']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_registration');
    }
};
