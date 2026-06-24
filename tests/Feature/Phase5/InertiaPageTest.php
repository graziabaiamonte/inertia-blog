<?php

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
});

// ─── Shared props ───────────────────────────────────────────────────────────

it('shares locale and translations with every page response', function () {
    /** @var TestCase $this */
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('locale')
            ->has('translations')
        );
});

it('shares auth.roles for authenticated users', function () {
    /** @var TestCase $this */
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole(RoleName::Author);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('auth.roles')
            ->where('auth.roles', ['author'])
        );
});

it('shares admin role for admin users', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(RoleName::Admin);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.roles', ['admin'])
        );
});

// ─── Blog/Index ─────────────────────────────────────────────────────────────

it('renders Blog/Index with required props', function () {
    /** @var TestCase $this */
    Post::factory()->published()->count(2)->create();

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Index', false)
            ->has('posts')
            ->has('posts.data')
            ->has('posts.links')
            ->has('filters')
            ->has('sort')
            ->has('categories')
            ->has('tags')
        );
});

// ─── Blog/Show ──────────────────────────────────────────────────────────────

it('renders Blog/Show with correct post props', function () {
    /** @var TestCase $this */
    $post = Post::factory()->published()->create(['title' => 'Hello World']);

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Show', false)
            ->has('post')
            ->where('post.title', 'Hello World')
            ->has('post.body_html')
            ->has('post.comments')
        );
});

it('returns 404 for a draft post on blog show', function () {
    /** @var TestCase $this */
    $post = Post::factory()->draft()->create();

    $this->get(route('blog.show', $post->slug))
        ->assertNotFound();
});

// ─── Dashboard ──────────────────────────────────────────────────────────────

it('renders Dashboard with posts prop for authenticated user', function () {
    /** @var TestCase $this */
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole(RoleName::Author);
    Post::factory()->for($user)->count(2)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard', false)
            ->has('posts')
            ->has('posts.data')
        );
});

// ─── Admin/Posts/Index ───────────────────────────────────────────────────────

it('renders Admin/Posts/Index for author showing only their posts', function () {
    /** @var TestCase $this */
    $author = User::factory()->create(['email_verified_at' => now()]);
    $author->assignRole(RoleName::Author);
    Post::factory()->for($author)->count(3)->create();
    Post::factory()->count(2)->create(); // other user's posts

    $this->actingAs($author)
        ->get(route('admin.posts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Posts/Index', false)
            ->has('posts.data', 3)
        );
});

it('renders Admin/Posts/Index for admin showing all posts', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(RoleName::Admin);
    Post::factory()->count(5)->create();

    $this->actingAs($admin)
        ->get(route('admin.posts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Posts/Index', false)
            ->has('posts.data', 5)
        );
});

// ─── Admin/Posts/Form (create) ───────────────────────────────────────────────

it('renders Admin/Posts/Form for create with required props', function () {
    /** @var TestCase $this */
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole(RoleName::Author);

    Category::factory()->count(2)->create();
    Tag::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('admin.posts.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Posts/Form', false)
            ->has('categories', 2)
            ->has('tags', 3)
            ->has('statuses')
            ->missing('post')
        );
});

// ─── Admin/Posts/Form (edit) ─────────────────────────────────────────────────

it('renders Admin/Posts/Form for edit with post prop', function () {
    /** @var TestCase $this */
    $author = User::factory()->create(['email_verified_at' => now()]);
    $author->assignRole(RoleName::Author);
    $post = Post::factory()->for($author)->create(['title' => 'My Post']);

    $this->actingAs($author)
        ->get(route('admin.posts.edit', $post->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Posts/Form', false)
            ->has('post')
            ->where('post.id', $post->id)
            ->where('post.title', 'My Post')
            ->has('post.body')
            ->has('post.tags')
            ->has('categories')
            ->has('tags')
            ->has('statuses')
        );
});

it('returns 403 when author tries to edit another authors post', function () {
    /** @var TestCase $this */
    $author = User::factory()->create(['email_verified_at' => now()]);
    $author->assignRole(RoleName::Author);
    $post = Post::factory()->create(); // different owner

    $this->actingAs($author)
        ->get(route('admin.posts.edit', $post->slug))
        ->assertForbidden();
});

// ─── Admin/Categories/Index ───────────────────────────────────────────────────

it('renders Admin/Categories/Index for admin', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(RoleName::Admin);
    Category::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Categories/Index', false)
            ->has('categories', 3)
        );
});

it('denies author from accessing categories index', function () {
    /** @var TestCase $this */
    $author = User::factory()->create(['email_verified_at' => now()]);
    $author->assignRole(RoleName::Author);

    $this->actingAs($author)
        ->get(route('admin.categories.index'))
        ->assertForbidden();
});

// ─── Admin/Tags/Index ─────────────────────────────────────────────────────────

it('renders Admin/Tags/Index for admin', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(RoleName::Admin);
    Tag::factory()->count(4)->create();

    $this->actingAs($admin)
        ->get(route('admin.tags.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Tags/Index', false)
            ->has('tags', 4)
        );
});

// ─── Admin/Comments/Index ─────────────────────────────────────────────────────

it('renders Admin/Comments/Index for admin with pending first', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(RoleName::Admin);
    $post = Post::factory()->published()->create();
    Comment::factory()->for($post)->count(2)->create(['approved' => false]);
    Comment::factory()->for($post)->count(1)->create(['approved' => true]);

    $this->actingAs($admin)
        ->get(route('admin.comments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Comments/Index', false)
            ->has('comments.data', 3)
        );
});
