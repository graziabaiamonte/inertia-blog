<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Create roles and permissions from the enums and link them.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create every permission from the PermissionName enum.
        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        // Admin holds all permissions (replaces a policy before() override).
        $admin = Role::findOrCreate(RoleName::Admin->value);
        $admin->syncPermissions(Permission::all());

        // Author manages only their own posts.
        $author = Role::findOrCreate(RoleName::Author->value);
        $author->syncPermissions([
            PermissionName::CreatePosts->value,
            PermissionName::EditOwnPosts->value,
            PermissionName::DeleteOwnPosts->value,
            PermissionName::PublishPosts->value,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
