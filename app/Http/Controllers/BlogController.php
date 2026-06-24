<?php

namespace App\Http\Controllers;

use App\Enums\MediaCollection;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Support\Markdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BlogController extends Controller
{
    /**
     * Display a paginated, filterable list of published posts.
     */
    public function index(Request $request): Response
    {
        $posts = QueryBuilder::for(Post::published())
            ->allowedFilters(
                AllowedFilter::callback('category', function (Builder $query, string $value): void {
                    $query->whereHas('category', fn (Builder $q) => $q->where('slug', $value));
                }),
                AllowedFilter::callback('tag', function (Builder $query, string $value): void {
                    $query->whereHas('tags', fn (Builder $q) => $q->where('slug', $value));
                }),
                AllowedFilter::callback('search', function (Builder $query, string $value): void {
                    $query->where(function (Builder $q) use ($value): void {
                        $q->where('title', 'like', "%{$value}%")
                            ->orWhere('excerpt', 'like', "%{$value}%")
                            ->orWhere('body', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts('published_at', 'title')
            ->defaultSort('-published_at')
            ->with(['user:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('Blog/Index', [
            'posts' => $posts->through(fn (Post $post) => $this->toListItem($post)),
            'filters' => $request->query('filter', []),
            'sort' => $request->query('sort'),
            'categories' => Category::orderByDesc('name')->get(['name', 'slug']),
            'tags' => Tag::query()->orderByDesc('name')->get(['name', 'slug']),
        ]);
    }

    /**
     * Display a single published post with its approved comments.
     */
    public function show(Post $post): Response
    {
        abort_unless($this->isPublished($post), 404);

        $post->load([
            'user:id,name',
            'category:id,name,slug',
            'tags:id,name,slug',
            'comments' => fn ($query) => $query->approved()->latest(),
        ]);

        return Inertia::render('Blog/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'body_html' => Markdown::toHtml($post->body),
                'published_at' => $post->published_at?->toIso8601String(),
                'author' => $post->user?->name,
                'category' => $post->category
                    ? ['name' => $post->category->name, 'slug' => $post->category->slug]
                    : null,
                'tags' => $post->tags->map(fn (Tag $tag) => [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ]),
                'featured_image' => $post->getFirstMediaUrl(MediaCollection::Featured->value) ?: null,
                'comments' => $post->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'author_name' => $comment->author_name,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Map a post to its list-item representation.
     *
     * @return array<string, mixed>
     */
    protected function toListItem(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'published_at' => $post->published_at?->toIso8601String(),
            'author' => $post->user?->name,
            'category' => $post->category
                ? ['name' => $post->category->name, 'slug' => $post->category->slug]
                : null,
            'tags' => $post->tags->map(fn (Tag $tag) => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]),
            'featured_image' => $post->getFirstMediaUrl(MediaCollection::Featured->value, 'thumb') ?: null,
        ];
    }

    /**
     * Determine whether a post is publicly visible.
     */
    protected function isPublished(Post $post): bool
    {
        return Post::published()->whereKey($post->getKey())->exists();
    }
}
