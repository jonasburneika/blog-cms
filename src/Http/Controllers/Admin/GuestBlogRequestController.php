<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\GuestBlogRequest;
use BlogCms\Services\GuestBlogService;
use Illuminate\Http\Request;

class GuestBlogRequestController extends Controller
{
    public function __construct(protected GuestBlogService $service) {}

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
        // The view reads $guestRequest (matches the index's naming).
        return view('blog::admin.guest-requests.show', ['guestRequest' => $guestBlogRequest]);
    }

    public function approve(Request $request, GuestBlogRequest $guestBlogRequest)
    {
        // Mints the tokenized submit link + notifies the guest (idempotent).
        $this->service->approve($guestBlogRequest, $request->user()->id);

        return redirect()->route('admin.guest-requests.index')
            ->with('success', 'Pitch approved — the guest has been emailed a link to submit their article.');
    }

    public function reject(Request $request, GuestBlogRequest $guestBlogRequest)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        $this->service->reject($guestBlogRequest, $request->user()->id, $validated['admin_notes'] ?? null);

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
