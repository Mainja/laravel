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
        Schema::create('registered_students', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('program_id')
            // ->constrained('programs')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('student_id')
            // ->constrained('students')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('year_id')
            // ->constrained('training_years')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('semester_id')
            // ->constrained('training_semesters')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            $table->foreignId('registration_id')
            ->constrained('student_payments')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->enum('status', ['pending', 'completed']);
            $table->enum('registration_type', ['pending', 'normal', 'late']);
            $table->date('date_registered')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registered_students');
    }
};
