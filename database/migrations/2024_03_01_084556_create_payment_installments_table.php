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
        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_fee_id')
            ->constrained('program_fees')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('level_id')
            ->constrained('level_semester_fees')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('author')
            ->constrained('admins')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->integer('installment_number');
            $table->decimal('amount_local', 12, 2);
            $table->decimal('amount_foreign', 12, 2);
            $table->date('date_of_payment');
            $table->decimal('expected_paid_local_amount', 12, 2);
            $table->decimal('expected_paid_foreign_amount', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_installments');
    }
};
