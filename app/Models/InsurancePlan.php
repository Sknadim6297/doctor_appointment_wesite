<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsurancePlan extends Model
{
    protected $fillable = [
        'specializations',
        'amount_per_lakh',
        'service_tax_percent',
        'yearly_amount',
        'two_year_amount',
        'three_year_amount',
        'four_year_amount',
        'five_year_amount',
    ];

    protected $casts = [
        'specializations' => 'array',
    ];

    /**
     * @return array<int, int>
     */
    public function specializationIds(): array
    {
        $items = $this->specializations ?? [];

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static function (mixed $item): ?int {
            if (is_numeric($item)) {
                $id = (int) $item;

                return $id > 0 ? $id : null;
            }

            return null;
        }, $items))));
    }

    /**
     * Human-readable specialization labels for admin lists.
     *
     * @return array<int, string>
     */
    public function resolveSpecializationLabels(): array
    {
        $items = $this->specializations ?? [];

        if (!is_array($items) || $items === []) {
            return [];
        }

        $ids = $this->specializationIds();
        $labels = [];

        if ($ids !== []) {
            $namesById = Specialization::query()
                ->whereIn('id', $ids)
                ->orderBy('name')
                ->pluck('name', 'id');

            foreach ($ids as $id) {
                $name = $namesById->get($id);

                if ($name !== null) {
                    $labels[] = $name;
                }
            }
        }

        foreach ($items as $item) {
            if (!is_numeric($item) && trim((string) $item) !== '') {
                $labels[] = trim((string) $item);
            }
        }

        return $labels;
    }

    public function specializationLabelsText(): string
    {
        return implode(', ', $this->resolveSpecializationLabels());
    }
}
