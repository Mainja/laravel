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
        Schema::create('student_installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')
            ->constrained('student_payments')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            // $table->foreignId('year_id')
            // ->constrained('training_years')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->foreignId('semester_id')
            // ->constrained('training_semesters')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            // $table->integer('year_number');
            // $table->integer('semester_number');
            $table->integer('installment_number');
            $table->decimal('installment_amount', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->date('date_expected');
            $table->decimal('amount_expected', 12, 2);
            $table->decimal('carry_over', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_installment_payments');
    }
};
