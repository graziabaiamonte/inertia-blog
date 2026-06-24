<?php

use App\Enums\MediaCollection;
use App\Enums\PostStatus;
use App\Models\Post;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
    Storage::fake('public');
});

it('attaches an uploaded featured image to the post', function () {
    /** @var TestCase $this */
    $author = author();

    $this->actingAs($author)->post(route('admin.posts.store'), [
        'title' => 'Post With Image',
        'body' => 'Body',
        'status' => PostStatus::Draft->value,
        'featured_image' => UploadedFile::fake()->image('cover.jpg', 800, 600),
    ])->assertRedirect();

    $post = Post::firstWhere('title', 'Post With Image');
    expect($post->getFirstMedia(MediaCollection::Featured->value))->not->toBeNull();
});

it('rejects a non-image featured upload', function () {
    /** @var TestCase $this */
    $author = author();

    $this->actingAs($author)->post(route('admin.posts.store'), [
        'title' => 'Bad Upload',
        'body' => 'Body',
        'status' => PostStatus::Draft->value,
        'featured_image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('featured_image');

    $this->assertDatabaseMissing('posts', ['title' => 'Bad Upload']);
});

it('rejects an oversized featured upload', function () {
    /** @var TestCase $this */
    $author = author();

    $this->actingAs($author)->post(route('admin.posts.store'), [
        'title' => 'Too Big',
        'body' => 'Body',
        'status' => PostStatus::Draft->value,
        'featured_image' => UploadedFile::fake()->image('huge.jpg')->size(5000),
    ])->assertSessionHasErrors('featured_image');
});

it('lets an author upload an inline content image to their post', function () {
    /** @var TestCase $this */
    $author = author();
    $post = Post::factory()->for($author)->create();

    $this->actingAs($author)
        ->post(route('admin.posts.media.store', $post), [
            'image' => UploadedFile::fake()->image('inline.png'),
        ])
        ->assertCreated()
        ->assertJsonStructure(['uuid', 'url']);

    expect($post->getMedia(MediaCollection::Content->value))->toHaveCount(1);
});

it('forbids uploading an inline image to another author post', function () {
    /** @var TestCase $this */
    $post = Post::factory()->for(author())->create();

    $this->actingAs(author())
        ->post(route('admin.posts.media.store', $post), [
            'image' => UploadedFile::fake()->image('inline.png'),
        ])
        ->assertForbidden();
});
