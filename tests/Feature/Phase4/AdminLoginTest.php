<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
});

test('admin can access dashboard', function () {
    /** @var TestCase $this */
    $this->actingAs(admin())->get('/dashboard')->assertStatus(200);
});

test('admin has the admin role', function () {
    $adminUser = admin();

    expect($adminUser->hasRole(RoleName::Admin->value))->toBeTrue();
});

test('admin can access admin posts area', function () {
    /** @var TestCase $this */
    $this->actingAs(admin())->get('/admin/posts')->assertStatus(200);
});

test('author cannot access taxonomy management', function () {
    /** @var TestCase $this */
    $author = author();
    $author->markEmailAsVerified();

    $this->actingAs($author)->get('/admin/categories')->assertStatus(403);
});

test('unauthenticated user is redirected from dashboard', function () {
    /** @var TestCase $this */
    $this->get('/dashboard')->assertRedirect('/login');
});

test('unverified user is redirected from dashboard', function () {
    /** @var TestCase $this */
    $user = User::factory()->unverified()->create();
    $user->assignRole(RoleName::Author->value);

    $this->actingAs($user)->get('/dashboard')->assertRedirect('/verify-email');
});

test('seeded admin grazia@gmail.com exists with admin role and verified email', function () {
    /** @var TestCase $this */
    $this->seed(DatabaseSeeder::class);

    /** @var User $admin */
    $admin = User::where('email', 'grazia@gmail.com')->firstOrFail();

    expect($admin->hasRole(RoleName::Admin->value))->toBeTrue()
        ->and($admin->hasVerifiedEmail())->toBeTrue();
});
