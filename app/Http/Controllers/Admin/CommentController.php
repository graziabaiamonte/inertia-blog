<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    /**
     * List comments for moderation (pending first).
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Comments/Index', [
            'comments' => Comment::with('post:id,title,slug')
                ->orderBy('approved')
                ->latest()
                ->paginate(20)
                ->through(fn (Comment $comment) => [
                    'id' => $comment->id,
                    'author_name' => $comment->author_name,
                    'author_email' => $comment->author_email,
                    'body' => $comment->body,
                    'approved' => $comment->approved,
                    'created_at' => $comment->created_at?->toIso8601String(),
                    'post' => $comment->post
                        ? ['title' => $comment->post->title, 'slug' => $comment->post->slug]
                        : null,
                ]),
        ]);
    }

    /**
     * Approve a pending comment.
     */
    public function approve(Comment $comment): RedirectResponse
    {
        $comment->update(['approved' => true]);

        return back()->with('success', __('Comment approved.'));
    }

    /**
     * Delete a comment.
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('success', __('Comment deleted.'));
    }
}
