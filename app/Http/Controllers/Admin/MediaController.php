<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    /**
     * Upload an inline content image for a post and return its URL.
     *
     * Consumed by the frontend Markdown editor via the Axios instance.
     */
    public function store(StoreMediaRequest $request, Post $post): JsonResponse
    {
        $media = $post->addMediaFromRequest('image')
            ->toMediaCollection(MediaCollection::Content->value);

        return response()->json([
            'uuid' => $media->uuid,
            'url' => $media->getUrl(),
        ], 201);
    }
}
