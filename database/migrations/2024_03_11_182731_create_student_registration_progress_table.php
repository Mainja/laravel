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
        Schema::create('student_registration_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')
            ->constrained('student_payments')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            // $table->foreignId('semester_id')
            // ->constrained('training_semesters')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('program_id')
            // ->constrained('programs')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('office_id')
            // ->constrained('offices')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->integer('step');
            $table->foreignId('step_id')
            ->constrained('registration_steps')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->enum('status', ['pending', 'active', 'confirmed']);
            $table->date('date_confirmed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_registration_progress');
    }
};
