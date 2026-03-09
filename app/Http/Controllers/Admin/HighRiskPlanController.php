<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HighRiskPlan;
use Illuminate\Http\Request;

class HighRiskPlanController extends Controller
{
    public function index()
    {
        $plans = HighRiskPlan::orderBy('id')->get();

        return view('admin.high-risk-plans.index', [
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

        HighRiskPlan::create([
            'coverage_lakh' => $validated['coverage'],
            'yearly_amount' => $validated['yearly_amount'],
        ]);

        return redirect()->route('admin.high-risk-plans')->with('success', 'High risk plan added successfully.');
    }

    public function update(Request $request, HighRiskPlan $highRiskPlan)
    {
        $validated = $request->validate([
            'coverage' => 'required|numeric|min:0.01',
            'yearly_amount' => 'required|numeric|min:1',
        ]);

        $highRiskPlan->update([
            'coverage_lakh' => $validated['coverage'],
            'yearly_amount' => $validated['yearly_amount'],
        ]);

        return redirect()->route('admin.high-risk-plans')->with('success', 'High risk plan updated successfully.');
    }

    public function destroy(HighRiskPlan $highRiskPlan)
    {
        $highRiskPlan->delete();

        return redirect()->route('admin.high-risk-plans')->with('success', 'High risk plan deleted successfully.');
    }
}
