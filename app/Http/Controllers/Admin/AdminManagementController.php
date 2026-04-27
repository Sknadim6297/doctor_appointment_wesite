<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AdminLoginLog;
use App\Models\AdminPrivilege;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\AdminAccessService;

class AdminManagementController extends Controller
{
    public function __construct(private readonly AdminAccessService $adminAccessService)
    {
    }

    public function index(Request $request)
    {
        $roleOptions = AdminRole::query()
            ->orderBy('role_title')
            ->get(['role_title', 'role_key']);

        $roleMap = $roleOptions
            ->pluck('role_title', 'role_key')
            ->toArray();

        // Ensure built-in legacy roles are visible and filterable in the panel.
        $roleMap = array_merge([
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
        ], $roleMap);

        $roleKeys = array_keys($roleMap);
        $customRoleKeys = AdminRole::query()->pluck('role_key')->all();

        $adminsQuery = User::query()
            ->with('roles:id,role_key,role_title')
            ->withCount([
                'loginLogs as login_logs_count',
                'privileges as allowed_privileges_count' => function (Builder $query) {
                    $query->where('is_allowed', true);
                },
            ])
            ->addSelect([
                'activity_logs_count' => AdminActivityLog::query()
                    ->selectRaw('count(distinct id)')
                    ->where(function (Builder $query) {
                        $query->whereColumn('actor_user_id', 'users.id')
                            ->orWhereColumn('owner_user_id', 'users.id');
                    })
                    ->where(function (Builder $query) {
                        $query->where('module_key', '!=', 'auth')
                            ->orWhereNull('module_key')
                            ->orWhereNotIn('action', ['login', 'logout']);
                    }),
            ])
            ->where(function (Builder $query) use ($roleKeys, $customRoleKeys) {
                $query->whereIn('role', $roleKeys)
                    ->orWhereHas('roles', function (Builder $roleQuery) use ($customRoleKeys) {
                        $roleQuery->whereIn('role_key', $customRoleKeys);
                    });
            });

        if ($request->get('status') === 'active') {
            $adminsQuery->where('is_active', true);
        }

        if ($request->get('status') === 'inactive') {
            $adminsQuery->where('is_active', false);
        }

        $selectedRole = (string) $request->query('role', '');
        if ($selectedRole !== '' && in_array($selectedRole, $roleKeys, true)) {
            $adminsQuery->where(function (Builder $query) use ($selectedRole) {
                $query->where('role', $selectedRole)
                    ->orWhereHas('roles', function (Builder $roleQuery) use ($selectedRole) {
                        $roleQuery->where('role_key', $selectedRole);
                    });
            });
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

        $privilegeCatalog = $this->adminAccessService->privilegeCatalogForView();

        return view('admin.admin-management.create', compact('roleOptions', 'privilegeCatalog'));
    }

    public function roles()
    {
        $roleRows = AdminRole::query()
            ->withCount('users')
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
            'role_keys' => ['required', 'array', 'min:1'],
            'role_keys.*' => [Rule::exists('admin_roles', 'role_key')],
            'status' => 'required|in:active,inactive',
            'profile_pic' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'privilege_keys' => 'nullable|array',
            'privilege_keys.*' => 'string',
        ]);

        $profilePicPath = null;
        if ($request->hasFile('profile_pic')) {
            $profilePicPath = $request->file('profile_pic')->store('profile-pics', 'public');
        }

        $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));

        $user = User::create([
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
            'role' => $validated['role_keys'][0],
            'is_active' => $validated['status'] === 'active',
        ]);

        $this->adminAccessService->syncRoles($user, $validated['role_keys']);
        $this->adminAccessService->syncPrivilegesFromSelection($user, $validated['privilege_keys'] ?? []);

        return redirect()->route('admin.admin-management.index')
            ->with('success', 'Sub admin created successfully!');
    }

    public function edit(User $admin)
    {
        if ($response = $this->rejectProtectedAdminMutation($admin)) {
            return $response;
        }

        $roleOptions = AdminRole::query()
            ->orderBy('role_title')
            ->get(['role_title', 'role_key']);

        $this->adminAccessService->syncPrivilegeCatalogForUser($admin);

        $privilegeCatalog = $this->adminAccessService->privilegeCatalogForView();
        $selectedPrivilegeKeys = $admin->privileges()
            ->where('is_allowed', true)
            ->get()
            ->map(fn (AdminPrivilege $privilege) => $privilege->page_key . ':' . $privilege->action_key)
            ->all();

        return view('admin.admin-management.edit', compact('admin', 'roleOptions', 'privilegeCatalog', 'selectedPrivilegeKeys'));
    }

    public function update(Request $request, User $admin)
    {
        if ($response = $this->rejectProtectedAdminMutation($admin)) {
            return $response;
        }

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
            'role_keys' => ['required', 'array', 'min:1'],
            'role_keys.*' => [Rule::exists('admin_roles', 'role_key')],
            'status' => 'required|in:active,inactive',
            'profile_pic' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'privilege_keys' => 'nullable|array',
            'privilege_keys.*' => 'string',
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
            'role' => $validated['role_keys'][0],
            'is_active' => $validated['status'] === 'active',
        ]);

        $this->adminAccessService->syncRoles($admin, $validated['role_keys']);
        $this->adminAccessService->syncPrivilegesFromSelection($admin, $validated['privilege_keys'] ?? []);

        return redirect()->route('admin.admin-management.index')
            ->with('success', 'Sub admin updated successfully!');
    }

    public function resetPassword(Request $request, User $admin)
    {
        if ($response = $this->rejectProtectedAdminMutation($admin)) {
            return $response;
        }

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
        if ($response = $this->rejectProtectedAdminMutation($admin)) {
            return $response;
        }

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
        if ($response = $this->rejectProtectedAdminMutation($admin)) {
            return $response;
        }

        $this->adminAccessService->syncPrivilegeCatalogForUser($admin);

        $privileges = AdminPrivilege::query()
            ->where('user_id', $admin->id)
            ->orderBy('group_key')
            ->orderBy('page_key')
            ->orderBy('action_key')
            ->get();

        $groupedPrivileges = $privileges->groupBy('group_key')->map(function ($items) {
            return [
                'group_title' => (string) $items->first()->group_title,
                'pages' => $items->groupBy('page_key')->map(function ($pageItems) {
                    return [
                        'page_title' => (string) $pageItems->first()->page_title,
                        'items' => $pageItems->values(),
                    ];
                })->values(),
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
        if ($response = $this->rejectProtectedAdminMutation($admin)) {
            return $response;
        }

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

    public function activityLog(User $admin)
    {
        $logs = AdminActivityLog::query()
            ->with(['actor:id,name,email', 'owner:id,name,email'])
            ->where(function (Builder $query) use ($admin) {
                $query->where('actor_user_id', $admin->id)
                    ->orWhere('owner_user_id', $admin->id);
            })
            ->where(function (Builder $query) {
                $query->where('module_key', '!=', 'auth')
                    ->orWhereNull('module_key')
                    ->orWhereNotIn('action', ['login', 'logout']);
            })
            ->distinct()
            ->orderByDesc('occurred_at')
            ->paginate(20);

        return view('admin.admin-management.activity-log', compact('admin', 'logs'));
    }

    private function rejectProtectedAdminMutation(User $admin): ?RedirectResponse
    {
        if ($admin->hasAdminRole('super_admin')) {
            return redirect()
                ->route('admin.admin-management.index')
                ->with('error', 'Super admin account cannot be edited, reset, or deleted from this panel.');
        }

        return null;
    }
}

