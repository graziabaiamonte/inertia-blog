<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
});

test('unverified author is blocked from admin posts', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->unverified()->create();
    $user->assignRole(RoleName::Author->value);

    $this->actingAs($user)->get('/admin/posts')->assertRedirect('/verify-email');
});

test('verified author can access admin posts', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole(RoleName::Author->value);
    $user->markEmailAsVerified();

    $this->actingAs($user)->get('/admin/posts')->assertStatus(200);
});
