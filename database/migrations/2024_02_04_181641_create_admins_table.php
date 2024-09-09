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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')
            ->constrained('offices')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone_number');
            $table->string('password');
            $table->date('date_of_birth')->nullable();
            $table->string('nrc_number')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->date('joining_date')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
