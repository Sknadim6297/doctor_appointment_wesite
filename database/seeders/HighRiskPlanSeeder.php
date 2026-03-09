<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HighRiskPlan;

class HighRiskPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'coverage_lakh' => 1,
                'yearly_amount' => 5000,
            ],
            [
                'coverage_lakh' => 2,
                'yearly_amount' => 8000,
            ],
        ];

        foreach ($plans as $plan) {
            HighRiskPlan::create($plan);
        }
    }
}
