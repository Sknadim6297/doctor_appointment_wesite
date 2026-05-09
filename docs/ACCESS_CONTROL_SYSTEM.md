# Sub-Admin Access Control System - Documentation

## Overview

This document outlines the comprehensive access control system implemented for sub-admin management in MediForum Admin. The system enforces **one-to-one mapping** between sidebar menu permissions and sub-admins, ensuring that each menu item can only be assigned to a single sub-admin.

---

## Architecture

### 1. Permission Assignment Model

**Database Table**: `admin_privileges`

```sql
CREATE TABLE admin_privileges (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL (FK: users.id),
    group_key VARCHAR(100),           -- 'sidebar', 'enrollment', 'doctors', etc.
    group_title VARCHAR(150),
    page_key VARCHAR(150),            -- Unique key for the permission
    page_title VARCHAR(255),
    action_key VARCHAR(50),           -- 'view', 'edit', 'delete'
    action_title VARCHAR(100),
    is_allowed BOOLEAN DEFAULT false, -- Whether permission is granted
    sidebar_unique_marker VARCHAR(200) UNIQUE NULL,  -- Enforces uniqueness for sidebar
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (user_id, page_key),
    INDEX (user_id, group_key)
);
```

**Key Constraints**:
- `sidebar_unique_marker`: Set to `sidebar:{page_key}` when a sidebar permission is assigned (`is_allowed = true`)
- This ensures only ONE user can have `is_allowed = true` for a given sidebar permission
- Non-sidebar permissions have `sidebar_unique_marker = NULL`

---

### 2. Permission Assignment Flow

#### Creating/Updating a Sub-Admin with Sidebar Permissions

1. **Validation Phase** (AdminManagementController)
   - Check for conflicts: `findAllSidebarAccessConflicts()`
   - Returns list of all menus already assigned to other sub-admins
   - If conflicts exist, return error with conflict details

2. **Assignment Phase** (AdminAccessService::syncSidebarPrivilegesFromSelection)
   - Begin database transaction
   - Verify no conflicts exist for new assignments
   - Disable all existing sidebar privileges for the user
   - Enable only selected sidebar permissions
   - Set `sidebar_unique_marker` for enabled permissions
   - Enable corresponding route-level permissions

3. **Model Validation** (AdminPrivilege::booted)
   - Automatically validates constraints during save
   - Throws exception if constraint violated
   - Sets `sidebar_unique_marker` automatically

---

### 3. Permission Layers

#### Layer 1: Sidebar Visibility (UI Level)
- **Service**: `AdminAccessService::visibleSidebarCatalogForUser()`
- **Output**: Only assigned menus visible in sidebar
- **Location**: `AppServiceProvider::boot()` passes `$sidebarTree` to views
- **Result**: Unassigned menus hidden from UI

#### Layer 2: Route-Level Access Control (Middleware)
- **Middleware**: `AdminPrivilegeMiddleware`
- **Usage**: `->middleware('admin.privilege:enrollment,view')`
- **Behavior**: Checks database privilege record before allowing request
- **Fallback**: Super-admins bypass all checks

#### Layer 3: Module-Specific Access Control (Middleware)
- **Middleware**: `SubAdminAccessControl`
- **Usage**: `->middleware('sub-admin.access-control:enrollment-entry')`
- **Behavior**: Enforces that ONLY the assigned sub-admin can access specific modules
- **Super-admins**: Always allowed
- **Example**: Only the sub-admin with "Enrollment Entry" permission can create new enrollments

---

## Enrollment Entry Access Control

### Routes Protected

```php
// NEW ENROLLMENT ENTRY - Protected for assigned sub-admins only
Route::get('enrollment/create', [...])
    ->middleware(['admin.privilege:enrollment,edit', 'sub-admin.access-control:enrollment-entry']);
    
Route::post('enrollment', [...])
    ->middleware(['admin.privilege:enrollment,edit', 'sub-admin.access-control:enrollment-entry']);
```

### Access Flow

1. **Create new sub-admin** → Assign "Enrollment Entry" permission
2. **Sub-admin logs in** → "Doctor Management > Enrollment Entry" appears in sidebar
3. **Click "Enrollment Entry"** → Routed to `admin.enrollment.create`
4. **Middleware checks**:
   - `admin.privilege:enrollment,edit` → Database record check
   - `sub-admin.access-control:enrollment-entry` → Permission ownership check
5. **Auto-fill page** with:
   - Sub-admin name
   - Office-use agent name (owner of menu permission)
   - Phone number

### Direct URL Access Protection

If a sub-admin WITHOUT the "Enrollment Entry" permission tries to access:
```
/admin/enrollment/create
```

