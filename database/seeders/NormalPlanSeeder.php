<?php

namespace Database\Seeders;

use App\Models\NormalPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NormalPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            ['coverage_lakh' => 3, 'yearly_amount' => 9000],
            ['coverage_lakh' => 3, 'yearly_amount' => 6000],
            ['coverage_lakh' => 1, 'yearly_amount' => 2000],
            ['coverage_lakh' => 1, 'yearly_amount' => 2000],
            ['coverage_lakh' => 1, 'yearly_amount' => 1500],
            ['coverage_lakh' => 2, 'yearly_amount' => 6000],
            ['coverage_lakh' => 1, 'yearly_amount' => 3000],
            ['coverage_lakh' => 1, 'yearly_amount' => 1500],
            ['coverage_lakh' => 1, 'yearly_amount' => 3540],
        ];

        foreach ($plans as $plan) {
            NormalPlan::firstOrCreate($plan);
        }
    }
}
