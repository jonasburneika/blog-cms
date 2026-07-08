<?php

namespace BlogCms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validation contract for the tokenized guest-article submission form. */
class GuestArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is only registered when guest blogging is enabled
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:100000',
            'excerpt' => 'nullable|string|max:1000',
            'cover' => 'nullable|image|max:8192',
        ];
    }
}
