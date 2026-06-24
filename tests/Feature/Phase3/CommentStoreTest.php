<?php

use App\Models\Post;

it('stores a guest comment as unapproved', function () {
    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post), [
        'author_name' => 'Jane Guest',
        'author_email' => 'jane@example.com',
        'body' => 'Great article, thanks!',
    ])->assertRedirect();

    $this->assertDatabaseHas('comments', [
        'post_id' => $post->id,
        'author_name' => 'Jane Guest',
        'approved' => false,
    ]);
});

it('rejects a comment with missing fields', function () {
    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post), [
        'author_name' => '',
        'author_email' => 'not-an-email',
        'body' => '',
    ])->assertSessionHasErrors(['author_name', 'author_email', 'body']);

    $this->assertDatabaseCount('comments', 0);
});

it('does not allow commenting on a draft post', function () {
    $post = Post::factory()->draft()->create();

    $this->post(route('comments.store', $post), [
        'author_name' => 'Jane Guest',
        'author_email' => 'jane@example.com',
        'body' => 'Hello',
    ])->assertNotFound();
});
