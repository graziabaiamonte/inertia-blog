<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    /**
     * Inline content images may be uploaded by whoever can edit the post.
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
