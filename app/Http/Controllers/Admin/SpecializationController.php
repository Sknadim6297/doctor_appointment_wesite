<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use Illuminate\Http\Request;

class SpecializationController extends Controller
{
    public function index()
    {
        $specializations = Specialization::orderBy('id')->get();

        return view('admin.master-data.specialization', [
            'specializations' => $specializations,
            'totalSpecializations' => $specializations->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'specialization' => 'required|string|max:255|unique:specializations,name',
        ]);

        Specialization::create([
            'name' => $validated['specialization'],
        ]);

        return redirect()
            ->route('admin.specialization')
            ->with('success', 'Specialization added successfully.');
    }

    public function update(Request $request, Specialization $specialization)
    {
        $validated = $request->validate([
            'specialization' => 'required|string|max:255|unique:specializations,name,' . $specialization->id,
        ]);

        $specialization->update([
            'name' => $validated['specialization'],
        ]);

        return redirect()
            ->route('admin.specialization')
            ->with('success', 'Specialization updated successfully.');
    }

    public function destroy(Specialization $specialization)
    {
        $specialization->delete();

        return redirect()
            ->route('admin.specialization')
            ->with('success', 'Specialization deleted successfully.');
    }
}
