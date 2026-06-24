<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount('posts')->orderBy('name')->get([
                'id', 'name', 'slug', 'description',
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Form');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', __('Category created.'));
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => $category->only(['id', 'name', 'slug', 'description']),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', __('Category updated.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete($category);

        return redirect()->route('admin.categories.index')
            ->with('success', __('Category deleted.'));
    }
}
