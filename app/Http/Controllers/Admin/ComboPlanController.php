<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComboPlan;
use App\Models\Specialization;
use Illuminate\Http\Request;

class ComboPlanController extends Controller
{
    public function index()
    {
        $plans = ComboPlan::orderBy('id')->get();
        $specializations = Specialization::orderBy('name')->get();

        return view('admin.combo-plans.index', [
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
            'coverage' => 'required|numeric|min:0.01',
            'yearly_amount' => 'required|numeric|min:1',
        ]);

        ComboPlan::create([
            'specializations' => $validated['specializations'],
            'coverage_lakh' => $validated['coverage'],
            'yearly_amount' => $validated['yearly_amount'],
        ]);

        return redirect()->route('admin.combo-plans')->with('success', 'Combo plan added successfully.');
    }

    public function update(Request $request, ComboPlan $comboPlan)
    {
        $validated = $request->validate([
            'specializations' => 'required|array|min:1',
            'specializations.*' => 'string',
            'coverage' => 'required|numeric|min:0.01',
            'yearly_amount' => 'required|numeric|min:1',
        ]);

        $comboPlan->update([
            'specializations' => $validated['specializations'],
            'coverage_lakh' => $validated['coverage'],
            'yearly_amount' => $validated['yearly_amount'],
        ]);

        return redirect()->route('admin.combo-plans')->with('success', 'Combo plan updated successfully.');
    }

    public function destroy(ComboPlan $comboPlan)
    {
        $comboPlan->delete();

        return redirect()->route('admin.combo-plans')->with('success', 'Combo plan deleted successfully.');
    }
}
