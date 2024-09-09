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
        Schema::create('payment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author')
            ->constrained('admins')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->string('invoice_number')->nullable();
            $table->foreignId('payment_id')
            ->constrained('student_payments')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->date('date_paid');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
