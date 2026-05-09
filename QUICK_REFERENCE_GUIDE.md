# Sub-Admin Access Control - Quick Reference Guide

## How to Apply to Other Modules

### Step 1: Identify the Sidebar Menu Key

In `config/sidebar_permissions.php`, find your menu item's `key`:

```php
// Example from Doctor Management
[
    'key' => 'doctor-list',  // ← This is the sidebar key
    'title' => 'Doctor List',
    'icon' => 'ri-user-line',
    'route' => 'admin.doctors',
    'route_names' => ['admin.doctors'],
]
```

### Step 2: Protect the Route

In `routes/web.php`, add the middleware:

```php
// BEFORE (single middleware):
Route::get('doctors', [DoctorController::class, 'index'])
    ->middleware('admin.privilege:doctors,view');

// AFTER (with module-specific control):
Route::get('doctors', [DoctorController::class, 'index'])
    ->middleware([
        'admin.privilege:doctors,view',
        'sub-admin.access-control:doctor-list'
    ]);
```

**Syntax**: `sub-admin.access-control:{sidebar-key}`

### Step 3: Pass Owner Details to View (Optional)

In your controller:

```php
public function index()
{
    $adminAccessService = app(\App\Services\AdminAccessService::class);
    
    // If you want to show who this module is assigned to
    $ownerDetails = $adminAccessService->sidebarAccessOwnerDetails(
        'sidebar.doctor-management.doctor-list'
    );
    
    // $ownerDetails = [
    //     'name' => 'John Doe',
    //     'phone' => '9876543210'
    // ]
    
    return view('admin.doctors.list', compact('ownerDetails'));
}
```

### Step 4: (Optional) Update UI to Show Owner

In your Blade view:

```blade
@if($ownerDetails)
    <div class="alert alert-info">
        <strong>Assigned to:</strong> {{ $ownerDetails['name'] }}
        @if($ownerDetails['phone'])
            ({{ $ownerDetails['phone'] }})
        @endif
    </div>
@endif
```

---

## Key Middleware Patterns

### Pattern 1: View-Only Access
```php
Route::get('doctors', [...])
    ->middleware([
        'admin.privilege:doctors,view',
        'sub-admin.access-control:doctor-list'
    ]);
```

### Pattern 2: Edit/Create Access
```php
Route::post('doctors', [...])
    ->middleware([
        'admin.privilege:doctors,edit',
        'sub-admin.access-control:doctor-list'
    ]);
```

### Pattern 3: Multiple Sub-Routes
```php
// All routes under /doctors protected together
Route::middleware([
    'admin.privilege:doctors,edit',
    'sub-admin.access-control:doctor-list'
])->group(function () {
    Route::get('doctors', [DoctorController::class, 'index']);
    Route::post('doctors', [DoctorController::class, 'store']);
    Route::get('doctors/{id}/edit', [DoctorController::class, 'edit']);
    Route::put('doctors/{id}', [DoctorController::class, 'update']);
});
```

---

## Permission Key Format

All permission keys follow this pattern:

```
sidebar.{parent-key}.{menu-key}

Examples:
- sidebar.doctor-management.enrollment-entry
- sidebar.doctor-management.doctor-list
- sidebar.doctor-management.doctor-forms
- sidebar.legal-case.case-list
- sidebar.legal-case.case-forms
```

To find the full key, look at:
1. **Parent key**: The group/section in `config/sidebar_permissions.php` (usually first level)
2. **Menu key**: The specific menu item's `key` property

---

## Testing the New Route

### Test Case 1: Sub-Admin With Permission
```
1. Edit SubAdmin1
2. Assign the new menu access
3. SubAdmin1 logs in
4. Navigate to the route → ✓ Should work

Expected: Page loads normally
```

### Test Case 2: Sub-Admin Without Permission
```
1. Login as SubAdmin2 (who doesn't have access)
2. Try to visit the route directly
3. Example: /admin/doctors

Expected: HTTP 403 Forbidden
Message: "You do not have permission to access this module. 
          Only the assigned sub-admin for 'doctor-list' can access it."
```

### Test Case 3: Duplicate Assignment Prevention
```
1. SubAdmin1 has menu access
2. Try to assign same menu to SubAdmin2
3. Expected: Error message shows conflict

Message: "Cannot assign the following menu items. They are already 
         assigned to other sub-admins:
         • Doctor List → SubAdmin1 (9876543210)"
```

---

## Common Mistakes to Avoid

