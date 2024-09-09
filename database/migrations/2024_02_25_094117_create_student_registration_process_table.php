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
        Schema::create('student_registration_process', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
            ->constrained('students')
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
            $table->foreignId('step_id')
            ->constrained('registration_steps')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('office_id')
            ->constrained('offices')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->text('comment')->nullable();
            $table->enum('status', ['progress', 'completed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_registration_process');
    }
};
