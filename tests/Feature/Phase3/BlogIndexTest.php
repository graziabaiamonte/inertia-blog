<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->withoutVite();
});

it('lists only published posts on the blog index', function () {
    /** @var TestCase $this */
    Post::factory()->published()->count(3)->create();
    Post::factory()->draft()->count(2)->create();

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Index', false)
            ->has('posts.data', 3)
        );
});

it('filters posts by category slug', function () {
    /** @var TestCase $this */
    $category = Category::factory()->create(['slug' => 'laravel']);
    Post::factory()->published()->for($category)->count(2)->create();
    Post::factory()->published()->count(3)->create();

    $this->get(route('blog.index', ['filter' => ['category' => 'laravel']]))
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
});

it('filters posts by tag slug', function () {
    /** @var TestCase $this */
    $tag = Tag::factory()->create(['slug' => 'php']);
    $tagged = Post::factory()->published()->count(2)->create();
    $tagged->each(fn (Post $post) => $post->tags()->attach($tag));
    Post::factory()->published()->count(3)->create();

    $this->get(route('blog.index', ['filter' => ['tag' => 'php']]))
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
});

it('filters posts by search term', function () {
    /** @var TestCase $this */
    Post::factory()->published()->create(['title' => 'Mastering Eloquent Relationships']);
    Post::factory()->published()->create(['title' => 'A totally unrelated topic']);

    $this->get(route('blog.index', ['filter' => ['search' => 'Eloquent']]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Mastering Eloquent Relationships')
        );
});

it('sorts posts by title when requested', function () {
    /** @var TestCase $this */
    Post::factory()->published()->create(['title' => 'Zebra']);
    Post::factory()->published()->create(['title' => 'Alpha']);

    $this->get(route('blog.index', ['sort' => 'title']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('posts.data.0.title', 'Alpha')
            ->where('posts.data.1.title', 'Zebra')
        );
});

it('ignores disallowed sort and filter params', function () {
    /** @var TestCase $this */
    Post::factory()->published()->count(2)->create();

    $this->get(route('blog.index', ['sort' => 'body', 'filter' => ['status' => 'draft']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
});
