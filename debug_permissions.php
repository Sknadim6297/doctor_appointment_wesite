<?php
// Quick diagnostic script to debug permission mismatches

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\AdminPrivilege;
use App\Models\User;
use App\Services\AdminAccessService;

$app = app();

// Get a sample sub-admin with sidebar permissions
$subAdmin = User::where('role', '!=', 'super_admin')
    ->whereHas('privileges', function ($query) {
        $query->where('group_key', 'sidebar')
            ->where('is_allowed', true);
    })
    ->first();

if (!$subAdmin) {
    echo "No sub-admin with sidebar permissions found.\n";
    exit;
}

echo "=== SUB-ADMIN: {$subAdmin->name} (ID: {$subAdmin->id}) ===\n\n";

// Get all assigned sidebar permissions
$privileges = AdminPrivilege::where('user_id', $subAdmin->id)
    ->where('group_key', 'sidebar')
    ->where('is_allowed', true)
    ->get();

echo "ASSIGNED SIDEBAR PERMISSIONS IN DATABASE:\n";
foreach ($privileges as $priv) {
    echo "  - page_key: '{$priv->page_key}' | page_title: '{$priv->page_title}'\n";
}

echo "\n";

// Test what the service constructs for permission keys
$service = $app->make(AdminAccessService::class);

echo "CATALOG PERMISSION KEYS (from sidebar config):\n";
$catalog = $service->sidebarCatalogForView();
$flat = [];
$flatten = function (&$nodes) use (&$flatten, &$flat) {
    foreach ($nodes as $node) {
        if (is_array($node) && !empty($node['key'])) {
            $permKey = $node['permission_key'] ?? ('sidebar.' . $node['key']);
            $flat[$permKey] = $node['title'] ?? $node['key'];
            if (!empty($node['children'])) {
                $flatten($node['children']);
            }
        }
    }
};
$flatten($catalog);

foreach ($flat as $key => $title) {
    echo "  - $key => $title\n";
}

echo "\n=== ISSUE DIAGNOSIS ===\n\n";

// Test the middleware logic
$testKey = 'enrollment-entry';
$middlewareConstructedKey = 'sidebar.doctor-management.' . $testKey;

echo "Test Case: 'enrollment-entry'\n";
echo "  Middleware constructs: '$middlewareConstructedKey'\n";
echo "  Database has: ?";

$dbRecord = AdminPrivilege::where('user_id', $subAdmin->id)
    ->where('page_key', 'LIKE', '%' . $testKey . '%')
    ->first();

if ($dbRecord) {
    echo " '{$dbRecord->page_key}'\n";
    if ($dbRecord->page_key === $middlewareConstructedKey) {
        echo "  ✓ MATCH - Permission check will PASS\n";
    } else {
        echo "  ✗ MISMATCH - Permission check will FAIL!\n";
        echo "    Middleware looks for: '$middlewareConstructedKey'\n";
        echo "    But database has:    '{$dbRecord->page_key}'\n";
    }
} else {
    echo " NOT FOUND\n";
    echo "  ✗ Database doesn't have this permission at all!\n";
}
?>