**Result**: HTTP 403 Forbidden
```
Access control misconfiguration: sidebar key not provided.
```

---

## Unique Assignment Enforcement

### Constraint Violation Prevention

**Scenario**: Admin tries to assign "Enrollment Entry" to SubAdmin2, but SubAdmin1 already has it.

**Flow**:

```
Admin selects SubAdmin2 + "Enrollment Entry" permission
    ↓
AdminManagementController::update()
    ↓
denyIfSidebarAccessAlreadyAssigned() checks:
  → SELECT * FROM admin_privileges 
      WHERE page_key = 'enrollment-entry' 
      AND is_allowed = true 
      AND user_id != SubAdmin2.id
    ↓
Conflict found! SubAdmin1 has it
    ↓
Return error to Admin:
  "Cannot assign the following menu items. They are already assigned to 
   other sub-admins:
   • Enrollment Entry → SubAdmin1 (9876543210)"
    ↓
Assignment BLOCKED
```

### Bypass Scenario

When **SubAdmin1 is removed** from the permission:

```
Admin removes "Enrollment Entry" from SubAdmin1
    ↓
syncSidebarPrivilegesFromSelection() with empty selection
    ↓
UPDATE admin_privileges SET is_allowed = false, sidebar_unique_marker = NULL
    WHERE user_id = SubAdmin1 AND page_key = 'enrollment-entry'
    ↓
Now "Enrollment Entry" is unassigned
    ↓
Next, assign to SubAdmin2:
    ↓
No conflicts! Assignment succeeds
    ↓
UPDATE admin_privileges SET is_allowed = true, sidebar_unique_marker = 'sidebar:enrollment-entry'
    WHERE user_id = SubAdmin2 AND page_key = 'enrollment-entry'
```

---

## Configuration Files

### 1. Sidebar Permissions (`config/sidebar_permissions.php`)

Defines the menu structure:

```php
[
    'key' => 'doctor-management',
    'title' => 'Doctor Management',
    'children' => [
        [
            'key' => 'enrollment-entry',
            'title' => 'Enrollment Entry',
            'icon' => 'ri-user-add-line',
            'route' => 'admin.enrollment.create',
            'route_names' => ['admin.enrollment.create'],
        ],
        // ... other menu items
    ],
]
```

### 2. Admin Privileges (`config/admin_privileges.php`)

Defines route-level permissions:

```php
'enrollment' => [
    'title' => 'Enrollment Management',
    'pages' => [
        ['key' => 'enrollment', 'title' => 'Enrollment', 'actions' => ['view', 'edit']],
        // ... other pages
    ],
]
```

---

## Service Layer Methods

### AdminAccessService

#### `findAllSidebarAccessConflicts(array $keys, ?int $excludeUserId)`
Returns array of all conflicts for the given sidebar keys.

```php
$conflicts = $adminAccessService->findAllSidebarAccessConflicts(
    ['sidebar.doctor-management.enrollment-entry'],
    excludeUserId: null
);

// Returns:
[
    [
        'permission_key' => 'enrollment-entry',
        'menu_title' => 'Enrollment Entry',
        'owner_name' => 'John Doe',
        'owner_phone' => '9876543210',
        'owner_user_id' => 5,
    ]
]
```

#### `sidebarAccessOwnerDetails(string $permissionKey)`
Get the owner of a sidebar permission.

```php
$details = $adminAccessService->sidebarAccessOwnerDetails('sidebar.doctor-management.enrollment-entry');

// Returns:
[
    'name' => 'John Doe',
    'phone' => '9876543210',
]
```

#### `visibleSidebarCatalogForUser(User $user)`
Get filtered sidebar tree (only assigned menus).

```php
$sidebarTree = $adminAccessService->visibleSidebarCatalogForUser($user);
// Returns only menus where is_allowed = true
```

#### `syncSidebarPrivilegesFromSelection(User $user, array $selectedKeys)`
Sync sidebar privileges with validation.

```php
try {
    $adminAccessService->syncSidebarPrivilegesFromSelection($user, [
        'sidebar.doctor-management.enrollment-entry',
    ]);
} catch (\Exception $e) {
    // Handle conflict: "Enrollment Entry is already assigned to another sub-admin"
}
```

---

## Middleware

### 1. AdminPrivilegeMiddleware
**File**: `app/Http/Middleware/AdminPrivilegeMiddleware.php`

```php
Route::get('enrollment', [...])
    ->middleware('admin.privilege:enrollment,view');
    
// Checks: Does user have admin_privileges record with 
// page_key='enrollment', action_key='view', is_allowed=true?
```

### 2. SubAdminAccessControl
**File**: `app/Http/Middleware/SubAdminAccessControl.php`

