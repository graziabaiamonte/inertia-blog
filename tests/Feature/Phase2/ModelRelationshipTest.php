<?php

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

it('auto-generates a post slug from the title', function () {
    $post = Post::factory()->create(['title' => 'My First Blog Post', 'slug' => null]);

    expect($post->slug)->toBe('my-first-blog-post');
});

it('auto-generates category and tag slugs', function () {
    $category = Category::factory()->create(['name' => 'Web Development', 'slug' => null]);
    $tag = Tag::factory()->create(['name' => 'Laravel Tips', 'slug' => null]);

    expect($category->slug)->toBe('web-development')
        ->and($tag->slug)->toBe('laravel-tips');
});

it('uses the slug for route-model binding', function () {
    expect((new Post)->getRouteKeyName())->toBe('slug')
        ->and((new Category)->getRouteKeyName())->toBe('slug')
        ->and((new Tag)->getRouteKeyName())->toBe('slug');
});

it('only returns published posts via the published scope', function () {
    Post::factory()->published()->count(3)->create();
    Post::factory()->draft()->count(2)->create();
    Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->addDay()]);

    expect(Post::published()->count())->toBe(3);
});

it('only returns approved comments via the approved scope', function () {
    Comment::factory()->approved()->count(4)->create();
    Comment::factory()->pending()->count(3)->create();

    expect(Comment::approved()->count())->toBe(4);
});

it('relates a post to its author, category, tags and comments', function () {
    $post = Post::factory()->create();
    $post->tags()->attach(Tag::factory()->count(2)->create());
    Comment::factory()->count(3)->for($post)->create();

    expect($post->user)->toBeInstanceOf(User::class)
        ->and($post->category)->toBeInstanceOf(Category::class)
        ->and($post->tags)->toHaveCount(2)
        ->and($post->comments)->toHaveCount(3);
});

it('relates a user to their posts', function () {
    $user = User::factory()->create();
    Post::factory()->count(2)->for($user)->create();

    expect($user->posts)->toHaveCount(2);
});
