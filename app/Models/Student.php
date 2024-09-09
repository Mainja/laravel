<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'intake_id',
        'program_id',
        'positional_index',
        'name',
        'email',
        'password',
        'computer_number',
        'index_number',
        'date_of_birth',
        'nrc_number',
        'gender',
        'phone_number',
        'sponsor_name',
        'sponsor_relation',
        'sponsor_phone_number',
        'physical_address',
        'next_of_kin_name',
        'next_of_kin_phone_number',
        'next_of_kin_relation',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $table = "students";
}
