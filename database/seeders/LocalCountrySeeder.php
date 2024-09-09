<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalCountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // get zambia
        $local_country_id = DB::table("countries")->where("short_name","ZM")->value("id");

        DB::table('local_country')
        ->updateOrInsert(
            ['country_id' => $local_country_id],
            ['country_id' => $local_country_id]
        );
    }
}
