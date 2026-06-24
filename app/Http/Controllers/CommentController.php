<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    /**
     * Store a guest comment for a published post (unapproved, pending moderation).
     */
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        abort_unless(Post::published()->whereKey($post->getKey())->exists(), 404);

        $post->comments()->create([
            ...$request->validated(),
            'approved' => false,
        ]);

        return back()->with('success', __('Your comment has been submitted and is awaiting moderation.'));
    }
}
