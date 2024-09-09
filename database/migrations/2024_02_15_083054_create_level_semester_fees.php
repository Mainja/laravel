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
        Schema::create('level_semester_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_fee_id')
            ->constrained('program_fees')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('author')
            ->constrained('admins')
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
            $table->decimal('local_student_tuition', 12, 2);
            $table->decimal('foreign_student_tuition', 12, 2);
            $table->decimal('local_reporting_payment', 12, 2);
            $table->decimal('foreign_reporting_payment', 12, 2);
            $table->decimal('exam_fee', 12, 2);
            $table->decimal('other_requirements', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('level_semester_fees');
    }
};
