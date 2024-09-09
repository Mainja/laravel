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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')
            ->constrained('programs')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('intake_id')
            ->constrained('intakes')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('country_id')
            ->constrained('countries')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone_number');
            $table->enum('gender', ['male', 'female']);
            $table->string('address');
            $table->string('results');
            $table->string('nrc');
            $table->string('referral_code')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
