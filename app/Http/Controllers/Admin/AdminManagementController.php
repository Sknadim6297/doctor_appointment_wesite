<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use App\Models\AdminPrivilege;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminManagementController extends Controller
{
    public function index(Request $request)
    {
        $roleOptions = AdminRole::query()
            ->orderBy('role_title')
            ->get(['role_title', 'role_key']);

        $roleMap = $roleOptions
            ->pluck('role_title', 'role_key')
            ->toArray();

        $roleKeys = array_keys($roleMap);

        $adminsQuery = User::query()->where(function (Builder $query) use ($roleKeys) {
            $query->whereIn('role', $roleKeys)
                ->orWhere('role', 'super_admin');
        });

        if ($request->get('status') === 'active') {
            $adminsQuery->where('is_active', true);
        }

        if ($request->get('status') === 'inactive') {
            $adminsQuery->where('is_active', false);
        }

        $selectedRole = (string) $request->query('role', '');
        if ($selectedRole !== '' && in_array($selectedRole, array_merge($roleKeys, ['super_admin']), true)) {
            $adminsQuery->where('role', $selectedRole);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $adminsQuery->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('employee_no', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $admins = $adminsQuery
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $totalAdmins = $admins->total();
        
        return view('admin.admin-management.index', compact('admins', 'totalAdmins', 'roleOptions', 'roleMap', 'search', 'selectedRole'));
    }

    public function create()
    {
        $roleOptions = AdminRole::query()
            ->orderBy('role_title')
            ->get(['role_title', 'role_key']);

        return view('admin.admin-management.create', compact('roleOptions'));
    }

    public function roles()
    {
        $roleRows = AdminRole::query()
            ->select('admin_roles.*')
            ->selectRaw('COUNT(users.id) as users_count')
            ->leftJoin('users', 'users.role', '=', 'admin_roles.role_key')
            ->groupBy('admin_roles.id', 'admin_roles.role_title', 'admin_roles.role_key', 'admin_roles.created_at', 'admin_roles.updated_at')
            ->orderBy('admin_roles.id')
            ->get();

        return view('admin.admin-management.roles', compact('roleRows'));
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'role_title' => 'required|string|max:100|unique:admin_roles,role_title',
        ]);

        $roleKey = Str::of($validated['role_title'])->trim()->lower()->replace(' ', '_')->toString();
        $uniqueRoleKey = $roleKey;
        $counter = 1;

        while (AdminRole::where('role_key', $uniqueRoleKey)->exists()) {
            $uniqueRoleKey = $roleKey . '_' . $counter;
            $counter++;
        }

        AdminRole::create([
            'role_title' => $validated['role_title'],
            'role_key' => $uniqueRoleKey,
        ]);

        return redirect()->route('admin.admin-management.roles')
            ->with('success', 'Role added successfully!');
    }

    public function updateRole(Request $request, AdminRole $role)
    {
        $validated = $request->validate([
            'role_title' => ['required', 'string', 'max:100', Rule::unique('admin_roles', 'role_title')->ignore($role->id)],
        ]);

        $roleKey = Str::of($validated['role_title'])->trim()->lower()->replace(' ', '_')->toString();
        $uniqueRoleKey = $roleKey;
        $counter = 1;

        while (AdminRole::where('role_key', $uniqueRoleKey)->where('id', '!=', $role->id)->exists()) {
            $uniqueRoleKey = $roleKey . '_' . $counter;
            $counter++;
        }

        $role->update([
            'role_title' => $validated['role_title'],
            'role_key' => $uniqueRoleKey,
        ]);

        return redirect()->route('admin.admin-management.roles')
            ->with('success', 'Role updated successfully!');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email',
            'salary' => 'nullable|numeric|min:0',
            'employee_no' => 'nullable|string|max:50|unique:users,employee_no',
            'password' => 'required|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'aadhaar_no' => 'nullable|string|max:20',
            'pan_no' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'role' => ['required', Rule::exists('admin_roles', 'role_key')],
            'status' => 'required|in:active,inactive',
            'profile_pic' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $profilePicPath = null;
        if ($request->hasFile('profile_pic')) {
            $profilePicPath = $request->file('profile_pic')->store('profile-pics', 'public');
        }

        $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));

        User::create([
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'salary' => $validated['salary'] ?? null,
            'employee_no' => $validated['employee_no'] ?? null,
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'aadhaar_no' => $validated['aadhaar_no'] ?? null,
            'pan_no' => $validated['pan_no'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'profile_pic' => $profilePicPath,
            'role' => $validated['role'],
            'is_active' => $validated['status'] === 'active',
        ]);

        return redirect()->route('admin.admin-management.index')
            ->with('success', 'Sub admin created successfully!');
    }

    public function edit(User $admin)
    {
        $roleOptions = AdminRole::query()
            ->orderBy('role_title')
            ->get(['role_title', 'role_key']);

        return view('admin.admin-management.edit', compact('admin', 'roleOptions'));
    }

    public function update(Request $request, User $admin)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($admin->id)],
            'salary' => 'nullable|numeric|min:0',
            'employee_no' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_no')->ignore($admin->id)],
            'phone' => 'nullable|string|max:20',
            'aadhaar_no' => 'nullable|string|max:20',
            'pan_no' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'role' => ['required', Rule::exists('admin_roles', 'role_key')],
            'status' => 'required|in:active,inactive',
            'profile_pic' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $profilePicPath = $admin->profile_pic;
        if ($request->hasFile('profile_pic')) {
            $profilePicPath = $request->file('profile_pic')->store('profile-pics', 'public');
        }

        $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));

        $admin->update([
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'salary' => $validated['salary'] ?? null,
            'employee_no' => $validated['employee_no'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'aadhaar_no' => $validated['aadhaar_no'] ?? null,
            'pan_no' => $validated['pan_no'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'profile_pic' => $profilePicPath,
            'role' => $validated['role'],
            'is_active' => $validated['status'] === 'active',
        ]);

        return redirect()->route('admin.admin-management.index')
            ->with('success', 'Sub admin updated successfully!');
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
        if ((int) $admin->getKey() === (int) Auth::id()) {
            return back()->with('error', 'You cannot delete your own account!');
        }

        $admin->delete();

        return redirect()->route('admin.admin-management.index')
            ->with('success', 'Sub admin deleted successfully!');
    }

    public function privileges(User $admin)
    {
        $this->syncPrivilegeCatalogForUser($admin);

        $privileges = AdminPrivilege::query()
            ->where('user_id', $admin->id)
            ->orderBy('id')
            ->get();

        $groupedPrivileges = $privileges->groupBy('group_key')->map(function (Collection $items) {
            return [
                'group_title' => (string) $items->first()->group_title,
                'items' => $items->values(),
            ];
        });

        return view('admin.admin-management.privileges', [
            'admin' => $admin,
            'groupedPrivileges' => $groupedPrivileges,
            'totalPrivileges' => $privileges->count(),
        ]);
    }

    public function updatePrivileges(Request $request, User $admin)
    {
        $validated = $request->validate([
            'selected_ids' => 'required|array|min:1',
            'selected_ids.*' => 'integer|exists:admin_privileges,id',
            'action' => 'required|in:allow,disallow',
        ]);

        $isAllowed = $validated['action'] === 'allow';

        AdminPrivilege::query()
            ->where('user_id', $admin->id)
            ->whereIn('id', $validated['selected_ids'])
            ->update([
                'is_allowed' => $isAllowed,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.admin-management.privileges', $admin)
            ->with('success', $isAllowed ? 'Selected privileges allowed successfully.' : 'Selected privileges disallowed successfully.');
    }

    public function loginLog(User $admin)
    {
        $logs = AdminLoginLog::query()
            ->where('user_id', $admin->id)
            ->orderByDesc('logged_in_at')
            ->paginate(20);

        return view('admin.admin-management.login-log', compact('admin', 'logs'));
    }

    private function syncPrivilegeCatalogForUser(User $admin): void
    {
        $catalog = config('admin_privileges', []);

        DB::transaction(function () use ($admin, $catalog): void {
            foreach ($catalog as $groupKey => $group) {
                $groupTitle = $group['title'] ?? Str::title(str_replace('_', ' ', $groupKey));

                foreach (($group['pages'] ?? []) as $page) {
                    $pageKey = (string) ($page['key'] ?? '');
                    if ($pageKey === '') {
                        continue;
                    }

                    $pageTitle = (string) ($page['title'] ?? Str::title(str_replace('_', ' ', $pageKey)));

                    AdminPrivilege::query()->updateOrCreate(
                        [
                            'user_id' => $admin->id,
                            'page_key' => $pageKey,
                        ],
                        [
                            'group_key' => (string) $groupKey,
                            'group_title' => $groupTitle,
                            'page_title' => $pageTitle,
                        ]
                    );
                }
            }
        });
    }
}

