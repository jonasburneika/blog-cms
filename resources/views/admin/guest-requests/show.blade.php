@extends('blog::layouts.admin')

@section('page-title', 'Guest Request Details')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.guest-requests.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Guest Requests</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Details</span>
@endsection

@section('content')
    <div x-data="{ showRejectModal: false }">
        <div class="max-w-3xl mx-auto">
            {{-- Back Link --}}
            <div class="mb-6">
                <a href="{{ route('admin.guest-requests.index') }}"
                   class="inline-flex items-center text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Guest Requests
                </a>
            </div>

            {{-- Request Details Card --}}
            <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 overflow-hidden">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-warm-200 dark:border-neutral-700 flex items-center justify-between">
                    <h1 class="text-xl font-bold text-stone-800 dark:text-stone-200">Guest Request Details</h1>
                    @if($guestRequest->status === 'pending')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Pending</span>
                    @elseif($guestRequest->status === 'approved')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Approved</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Rejected</span>
                    @endif
                </div>

                {{-- Details --}}
                <div class="p-6 space-y-6">
                    {{-- Contact Info --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-1">Name</label>
                            <p class="text-stone-800 dark:text-stone-200 font-medium">{{ $guestRequest->name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-1">Email</label>
                            <p class="text-stone-800 dark:text-stone-200">
                                <a href="mailto:{{ $guestRequest->email }}" class="hover:text-amber-600 dark:hover:text-amber-400 transition">{{ $guestRequest->email }}</a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-1">Phone</label>
                            <p class="text-stone-800 dark:text-stone-200">{{ $guestRequest->phone ?? '-' }}</p>
                        </div>
                    </div>

                    <hr class="border-warm-200 dark:border-neutral-700">

                    {{-- Topic Info --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-1">Proposed Topic</label>
                            <p class="text-stone-800 dark:text-stone-200 font-medium">{{ $guestRequest->proposed_topic }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-1">Category Preference</label>
                            <p class="text-stone-800 dark:text-stone-200">{{ $guestRequest->category_preference ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Message --}}
                    <div>
                        <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-2">Message</label>
                        <div class="bg-warm-50 dark:bg-neutral-900 border border-warm-200 dark:border-neutral-700 rounded-lg p-4">
                            <p class="text-stone-700 dark:text-stone-300 whitespace-pre-wrap leading-relaxed">{{ $guestRequest->message }}</p>
                        </div>
                    </div>

                    {{-- Sample URL --}}
                    @if($guestRequest->sample_url)
                        <div>
                            <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-1">Sample URL</label>
                            <a href="{{ $guestRequest->sample_url }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">
                                {{ $guestRequest->sample_url }}
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    @endif

                    {{-- Guest submission status (flow B) --}}
                    @if($guestRequest->awaitingSubmission())
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                            <label class="block text-xs font-medium text-amber-700 dark:text-amber-300 uppercase tracking-wider mb-1">Awaiting article</label>
                            <p class="text-sm text-amber-800 dark:text-amber-300 mb-2">The guest has been emailed this private submission link:</p>
                            <code class="block px-3 py-2 bg-white dark:bg-neutral-900 border border-amber-200 dark:border-amber-800 rounded text-xs font-mono break-all select-all">{{ $guestRequest->submitUrl() }}</code>
                        </div>
                    @elseif($guestRequest->hasDraft())
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 flex items-center justify-between gap-3">
                            <div>
                                <label class="block text-xs font-medium text-green-700 dark:text-green-300 uppercase tracking-wider mb-1">Draft created</label>
                                <p class="text-sm text-green-800 dark:text-green-300">Article submitted{{ $guestRequest->submitted_at ? ' '.$guestRequest->submitted_at->diffForHumans() : '' }} — review &amp; publish the draft.</p>
                            </div>
                            <a href="{{ route('admin.posts.edit', $guestRequest->created_post_id) }}"
                               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition whitespace-nowrap">
                                Open draft
                            </a>
                        </div>
                    @endif

                    {{-- Review Info --}}
                    @if($guestRequest->status !== 'pending')
                        <hr class="border-warm-200 dark:border-neutral-700">
                        <div class="bg-stone-50 dark:bg-neutral-900 border border-stone-200 dark:border-neutral-700 rounded-lg p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @if($guestRequest->reviewer)
                                    <div>
                                        <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-1">Reviewed By</label>
                                        <p class="text-stone-800 dark:text-stone-200">{{ $guestRequest->reviewer->name ?? 'Unknown' }}</p>
                                    </div>
                                @endif
                                @if($guestRequest->reviewed_at)
                                    <div>
                                        <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-1">Reviewed At</label>
                                        <p class="text-stone-800 dark:text-stone-200">{{ $guestRequest->reviewed_at->format('M d, Y \a\t g:i A') }}</p>
                                    </div>
                                @endif
                            </div>
                            @if($guestRequest->admin_notes)
                                <div class="mt-4">
                                    <label class="block text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-1">Admin Notes</label>
                                    <p class="text-stone-700 dark:text-stone-300">{{ $guestRequest->admin_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="px-6 py-4 bg-warm-50 dark:bg-neutral-850 border-t border-warm-200 dark:border-neutral-700">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <a href="{{ route('admin.guest-requests.index') }}"
                           class="text-sm text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 transition">
                            Back to list
                        </a>

                        <div class="flex items-center gap-3">
                            @if($guestRequest->status === 'pending')
                                {{-- Approve Button --}}
                                <form method="POST" action="{{ route('admin.guest-requests.approve', $guestRequest) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            class="inline-flex items-center px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Approve
                                    </button>
                                </form>

                                {{-- Reject Button --}}
                                <button type="button" @click="showRejectModal = true"
                                        class="inline-flex items-center px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Reject
                                </button>
                            @else
                                {{-- Delete --}}
                                <form method="POST" action="{{ route('admin.guest-requests.destroy', $guestRequest) }}"
                                      onsubmit="return confirm('Are you sure you want to delete this request?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete Request
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reject Modal --}}
        <div x-show="showRejectModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showRejectModal = false"></div>
                <div class="relative bg-white dark:bg-neutral-800 rounded-xl shadow-xl border border-warm-200 dark:border-neutral-700 max-w-md w-full p-6 z-10">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Reject Request</h3>
                    </div>
                    <p class="text-stone-600 dark:text-stone-400 mb-4">Please provide a reason for rejecting this guest blog request.</p>

                    <form method="POST" action="{{ route('admin.guest-requests.reject', $guestRequest) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-5">
                            <label for="admin_notes" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Admin Notes</label>
                            <textarea name="admin_notes" id="admin_notes" rows="4"
                                      class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                      placeholder="Reason for rejection (optional)"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="button" @click="showRejectModal = false"
                                    class="px-4 py-2 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition">
                                Reject Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
