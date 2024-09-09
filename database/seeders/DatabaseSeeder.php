<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $role = DB::table("roles")->where('role', 'master')->first();

        $master_office = DB::table('offices')->insertGetId([
            'office_name' => 'computer lab',
            'number_of_occupants' => 1
        ]);

        $admin = DB::table('admins')->insertGetId([
            'office_id' => $master_office,
            'name' => 'Mainja Mbunge',
            'email' => 'mainjambunge117@gmail.com',
            'password' => Hash::make('Thep1$$word'),
            'gender' => 'male',
            'phone_number' => '0974049247'
        ]);

        DB::table('admin_roles')->insert([
            'role_id' => $role->id,
            'admin_id' => $admin
        ]);
        // DB::table('students')->insert([
        //     'intake_id' => 1,
        //     'program_id' => 1,
        //     'positional_index' => 1,
        //     'name' => 'john doe',
        //     'email' => 'doe@gmail.com',
        //     'password' => Hash::make('password12345'),
        //     'computer_number' => '23010001',
        //     'gender' => 'male',
        // ]);
    }
}
