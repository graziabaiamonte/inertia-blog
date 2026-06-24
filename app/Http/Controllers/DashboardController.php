<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $posts = $user->hasRole(RoleName::Admin->value)
            ? Post::with('user')->latest()->paginate(10)
            : Post::with('user')->where('user_id', $user->id)->latest()->paginate(10);

        return Inertia::render('Dashboard', [
            'posts' => $posts,
        ]);
    }
}
