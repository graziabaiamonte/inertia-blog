<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Tags/Index', [
            'tags' => Tag::withCount('posts')->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Tags/Form');
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        Tag::create($request->validated());

        return redirect()->route('admin.tags.index')
            ->with('success', __('Tag created.'));
    }

    public function edit(Tag $tag): Response
    {
        return Inertia::render('Admin/Tags/Form', [
            'tag' => $tag->only(['id', 'name', 'slug']),
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        return redirect()->route('admin.tags.index')
            ->with('success', __('Tag updated.'));
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()->route('admin.tags.index')
            ->with('success', __('Tag deleted.'));
    }
}
