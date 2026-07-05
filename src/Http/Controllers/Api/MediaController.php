<?php

namespace BlogCms\Http\Controllers\Api;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPost;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function uploadCover(Request $request, BlogPost $post)
    {
        $request->validate([
            'cover_desktop' => 'nullable|image|max:5120',
            'cover_mobile' => 'nullable|image|max:5120',
        ]);

        if (! $request->hasFile('cover_desktop') && ! $request->hasFile('cover_mobile')) {
            return response()->json(['error' => 'At least one cover image (cover_desktop or cover_mobile) is required.'], 422);
        }

        if ($request->hasFile('cover_desktop')) {
            $post->clearMediaCollection('cover_desktop');
            $post->addMediaFromRequest('cover_desktop')->toMediaCollection('cover_desktop');
        }

        if ($request->hasFile('cover_mobile')) {
            $post->clearMediaCollection('cover_mobile');
            $post->addMediaFromRequest('cover_mobile')->toMediaCollection('cover_mobile');
        }

        return response()->json([
            'data' => [
                'cover_desktop_url' => $post->getFirstMediaUrl('cover_desktop'),
                'cover_mobile_url' => $post->getFirstMediaUrl('cover_mobile'),
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        $file = $request->file('image');
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $path = $file->storeAs('blog-images', $filename, 'public');

        return response()->json([
            'data' => [
                'path' => '/storage/' . $path,
                'url' => asset('storage/' . $path),
            ],
        ], 201);
    }
}
