<?php

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BlogController::class, 'index'])->name('blog.index');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin / author area
Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Posts — authors manage their own, admins manage all (enforced in requests).
        $authorOrAdmin = 'role:'.RoleName::Admin->value.'|'.RoleName::Author->value;

        Route::middleware($authorOrAdmin)->group(function () {
            Route::resource('posts', PostController::class)->except('show');
            Route::post('posts/{post}/media', [MediaController::class, 'store'])->name('posts.media.store');
        });

        // Taxonomy — admins only.
        Route::middleware('permission:'.PermissionName::ManageTaxonomy->value)->group(function () {
            Route::resource('categories', CategoryController::class)->except('show');
            Route::resource('tags', TagController::class)->except('show');
        });

        // Comment moderation — admins only.
        Route::middleware('permission:'.PermissionName::ModerateComments->value)->group(function () {
            Route::get('comments', [AdminCommentController::class, 'index'])->name('comments.index');
            Route::patch('comments/{comment}/approve', [AdminCommentController::class, 'approve'])->name('comments.approve');
            Route::delete('comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
        });
    });

require __DIR__.'/auth.php';

// Public single post + comments — wildcard at root, MUST be registered last
// so literal routes (/login, /dashboard, /admin/*, /up, etc.) take precedence.
Route::get('/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/{post:slug}/comments', [CommentController::class, 'store'])->name('comments.store');
