<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Guest commenting is open to everyone; comments are created unapproved
     * and surfaced only after moderation.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['required', 'string', 'email', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
