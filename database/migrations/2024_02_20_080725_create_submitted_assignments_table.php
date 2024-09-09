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
        Schema::create('submitted_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
            ->constrained('students')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('assignment_id')
            ->constrained('assignments')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->date('date_submitted');
            $table->time('time_submitted');
            $table->string('students_remarks')->nullable();
            $table->string('lecturer_remarks')->nullable();
            $table->string('file_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submitted_assignments');
    }
};
