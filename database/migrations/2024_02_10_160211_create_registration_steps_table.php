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
        Schema::create('registration_steps', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('year_id')
            // ->constrained('training_years')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('semester_id')
            // ->constrained('training_semesters')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            $table->foreignId('program_id')
            ->constrained('programs')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('office_id')
            ->constrained('offices')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->integer('step');
            $table->enum('student_category', ['new_students', 'transin_students', 'returning_students']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_steps');
    }
};
