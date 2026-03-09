<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ComboPlan;

class ComboPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'specializations' => [
                    'Dentist (PLAN-C200 SURG)',
                    'Gynecologist and Obstetrician(PLAN-C200 SURG)',
                    'Neurosurgeon (PLAN-C200 SURG)',
                    'Ent Surgeon(PLAN-C200 SURG)',
                    'Eye Surgeon (Ophthalmologist) (PLAN-C200 SURG)',
                    'Orthopedic Surgeon (PLAN-C200 SURG)',
                    'Cardiovascular Surgeon (PLAN-C200 SURG)',
                ],
                'coverage_lakh' => 5,
                'yearly_amount' => 3900,
            ],
            [
                'specializations' => [
                    'Dentist (PLAN-C200 SURG)',
                    'Gynecologist and Obstetrician(PLAN-C200 SURG)',
                    'Neurosurgeon (PLAN-C200 SURG)',
                    'Ent Surgeon(PLAN-C200 SURG)',
                    'Eye Surgeon (Ophthalmologist) (PLAN-C200 SURG)',
                    'Orthopedic Surgeon (PLAN-C200 SURG)',
                    'General Surgeon (PLAN-C200 SURG)',
                ],
                'coverage_lakh' => 10,
                'yearly_amount' => 6500,
            ],
        ];

        foreach ($plans as $plan) {
            ComboPlan::create($plan);
        }
    }
}
