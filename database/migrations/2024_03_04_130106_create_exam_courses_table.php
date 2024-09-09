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
        Schema::create('exam_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_student_id')
            ->constrained('exam_student')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('course_id')
            ->constrained('courses')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_courses');
    }
};
