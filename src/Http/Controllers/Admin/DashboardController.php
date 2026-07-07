<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogCategory;
use BlogCms\Models\BlogPost;
use BlogCms\Models\GuestBlogRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $totalPosts = BlogPost::count();
        $publishedPosts = BlogPost::where('status', BlogPost::STATUS_PUBLISHED)->count();
        $totalCategories = BlogCategory::count();
        $pendingRequests = GuestBlogRequest::where('status', 'pending')->count();

        $recentPosts = BlogPost::with('category')->latest()->take(5)->get();
        $recentGuestRequests = GuestBlogRequest::latest()->take(5)->get();

        return view('blog::admin.dashboard', compact(
            'user',
            'totalPosts',
            'publishedPosts',
            'totalCategories',
            'pendingRequests',
            'recentPosts',
            'recentGuestRequests',
        ));
    }
}
