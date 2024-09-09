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
        Schema::create('student_submitted_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author')
            ->constrained('admins')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('item_id')
            ->constrained('inventory_items')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('office_id')
            ->constrained('offices')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('registration_id')
            ->constrained('student_payments')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            // $table->foreignId('student_id')
            // ->constrained('students')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('year_id')
            // ->constrained('training_years')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('program_id')
            // ->constrained('programs')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('semester_id')
            // ->constrained('training_semesters')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            $table->date('date_submitted');
            $table->integer('expected_quantity');
            $table->integer('submitted');
            $table->integer('balance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_submitted_inventory');
    }
};
