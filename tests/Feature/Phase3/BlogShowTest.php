<?php

use App\Models\Comment;
use App\Models\Post;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->withoutVite();
});

it('shows a published post with rendered markdown body', function () {
    $post = Post::factory()->published()->create([
        'body' => "# Heading\n\nSome **bold** text.",
    ]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Show', false)
            ->where('post.slug', $post->slug)
            ->where('post.body_html', fn (string $html) => str_contains($html, '<h1>')
                && str_contains($html, '<strong>bold</strong>'))
        );
});

it('strips embedded html from the markdown body', function () {
    $post = Post::factory()->published()->create([
        'body' => 'Hello <script>alert(1)</script> world',
    ]);

    $this->get(route('blog.show', $post))
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.body_html', fn (string $html) => ! str_contains($html, '<script>'))
        );
});

it('returns 404 for a draft post', function () {
    $post = Post::factory()->draft()->create();

    $this->get(route('blog.show', $post))->assertNotFound();
});

it('only includes approved comments', function () {
    $post = Post::factory()->published()->create();
    Comment::factory()->for($post)->create(['approved' => true, 'author_name' => 'Visible']);
    Comment::factory()->for($post)->create(['approved' => false, 'author_name' => 'Hidden']);

    $this->get(route('blog.show', $post))
        ->assertInertia(fn (Assert $page) => $page
            ->has('post.comments', 1)
            ->where('post.comments.0.author_name', 'Visible')
        );
});
