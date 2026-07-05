<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\GuestBlogRequest;
use Illuminate\Http\Request;

class GuestBlogRequestController extends Controller
{
    public function index(Request $request)
    {
        $guestRequests = GuestBlogRequest::query()
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('blog::admin.guest-requests.index', compact('guestRequests'));
    }

    public function show(GuestBlogRequest $guestBlogRequest)
    {
        return view('blog::admin.guest-requests.show', compact('guestBlogRequest'));
    }

    public function approve(Request $request, GuestBlogRequest $guestBlogRequest)
    {
        $guestBlogRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.guest-requests.index')
            ->with('success', 'Guest blog request approved successfully.');
    }

    public function reject(Request $request, GuestBlogRequest $guestBlogRequest)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        $guestBlogRequest->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.guest-requests.index')
            ->with('success', 'Guest blog request rejected.');
    }

    public function destroy(GuestBlogRequest $guestBlogRequest)
    {
        $guestBlogRequest->delete();

        return redirect()->route('admin.guest-requests.index')
            ->with('success', 'Guest blog request deleted successfully.');
    }
}
