<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsurancePlan;
use App\Models\Specialization;
use Illuminate\Http\Request;

class InsurancePlanController extends Controller
{
    public function index()
    {
        $plans = InsurancePlan::orderBy('id')->get();
        $specializations = Specialization::orderBy('name')->get();

        return view('admin.insurance-plans.index', [
            'plans' => $plans,
            'totalPlans' => $plans->count(),
            'specializations' => $specializations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'specializations' => 'required|array|min:1',
            'specializations.*' => 'string',
            'amount' => 'required|numeric|min:0.01',
            'service_tax' => 'required|numeric|min:0|max:100',
        ]);

        InsurancePlan::create([
            'specializations' => $validated['specializations'],
            'amount_per_lakh' => $validated['amount'],
            'service_tax_percent' => $validated['service_tax'],
        ]);

        return redirect()->route('admin.insurance-plans')->with('success', 'Insurance plan added successfully.');
    }

    public function update(Request $request, InsurancePlan $insurancePlan)
    {
        $validated = $request->validate([
            'specializations' => 'required|array|min:1',
            'specializations.*' => 'string',
            'amount' => 'required|numeric|min:0.01',
            'service_tax' => 'required|numeric|min:0|max:100',
        ]);

        $insurancePlan->update([
            'specializations' => $validated['specializations'],
            'amount_per_lakh' => $validated['amount'],
            'service_tax_percent' => $validated['service_tax'],
        ]);

        return redirect()->route('admin.insurance-plans')->with('success', 'Insurance plan updated successfully.');
    }

    public function destroy(InsurancePlan $insurancePlan)
    {
        $insurancePlan->delete();

        return redirect()->route('admin.insurance-plans')->with('success', 'Insurance plan deleted successfully.');
    }
}
