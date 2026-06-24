<?php

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
});

it('lets an author create a post they own', function () {
    $author = author();
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->post(route('admin.posts.store'), [
            'title' => 'My First Post',
            'body' => 'Hello world',
            'status' => PostStatus::Draft->value,
            'category_id' => $category->id,
        ])
        ->assertRedirect(route('admin.posts.index'));

    $this->assertDatabaseHas('posts', [
        'title' => 'My First Post',
        'user_id' => $author->id,
        'status' => PostStatus::Draft->value,
    ]);
});

it('sets published_at when an author publishes a post', function () {
    $author = author();

    $this->actingAs($author)->post(route('admin.posts.store'), [
        'title' => 'Published Post',
        'body' => 'Body',
        'status' => PostStatus::Published->value,
    ])->assertRedirect();

    $post = Post::firstWhere('title', 'Published Post');
    expect($post->status)->toBe(PostStatus::Published)
        ->and($post->published_at)->not->toBeNull();
});

it('lets an author update their own post', function () {
    $author = author();
    $post = Post::factory()->for($author)->draft()->create();

    $this->actingAs($author)->put(route('admin.posts.update', $post), [
        'title' => 'Updated Title',
        'body' => $post->body,
        'status' => PostStatus::Draft->value,
    ])->assertRedirect(route('admin.posts.index'));

    expect($post->fresh()->title)->toBe('Updated Title');
});

it('forbids an author from updating another author post', function () {
    $owner = author();
    $intruder = author();
    $post = Post::factory()->for($owner)->create();

    $this->actingAs($intruder)->put(route('admin.posts.update', $post), [
        'title' => 'Hijacked',
        'body' => 'x',
        'status' => PostStatus::Draft->value,
    ])->assertForbidden();

    expect($post->fresh()->title)->not->toBe('Hijacked');
});

it('forbids an author from deleting another author post', function () {
    $owner = author();
    $intruder = author();
    $post = Post::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->delete(route('admin.posts.destroy', $post))
        ->assertForbidden();

    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

it('lets an admin update any post', function () {
    $author = author();
    $admin = admin();
    $post = Post::factory()->for($author)->create();

    $this->actingAs($admin)->put(route('admin.posts.update', $post), [
        'title' => 'Admin Edited',
        'body' => $post->body,
        'status' => PostStatus::Published->value,
    ])->assertRedirect();

    expect($post->fresh()->title)->toBe('Admin Edited');
});

it('lets an admin delete any post', function () {
    $post = Post::factory()->for(author())->create();

    $this->actingAs(admin())
        ->delete(route('admin.posts.destroy', $post))
        ->assertRedirect();

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('scopes the author post index to their own posts', function () {
    $author = author();
    Post::factory()->for($author)->count(2)->create();
    Post::factory()->for(author())->count(3)->create();

    $this->actingAs($author)
        ->get(route('admin.posts.index'))
        ->assertInertia(fn ($page) => $page->has('posts.data', 2));
});

it('shows the admin every post in the index', function () {
    Post::factory()->for(author())->count(2)->create();
    Post::factory()->for(author())->count(3)->create();

    $this->actingAs(admin())
        ->get(route('admin.posts.index'))
        ->assertInertia(fn ($page) => $page->has('posts.data', 5));
});
