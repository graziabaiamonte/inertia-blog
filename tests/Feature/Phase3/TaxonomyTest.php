<?php

use App\Models\Category;
use App\Models\Tag;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
});

it('lets an admin create a category', function () {
    /** @var TestCase $this */
    $this->actingAs(admin())->post(route('admin.categories.store'), [
        'name' => 'Laravel',
    ])->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseHas('categories', ['name' => 'Laravel', 'slug' => 'laravel']);
});

it('lets an admin update and delete a category', function () {
    /** @var TestCase $this */
    $category = Category::factory()->create();

    $this->actingAs(admin())->put(route('admin.categories.update', $category), [
        'name' => 'Renamed',
    ])->assertRedirect();
    expect($category->fresh()->name)->toBe('Renamed');

    $this->actingAs(admin())->delete(route('admin.categories.destroy', $category))
        ->assertRedirect();
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('lets an admin create a tag', function () {
    /** @var TestCase $this */
    $this->actingAs(admin())->post(route('admin.tags.store'), [
        'name' => 'PHP',
    ])->assertRedirect(route('admin.tags.index'));

    $this->assertDatabaseHas('tags', ['name' => 'PHP', 'slug' => 'php']);
});

it('forbids an author from managing categories', function () {
    /** @var TestCase $this */
    $author = author();

    $this->actingAs($author)->get(route('admin.categories.index'))->assertForbidden();
    $this->actingAs($author)->post(route('admin.categories.store'), ['name' => 'Nope'])
        ->assertForbidden();

    $this->assertDatabaseMissing('categories', ['name' => 'Nope']);
});

it('forbids an author from managing tags', function () {
    /** @var TestCase $this */
    $author = author();
    $tag = Tag::factory()->create();

    $this->actingAs($author)->get(route('admin.tags.index'))->assertForbidden();
    $this->actingAs($author)->delete(route('admin.tags.destroy', $tag))->assertForbidden();

    $this->assertDatabaseHas('tags', ['id' => $tag->id]);
});
