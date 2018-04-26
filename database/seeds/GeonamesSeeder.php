<?php

use Illuminate\Database\Seeder;

class GeonamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('geonames')->insert([
            'geonameid' => 8261389,
            'name' => 'Rinconada',
            'feature_code' => 'ADM3',
            'country_code' => 'CL',
            'admin1_code' => '01',
            'admin2_code' => '53',
            'admin3_code' => '05303',
        ]);
        DB::table('geonames')->insert([
            'geonameid' => 3882432,
            'name' => 'Provincia de Los Andes',
            'feature_code' => 'ADM2',
            'country_code' => 'CL',
            'admin1_code' => '01',
            'admin2_code' => '53',
        ]);
        DB::table('geonames')->insert([
            'geonameid' => 3868621,
            'name' => 'Valparaíso',
            'feature_code' => 'ADM1',
            'country_code' => 'CL',
            'admin1_code' => '01',
        ]);
    }
}
