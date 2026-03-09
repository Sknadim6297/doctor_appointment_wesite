<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use Illuminate\Http\Request;

class SpecializationController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $specializations = Specialization::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master-data.specialization', [
            'specializations' => $specializations,
            'totalSpecializations' => $specializations->total(),
            'search' => $search,
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
