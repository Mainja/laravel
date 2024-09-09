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
        Schema::create('result_details', function (Blueprint $table) {
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
            $table->decimal('assignment_1', 10,1)->nullable();
            $table->decimal('assignment_1_percent', 10,1)->nullable();
            $table->decimal('test_1', 10,1)->nullable();
            $table->decimal('test_1_percent', 10,1)->nullable();
            $table->decimal('assignment_2', 10,1)->nullable();
            $table->decimal('assignment_2_percent', 10,1)->nullable();
            $table->decimal('test_2', 10,1)->nullable();
            $table->decimal('test_2_percent', 10,1)->nullable();
            $table->decimal('ca', 10,1)->nullable()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_details');
    }
};
