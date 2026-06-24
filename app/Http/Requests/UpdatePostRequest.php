<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    /**
     * Admins may edit any post; authors may edit their own.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $post = $this->route('post');

        if (! $user || ! $post instanceof Post) {
            return false;
        }

        if ($user->can(PermissionName::ManageAllPosts->value)) {
            return true;
        }

        return $post->user_id === $user->id
            && $user->can(PermissionName::EditOwnPosts->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Post $post */
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(Post::class, 'slug')->ignore($post->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', Rule::exists('tags', 'id')],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
