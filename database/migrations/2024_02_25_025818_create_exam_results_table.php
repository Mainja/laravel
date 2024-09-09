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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')
            ->constrained('results')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('student_id')
            ->constrained('students')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('author')
            ->constrained('admins')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('course_id')
            ->constrained('courses')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->enum('exam_type', ['first_attempt', 'resit']);
            $table->decimal('end_of_course', 10,1)->nullable();
            $table->decimal('end_of_course_percent', 10,1)->nullable();
            $table->decimal('final_mark', 10,1)->nullable();
            $table->string('grade')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
