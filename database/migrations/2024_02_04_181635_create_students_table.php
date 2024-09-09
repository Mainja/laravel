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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intake_id')
            ->constrained('intakes')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('country_id')
            ->constrained('countries')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            // $table->foreignId('state_id')
            // ->constrained('states')
            // ->onUpdate('cascade')
            // ->onDelete('cascade');
            $table->foreignId('program_id')
            ->constrained('programs')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('author')
            ->constrained('admins')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            // $table->integer('positional_index');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('computer_number');
            $table->string('index_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nrc_number')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('phone_number')->nullable();
            $table->string('sponsor_name')->nullable();
            $table->string('sponsor_relation')->nullable();
            $table->string('sponsor_phone_number')->nullable();
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_relation')->nullable();
            $table->string('next_of_kin_phone_number')->nullable();
            $table->string('physical_address')->nullable();
            $table->timestamps();
        });
        // DB::statement('ALTER TABLE students CHANGE positional_index positional_index INT(4) UNSIGNED ZEROFILL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
