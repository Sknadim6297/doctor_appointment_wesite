<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NormalPlan;
use Illuminate\Http\Request;

class NormalPlanController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $plans = NormalPlan::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('coverage_lakh', 'like', '%' . $search . '%')
                    ->orWhere('yearly_amount', 'like', '%' . $search . '%');
            })
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.plans.index', [
            'plans' => $plans,
            'totalPlans' => $plans->total(),
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'coverage' => 'required|numeric|min:0.01',
            'yearly_amount' => 'required|numeric|min:1',
        ]);

        NormalPlan::create([
            'coverage_lakh' => $validated['coverage'],
            'yearly_amount' => $validated['yearly_amount'],
        ]);

        return redirect()->route('admin.plans')->with('success', 'Normal plan added successfully.');
    }

    public function update(Request $request, NormalPlan $plan)
    {
        $validated = $request->validate([
            'coverage' => 'required|numeric|min:0.01',
            'yearly_amount' => 'required|numeric|min:1',
        ]);

        $plan->update([
            'coverage_lakh' => $validated['coverage'],
            'yearly_amount' => $validated['yearly_amount'],
        ]);

        return redirect()->route('admin.plans')->with('success', 'Normal plan updated successfully.');
    }

    public function destroy(NormalPlan $plan)
    {
        $plan->delete();

        return redirect()->route('admin.plans')->with('success', 'Normal plan deleted successfully.');
    }
}
