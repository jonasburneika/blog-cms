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
        $stats = [
            'total_posts' => BlogPost::count(),
            'total_categories' => BlogCategory::count(),
            'pending_guest_requests' => GuestBlogRequest::where('status', 'pending')->count(),
            'published_posts' => BlogPost::where('status', BlogPost::STATUS_PUBLISHED)->count(),
        ];

        return view('blog::admin.dashboard', compact('stats'));
    }
}
