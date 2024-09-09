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
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
            ->constrained('students')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('opening_id')
            ->constrained('registration_openings')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            // $table->integer('year_number');
            // $table->integer('semester_number');
            $table->foreignId('year_id')
            ->constrained('training_years')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('semester_id')
            ->constrained('training_semesters')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('program_id')
            ->constrained('programs')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->decimal('amount_payable', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->decimal('percentage_paid', 12, 2);
            $table->date('date_paid');
            $table->enum('student_category', ['new_students', 'returning_students', 'transin_students']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};
