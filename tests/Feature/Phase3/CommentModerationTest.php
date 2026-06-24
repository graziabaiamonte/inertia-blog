<?php

use App\Models\Comment;
use App\Models\Post;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
});

it('lets an admin list comments for moderation', function () {
    Comment::factory()->for(Post::factory())->count(3)->create();

    $this->actingAs(admin())
        ->get(route('admin.comments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('comments.data', 3));
});

it('lets an admin approve a pending comment', function () {
    $comment = Comment::factory()->for(Post::factory())->create(['approved' => false]);

    $this->actingAs(admin())
        ->patch(route('admin.comments.approve', $comment))
        ->assertRedirect();

    expect($comment->fresh()->approved)->toBeTrue();
});

it('lets an admin delete a comment', function () {
    $comment = Comment::factory()->for(Post::factory())->create();

    $this->actingAs(admin())
        ->delete(route('admin.comments.destroy', $comment))
        ->assertRedirect();

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

it('forbids an author from moderating comments', function () {
    $comment = Comment::factory()->for(Post::factory())->create(['approved' => false]);

    $this->actingAs(author())
        ->patch(route('admin.comments.approve', $comment))
        ->assertForbidden();

    expect($comment->fresh()->approved)->toBeFalse();
});
