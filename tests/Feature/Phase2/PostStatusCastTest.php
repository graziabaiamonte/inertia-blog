<?php

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Carbon;

it('casts the status attribute to a PostStatus instance', function () {
    $post = Post::factory()->published()->create();

    expect($post->fresh()->status)->toBeInstanceOf(PostStatus::class)
        ->and($post->fresh()->status)->toBe(PostStatus::Published);
});

it('defaults a new post to the Draft status', function () {
    $post = Post::create([
        'user_id' => User::factory()->create()->id,
        'title' => 'A brand new post',
        'body' => 'Some body content.',
    ]);

    expect($post->fresh()->status)->toBe(PostStatus::Draft);
});

it('casts published_at to a datetime', function () {
    $post = Post::factory()->published()->create();

    expect($post->fresh()->published_at)->toBeInstanceOf(Carbon::class);
});
