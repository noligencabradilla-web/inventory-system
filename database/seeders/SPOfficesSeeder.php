<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SPOfficesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offices = [
            "secretary to the sangguniang",
            "board secretary ",
            "Legislative Tracking and Monitoring Unit",
            "Journal and Documentation Unit",
            "Legislative Research and Policy Unit",
            "Board Secretariat Administrative Unit",
            "Hon. Gerard G. Ostrea",
            "Hon. Alyssa Kristine B. Sibuma",
            "Hon. Maria Rosario Eufrosina P. Nisce",
            "Office of the Vice Governor",
            "Hon. Ruperto A. Rillera Jr.",
            "Hon. Aaron Kyle M. Pinzon",
            "Hon. Harold Dave E. Sibuma",
            "Hon. Joyce M. Abuan",
            "Hon. Ramon Guio A. Ortega, JR.",
            "Hon. Teresita â€œTessâ€ O. Garcia",
            "Hon. Ernesto V. Rafon",
            "Hon. Miguel Corleone B. Magsaysay",
            "Hon. Jeferson B. Fernando",
            "Hon. Eulogio Clarence Martin P. De Guzman III",
            "Hon. Eric O. Sibuma",
            "LEGISLATIVE RESOURCE AND INFORMATION CENTER",
            "Hon. Danielle Bianca G. Ortega",
        ];

        foreach ($offices as $office) {
            \App\Models\SPOffices::create([
                'office' => $office,
                'is_active' => true,
            ]);
        }
    }
}
