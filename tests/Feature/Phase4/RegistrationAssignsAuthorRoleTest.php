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

test('new user is assigned the author role on registration', function () {
    /** @var TestCase $this */
    $this->post('/register', [
        'name' => 'New Author',
        'email' => 'newauthor@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    /** @var User $user */
    $user = User::where('email', 'newauthor@example.com')->firstOrFail();

    expect($user->hasRole(RoleName::Author->value))->toBeTrue();
    expect($user->hasRole(RoleName::Admin->value))->toBeFalse();
});

test('newly registered user has exactly one role', function () {
    /** @var TestCase $this */
    $this->post('/register', [
        'name' => 'Another User',
        'email' => 'another@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    /** @var User $user */
    $user = User::where('email', 'another@example.com')->firstOrFail();

    expect($user->getRoleNames())->toHaveCount(1);
    expect($user->hasRole(RoleName::Admin->value))->toBeFalse();
});
