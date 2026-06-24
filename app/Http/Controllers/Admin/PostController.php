<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollection;
use App\Enums\PermissionName;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * List posts — own posts for authors, all posts for admins.
     */
    public function index(Request $request): Response
    {
        $query = Post::query()->with(['category:id,name', 'user:id,name'])->latest();

        if (! $this->managesAllPosts($request)) {
            $query->where('user_id', $request->user()->id);
        }

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $query->paginate(15)->through(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status->value,
                'author' => $post->user?->name,
                'category' => $post->category?->name,
                'published_at' => $post->published_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Show the form for creating a new post.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Posts/Form', $this->formData());
    }

    /**
     * Store a newly created post.
     */
    public function store(StorePostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? [];
        unset($data['tags'], $data['featured_image']);

        $data['user_id'] = $request->user()->id;
        $data = $this->withPublishedAt($data);

        $post = Post::create($data);
        $post->tags()->sync($tags);

        if ($request->hasFile('featured_image')) {
            $post->addMediaFromRequest('featured_image')
                ->toMediaCollection(MediaCollection::Featured->value);
        }

        return redirect()->route('admin.posts.index')
            ->with('success', __('Post created.'));
    }

    /**
     * Show the form for editing the given post.
     */
    public function edit(Request $request, Post $post): Response
    {
        abort_unless($this->canManage($request, $post), 403);

        $post->load('tags:id');

        return Inertia::render('Admin/Posts/Form', [
            ...$this->formData(),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'category_id' => $post->category_id,
                'status' => $post->status->value,
                'published_at' => $post->published_at?->toIso8601String(),
                'tags' => $post->tags->pluck('id'),
                'featured_image' => $post->getFirstMediaUrl(MediaCollection::Featured->value) ?: null,
            ],
        ]);
    }

    /**
     * Update the given post.
     */
    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? [];
        unset($data['tags'], $data['featured_image']);

        $data = $this->withPublishedAt($data);

        $post->update();
        $post->tags()->sync($tags);

        if ($request->hasFile('featured_image')) {
            $post->addMediaFromRequest('featured_image')
                ->toMediaCollection(MediaCollection::Featured->value);
        }

        return redirect()->route('admin.posts.index')
            ->with('success', __('Post updated.'));
    }

    /**
     * Remove the given post.
     */
    public function destroy(Request $request, Post $post): RedirectResponse
    {
        abort_unless($this->canManage($request, $post), 403);

        $post->delete($post);

        return redirect()->route('admin.posts.index')
            ->with('success', __('Post deleted.'));
    }

    /**
     * Shared data for the create/edit form.
     *
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'categories' => Category::orderByDesc('name')->get(['id', 'name']),
            'tags' => Tag::orderByDesc('name')->get(['id', 'name']),
            'statuses' => collect(PostStatus::cases())->map(fn (PostStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ];
    }

    /**
     * Default `published_at` to now when publishing without an explicit date.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withPublishedAt(array $data): array
    {
        if (($data['status'] ?? null) === PostStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    /**
     * Whether the current user manages every post (admin).
     */
    protected function managesAllPosts(Request $request): bool
    {
        return (bool) $request->user()?->can(PermissionName::ManageAllPosts->value);
    }

    /**
     * Whether the current user may manage the given post.
     */
    protected function canManage(Request $request, Post $post): bool
    {
        $user = $request->user();

        return $user !== null
            && ($this->managesAllPosts($request) || $post->user_id === $user->id);
    }
}
