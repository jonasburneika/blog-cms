<?php

namespace BlogCms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validation contract for the public guest-pitch form. */
class GuestPitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is only registered when guest blogging is enabled
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'proposed_topic' => 'required|string|max:255',
            'category_preference' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'sample_url' => 'nullable|url|max:2048',
        ];
    }
}