```php
Route::post('enrollment', [...])
    ->middleware('sub-admin.access-control:enrollment-entry');
    
// Checks: Does user have permission 'sidebar.doctor-management.enrollment-entry' 
// assigned AND is_allowed=true?
```

---

## Error Handling

### Conflict Detection

**When assigning permissions:**

```php
// In AdminManagementController::update()
if ($conflictResponse = $this->denyIfSidebarAccessAlreadyAssigned($validated['sidebar_keys'], $admin->id)) {
    return $conflictResponse;
}

// Generates multi-conflict error message
```

**Error Message Format**:
```
Cannot assign the following menu items. They are already assigned to other sub-admins:

• Enrollment Entry → SubAdmin1 (9876543210)
• Doctor List → SubAdmin2 (9876543211)

Each sidebar menu can only be assigned to ONE sub-admin at a time.
```

### Direct URL Access Denial

**When accessing without permission:**

```
HTTP 403 Forbidden

You do not have permission to access this module. 
Only the assigned sub-admin for 'enrollment-entry' can access it.
```

---

## Implementation Checklist

- [x] Database constraint with `sidebar_unique_marker` column
- [x] AdminPrivilege model with automatic constraint enforcement
- [x] AdminAccessService conflict detection methods
- [x] Conflict validation in AdminManagementController
- [x] SubAdminAccessControl middleware for module access
- [x] Route middleware application
- [x] Error message formatting
- [x] Exception handling in store/update methods
- [x] Sidebar visibility filtering
- [x] Auto-fill logic in RenewalController (for agent details)

---

## Testing Scenarios

### Scenario 1: Single Sub-Admin Assignment
```
1. Create SubAdmin1 with "Enrollment Entry"
   ✓ SubAdmin1 can access enrollment.create
   ✓ Menu visible in sidebar

2. SubAdmin1 logs in, tries /admin/enrollment/create
   ✓ Allowed (has permission)
```

### Scenario 2: Attempt Duplicate Assignment
```
1. SubAdmin1 already has "Enrollment Entry"
2. Try to assign "Enrollment Entry" to SubAdmin2
   ✗ Error: "Already assigned to SubAdmin1"
   ✗ Assignment blocked
   ✓ SubAdmin1 still retains access
```

### Scenario 3: Un-assigned Sub-Admin Access
```
1. SubAdmin2 has NO "Enrollment Entry" permission
2. SubAdmin2 tries /admin/enrollment/create
   ✗ HTTP 403 Forbidden
   ✓ Access denied (correct behavior)
```

### Scenario 4: Permission Transfer
```
1. Remove "Enrollment Entry" from SubAdmin1
   → is_allowed = false, sidebar_unique_marker = NULL

2. Assign "Enrollment Entry" to SubAdmin2
   ✓ Succeeds (no conflict, previous removed)
   ✓ SubAdmin2 can now access
```

---

## Migration & Deployment

### New Installation
```bash
# Run all migrations (including the new constraint migration)
php artisan migrate

# Seed initial permissions (if needed)
php artisan db:seed --class=AdminPrivilegesSeeder
```

### Existing Installation
```bash
# Run new constraint migration
php artisan migrate

# No existing data should break:
# - If duplicates exist, migration removes older ones
# - Keeps most recent assignment
```

---

## Troubleshooting

### Issue: "Cannot assign, already assigned"
**Cause**: Another sub-admin has this permission
**Solution**: Remove from the other sub-admin first, then reassign

### Issue: Sub-admin can't see menu
**Cause**: Permission not assigned
**Solution**: Go to Admin Management → Edit → Assign sidebar access

### Issue: Direct URL access gives 403
**Cause**: Sub-admin doesn't have the specific module permission
**Solution**: Assign the permission in Admin Management

### Issue: Sync operation fails with exception
**Cause**: Conflicting permission assignment
**Solution**: Check controller error message, follow suggested steps

---

## Security Notes

1. **Database-level constraint**: Prevents accidental duplicates
2. **Application-level validation**: Checks before state changes
3. **Middleware enforcement**: Protects routes from direct URL access
4. **Exception handling**: Graceful failure with meaningful errors
5. **Transaction safety**: Database changes rolled back on error

---

## Performance Considerations

- Queries use indexes on `(user_id, group_key)` and unique constraint
- Sidebar catalog cached during request (AppServiceProvider)
- Conflict checks use efficient single queries
- No N+1 problems in permission loading

---

## Future Enhancements

1. Audit log for permission assignment changes
2. Bulk permission management UI
3. Permission templates/roles
4. Time-based permission expiration
5. Permission delegation delegation system
