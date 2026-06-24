<?php

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
});

it('rejects a post without a title', function () {
    $this->actingAs(author())->post(route('admin.posts.store'), [
        'title' => '',
        'body' => 'Body',
        'status' => PostStatus::Draft->value,
    ])->assertSessionHasErrors('title');
});

it('rejects a post with an invalid status enum', function () {
    $this->actingAs(author())->post(route('admin.posts.store'), [
        'title' => 'Title',
        'body' => 'Body',
        'status' => 'archived',
    ])->assertSessionHasErrors('status');
});

it('rejects a post with a non-existent category', function () {
    $this->actingAs(author())->post(route('admin.posts.store'), [
        'title' => 'Title',
        'body' => 'Body',
        'status' => PostStatus::Draft->value,
        'category_id' => 9999,
    ])->assertSessionHasErrors('category_id');
});

it('rejects a duplicate post slug', function () {
    $author = author();
    Post::factory()->for($author)->create(['slug' => 'taken']);

    $this->actingAs($author)->post(route('admin.posts.store'), [
        'title' => 'Title',
        'slug' => 'taken',
        'body' => 'Body',
        'status' => PostStatus::Draft->value,
    ])->assertSessionHasErrors('slug');
});

it('rejects a category without a name', function () {
    $this->actingAs(admin())->post(route('admin.categories.store'), [
        'name' => '',
    ])->assertSessionHasErrors('name');
});

it('rejects a duplicate category slug', function () {
    Category::factory()->create(['slug' => 'php']);

    $this->actingAs(admin())->post(route('admin.categories.store'), [
        'name' => 'PHP',
        'slug' => 'php',
    ])->assertSessionHasErrors('slug');
});

it('rejects a tag without a name', function () {
    $this->actingAs(admin())->post(route('admin.tags.store'), [
        'name' => '',
    ])->assertSessionHasErrors('name');
});

it('rejects a duplicate tag slug', function () {
    Tag::factory()->create(['slug' => 'laravel']);

    $this->actingAs(admin())->post(route('admin.tags.store'), [
        'name' => 'Laravel',
        'slug' => 'laravel',
    ])->assertSessionHasErrors('slug');
});
