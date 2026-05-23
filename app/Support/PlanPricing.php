<?php

namespace App\Support;

use App\Models\ComboPlan;
use App\Models\HighRiskPlan;
use App\Models\InsurancePlan;
use App\Models\NormalPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlanPricing
{
    /**
     * Resolve premium for a payment mode using legacy tier columns when present.
     */
    public static function amountForPaymentMode(NormalPlan|HighRiskPlan|ComboPlan|InsurancePlan $plan, string $paymentMode): float
    {
        $amount = match ($paymentMode) {
            'Two Year' => $plan->two_year_amount,
            'Three Year' => $plan->three_year_amount,
            'Four Year' => $plan->four_year_amount,
            'Five Year' => $plan->five_year_amount,
            default => $plan->yearly_amount,
        };

        if ($amount !== null && (float) $amount > 0) {
            return round((float) $amount, 2);
        }

        $multiplier = match ($paymentMode) {
            'Two Year' => 2,
            'Three Year' => 3,
            'Four Year' => 4,
            'Five Year' => 5,
            default => 1,
        };

        return round(self::baseYearlyAmount($plan) * $multiplier, 2);
    }

    private static function baseYearlyAmount(NormalPlan|HighRiskPlan|ComboPlan|InsurancePlan $plan): float
    {
        if ($plan instanceof InsurancePlan) {
            return (float) ($plan->yearly_amount ?? $plan->amount_per_lakh ?? 0);
        }

        return (float) $plan->yearly_amount;
    }

    public static function comboPlanMatchesSpecialization(ComboPlan $plan, int $specializationId = 0, ?string $specializationName = null): bool
    {
        if ($specializationId > 0 && Schema::hasTable('combo_plan_specialization')) {
            $linked = DB::table('combo_plan_specialization')
                ->where('combo_plan_id', $plan->id)
                ->where('specialization_id', $specializationId)
                ->exists();

            if ($linked) {
                return true;
            }
        }

        $labels = $plan->specializations;

        if (!is_array($labels) || $labels === []) {
            return $specializationId <= 0;
        }

        if ($specializationId > 0 && self::arrayContainsNumericId($labels, $specializationId)) {
            return true;
        }

        if ($specializationName === null || trim($specializationName) === '') {
            return $specializationId <= 0;
        }

        return self::labelsMatchSpecializationName($labels, $specializationName);
    }

    /**
     * @param  array<int, mixed>  $labels
     */
    private static function arrayContainsNumericId(array $labels, int $specializationId): bool
    {
        foreach ($labels as $label) {
            if (is_numeric($label) && (int) $label === $specializationId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $labels
     */
    private static function labelsMatchSpecializationName(array $labels, string $specializationName): bool
    {
        $needle = mb_strtolower(trim($specializationName));

        foreach ($labels as $label) {
            if (is_numeric($label)) {
                continue;
            }

            $label = mb_strtolower(trim((string) $label));

            if ($label === '') {
                continue;
            }

            if (str_contains($label, $needle) || str_contains($needle, $label)) {
                return true;
            }

            $shortLabel = trim((string) preg_split('/\s*\(/', $label, 2)[0]);

            if ($shortLabel !== '' && (str_contains($needle, $shortLabel) || str_contains($shortLabel, $needle))) {
                return true;
            }
        }

        return false;
    }
}
