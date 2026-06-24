<?php

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('creates the admin and author roles', function () {
    expect(Role::where('name', RoleName::Admin->value)->exists())->toBeTrue()
        ->and(Role::where('name', RoleName::Author->value)->exists())->toBeTrue();
});

it('creates every permission from the enum', function () {
    foreach (PermissionName::cases() as $permission) {
        expect(Permission::where('name', $permission->value)->exists())->toBeTrue();
    }

    expect(Permission::count())->toBe(count(PermissionName::cases()));
});

it('grants the admin role all permissions', function () {
    $admin = Role::where('name', RoleName::Admin->value)->first();

    expect($admin->permissions()->count())->toBe(count(PermissionName::cases()));
});

it('grants the author role only its own-post permissions', function () {
    $author = Role::where('name', RoleName::Author->value)->first();

    expect($author->permissions->pluck('name')->sort()->values()->all())->toBe(collect([
        PermissionName::CreatePosts->value,
        PermissionName::EditOwnPosts->value,
        PermissionName::DeleteOwnPosts->value,
        PermissionName::PublishPosts->value,
    ])->sort()->values()->all());
});