❌ **Wrong**: Forgetting the sidebar key
```php
// WRONG - middleware without sidebar key
->middleware('sub-admin.access-control')
```

✅ **Right**: Always include the sidebar key
```php
// RIGHT - with sidebar key
->middleware('sub-admin.access-control:doctor-list')
```

---

❌ **Wrong**: Using route name instead of sidebar key
```php
// WRONG - using route name
->middleware('sub-admin.access-control:admin.doctors')
```

✅ **Right**: Use the config sidebar key
```php
// RIGHT - using sidebar key from config
->middleware('sub-admin.access-control:doctor-list')
```

---

❌ **Wrong**: Protecting only some routes in a group
```php
// WRONG - inconsistent protection
Route::get('doctors', [...])
    ->middleware('sub-admin.access-control:doctor-list');

Route::post('doctors', [...]);  // ← Not protected!
```

✅ **Right**: Protect all related routes
```php
// RIGHT - both GET and POST protected
Route::get('doctors', [...])
    ->middleware('sub-admin.access-control:doctor-list');

Route::post('doctors', [...])
    ->middleware('sub-admin.access-control:doctor-list');
```

---

## Debugging

### Issue: Middleware not working
**Solution**: Check that middleware name exactly matches:
```php
// In bootstrap/app.php, verify:
'sub-admin.access-control' => \App\Http\Middleware\SubAdminAccessControl::class
```

### Issue: Getting 403 when should be allowed
**Solution**: Verify permission is assigned:
```bash
php artisan tinker

# Check if user has permission:
> DB::table('admin_privileges')
    ->where('user_id', 5)  // Your user ID
    ->where('page_key', 'doctor-list')
    ->where('is_allowed', true)
    ->exists()
# Should return: true
```

### Issue: "Only assigned sub-admin" message not showing
**Solution**: Verify the sidebar key matches exactly:
```php
// In config/sidebar_permissions.php:
'key' => 'doctor-list',  // ← Sidebar key

// In middleware:
'sub-admin.access-control:doctor-list'  // ← Must match exactly
```

---

## Performance Tips

1. **Caching**: Sidebar catalog is cached during request
2. **Indexing**: Queries use existing indexes on `user_id` and `page_key`
3. **Lazy Loading**: Permissions loaded only when needed
4. **No N+1**: Use `with()` for related permission loads

---

## Deployment Checklist

When adding the middleware to a new route:

- [ ] Route identified and middleware middleware added
- [ ] Sidebar key matches `config/sidebar_permissions.php` exactly
- [ ] Tested with assigned sub-admin (should work)
- [ ] Tested with unassigned sub-admin (should get 403)
- [ ] Tested conflict detection when assigning (should show error)
- [ ] Code deployed to production
- [ ] Sub-admin permissions updated if needed

---

## Reference: All System Components

| Component | Location | Purpose |
|-----------|----------|---------|
| Sidebar Config | `config/sidebar_permissions.php` | Defines menu structure |
| Permission Config | `config/admin_privileges.php` | Defines route-level permissions |
| Model | `app/Models/AdminPrivilege.php` | Enforces constraints at save |
| Service | `app/Services/AdminAccessService.php` | Validates & retrieves permissions |
| Generic Middleware | `app/Http/Middleware/AdminPrivilegeMiddleware.php` | Route permission check |
| Module Middleware | `app/Http/Middleware/SubAdminAccessControl.php` | Ownership check |
| Controller | `app/Http/Controllers/Admin/AdminManagementController.php` | Handles assignment UI |
| Migration | `database/migrations/2026_05_09_*.php` | Creates constraint column |
| Docs | `docs/ACCESS_CONTROL_SYSTEM.md` | Full documentation |

---

## Examples in Codebase

To see examples of the system in action:

1. **Enrollment Entry** (Protected) 
   - Route: `routes/web.php` (search "enrollment")
   - Controller: `app/Http/Controllers/Admin/RenewalController.php`
   - View: `resources/views/admin/doctors/renewal-enrollment.blade.php`

2. **Admin Management** (Assigns Permissions)
   - Controller: `app/Http/Controllers/Admin/AdminManagementController.php` 
   - Methods: `update()`, `denyIfSidebarAccessAlreadyAssigned()`
   - Service: `AdminAccessService::syncSidebarPrivilegesFromSelection()`

---

**Ready to extend the system to other modules? Follow the steps above and refer to enrollment-entry for a complete working example.**
