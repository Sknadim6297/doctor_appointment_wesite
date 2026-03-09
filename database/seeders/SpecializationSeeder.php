<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            'Consultant Physician (General Medicine)(PL-B- SPL)',
            'Chest Physician (PL-B- SPL)',
            'Cardiologist',
            'Dentist (PL-CA)',
            'Dermatologist Skin Specialist (PL-B- SPL)',
            'Ent Specialist(PL-B- SPL)',
            'Gynecologist and Obstetrician(PL-CA SURG)',
            'Hematologists (PLAN-BA SPL)',
            'Internal Medicine Specialist (PL-B- SPL)',
            'Neurologist (PLAN-BA SPL)',
            'NEUROSURGEON PL-CA',
            'Pathologists (PLAN-BA SPL)',
            'Plastic Surgeon (PLAN-DA)',
            'Rheumatologist (PL-BA SPL)',
            'Sonologist (PL-BA SPL)',
            'ENT Surgeon PL-CA',
            'Oral Maxillofacial Surgeon (PLAN-DA)',
            'Eye Surgeon (Ophthalmologist) (PLAN-CA)',
            'Gastroenterologists (PLAN-BA)',
        ];

        foreach ($records as $name) {
            Specialization::firstOrCreate(['name' => $name]);
        }
    }
}
