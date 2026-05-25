<?php
// Usage: php scripts/reset_admin_password.php email@example.com NewPassword123

require __DIR__ . "/../vendor/autoload.php";

$app = require_once __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

if ($argc === 2 && $argv[1] === '--list') {
    $usersWithRoleField = User::whereNotNull('role')->where('role', '!=', '')->get();
    $usersWithRelation = User::whereHas('roles')->get();

    $users = $usersWithRoleField->merge($usersWithRelation)->unique('id')->values();

    if ($users->isEmpty()) {
        echo "No admin users found.\n";
        exit(0);
    }

    foreach ($users as $u) {
        $roleKeys = $u->adminRoleKeys();
        $roles = implode(',', $roleKeys);
        echo "ID: {$u->id} | Email: {$u->email} | Name: {$u->name} | role: {$u->role} | roles: {$roles}\n";
    }

    exit(0);
}

if ($argc < 3) {
    echo "Usage: php scripts/reset_admin_password.php <email> <new_password>\n";
    echo "Or: php scripts/reset_admin_password.php --list    # list admin users\n";
    exit(1);
}

$email = $argv[1];
$newPassword = $argv[2];


$user = User::where('email', $email)->first();

if (! $user) {
    echo "No user found with email: {$email}\n";
    exit(2);
}

$user->password = Hash::make($newPassword);
$user->save();

echo "Password updated for user: {$email}\n";
