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

it('redirects guests from the admin posts area to login', function () {
    /** @var TestCase $this */
    $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
});

it('blocks unverified users from the admin area', function () {
    /** @var TestCase $this */
    $unverified = User::factory()->unverified()->create();
    $unverified->assignRole(RoleName::Author->value);

    $this->actingAs($unverified)
        ->get(route('admin.posts.index'))
        ->assertRedirect(route('verification.notice'));
});

it('allows authors into the posts area', function () {
    /** @var TestCase $this */
    $this->actingAs(author())
        ->get(route('admin.posts.index'))
        ->assertOk();
});

it('denies authors access to the taxonomy and moderation areas', function () {
    /** @var TestCase $this */
    $author = author();

    $this->actingAs($author)->get(route('admin.categories.index'))->assertForbidden();
    $this->actingAs($author)->get(route('admin.tags.index'))->assertForbidden();
    $this->actingAs($author)->get(route('admin.comments.index'))->assertForbidden();
});

it('grants admins access to every admin area', function () {
    /** @var TestCase $this */
    $admin = admin();

    $this->actingAs($admin)->get(route('admin.posts.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.tags.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.comments.index'))->assertOk();
});
