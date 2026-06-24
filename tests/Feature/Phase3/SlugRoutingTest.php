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

it('admin can open post edit page via slug', function () {
    /** @var TestCase $this */
    $admin = admin();
    $post = Post::factory()->for($admin, 'user')->create(['status' => PostStatus::Draft]);

    $this->actingAs($admin)
        ->get(route('admin.posts.edit', $post->slug))
        ->assertOk();
});

it('admin can update a post via slug', function () {
    /** @var TestCase $this */
    $admin = admin();
    $post = Post::factory()->for($admin, 'user')->create(['status' => PostStatus::Draft]);

    $this->actingAs($admin)
        ->put(route('admin.posts.update', $post->slug), [
            'title' => 'Updated via slug',
            'body' => 'Updated body',
            'status' => PostStatus::Draft->value,
        ])
        ->assertRedirect();

    expect($post->fresh()->title)->toBe('Updated via slug');
});

it('admin can delete a post via slug', function () {
    /** @var TestCase $this */
    $admin = admin();
    $post = Post::factory()->for($admin, 'user')->create();

    $this->actingAs($admin)
        ->delete(route('admin.posts.destroy', $post->slug))
        ->assertRedirect();

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('author can edit and delete their own post via slug', function () {
    /** @var TestCase $this */
    $author = author();
    $post = Post::factory()->for($author, 'user')->create(['status' => PostStatus::Draft]);

    $this->actingAs($author)
        ->get(route('admin.posts.edit', $post->slug))
        ->assertOk();

    $this->actingAs($author)
        ->delete(route('admin.posts.destroy', $post->slug))
        ->assertRedirect();

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('author cannot delete another authors post via slug', function () {
    /** @var TestCase $this */
    $owner = author();
    $intruder = author();
    $post = Post::factory()->for($owner, 'user')->create();

    $this->actingAs($intruder)
        ->delete(route('admin.posts.destroy', $post->slug))
        ->assertForbidden();
});

it('admin can update a category via slug', function () {
    /** @var TestCase $this */
    $admin = admin();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.categories.update', $category->slug), ['name' => 'Slug Updated'])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Slug Updated');
});

it('admin can delete a category via slug', function () {
    /** @var TestCase $this */
    $admin = admin();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category->slug))
        ->assertRedirect();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('admin can update a tag via slug', function () {
    /** @var TestCase $this */
    $admin = admin();
    $tag = Tag::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.tags.update', $tag->slug), ['name' => 'Slug Tag Updated'])
        ->assertRedirect();

    expect($tag->fresh()->name)->toBe('Slug Tag Updated');
});

it('admin can delete a tag via slug', function () {
    /** @var TestCase $this */
    $admin = admin();
    $tag = Tag::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.tags.destroy', $tag->slug))
        ->assertRedirect();

    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});
