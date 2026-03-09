<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NormalPlan;
use Illuminate\Http\Request;

class NormalPlanController extends Controller
{
    public function index()
    {
        $plans = NormalPlan::orderBy('id')->get();

        return view('admin.plans.index', [
            'plans' => $plans,
            'totalPlans' => $plans->count(),
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
