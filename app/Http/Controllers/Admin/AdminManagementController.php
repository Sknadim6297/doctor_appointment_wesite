<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    public function index()
    {
        $admins = User::whereIn('role', ['admin', 'super_admin'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('admin.admin-management.index', compact('admins'));
    }

    public function create()
    {
        return view('admin.admin-management.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:admin,super_admin',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.admin-management.index')
            ->with('success', 'Admin created successfully!');
    }

    public function edit(User $admin)
    {
        return view('admin.admin-management.edit', compact('admin'));
    }

    public function update(Request $request, User $admin)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id,
            'role' => 'required|in:admin,super_admin',
            'is_active' => 'boolean',
        ]);

        $admin->update($validated);

        return redirect()->route('admin.admin-management.index')
            ->with('success', 'Admin updated successfully!');
    }

    public function resetPassword(Request $request, User $admin)
    {
        $validated = $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password reset successfully!');
    }

    public function destroy(User $admin)
    {
        // Prevent deleting yourself
        if ($admin->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account!');
        }

        $admin->delete();

        return redirect()->route('admin.admin-management.index')
            ->with('success', 'Admin deleted successfully!');
    }
}

