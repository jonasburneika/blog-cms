@extends('blog::layouts.admin')

@section('page-title', 'Guest Blog Requests')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Guest Blog Requests</span>
@endsection

@section('content')
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">Guest Blog Requests</h1>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="mb-6 border-b border-warm-200 dark:border-neutral-700">
        <nav class="flex gap-1 -mb-px">
            @php
                $currentStatus = request('status', 'all');
            @endphp
            <a href="{{ route('admin.guest-requests.index') }}"
               class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition {{ $currentStatus === 'all' ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600' }}">
                All
            </a>
            <a href="{{ route('admin.guest-requests.index', ['status' => 'pending']) }}"
               class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition {{ $currentStatus === 'pending' ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600' }}">
                Pending
            </a>
            <a href="{{ route('admin.guest-requests.index', ['status' => 'approved']) }}"
               class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition {{ $currentStatus === 'approved' ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600' }}">
                Approved
            </a>
            <a href="{{ route('admin.guest-requests.index', ['status' => 'rejected']) }}"
               class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition {{ $currentStatus === 'rejected' ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600' }}">
                Rejected
            </a>
        </nav>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-stone-500 dark:text-stone-400 bg-warm-50 dark:bg-neutral-850 border-b border-warm-200 dark:border-neutral-700">
                        <th class="px-6 py-3 font-medium">Name</th>
                        <th class="px-6 py-3 font-medium">Email</th>
                        <th class="px-6 py-3 font-medium">Proposed Topic</th>
                        <th class="px-6 py-3 font-medium text-center">Status</th>
                        <th class="px-6 py-3 font-medium">Submitted</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                    @forelse($guestRequests as $request)
                        <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition {{ $loop->even ? 'bg-warm-50/50 dark:bg-neutral-800/50' : '' }}">
                            <td class="px-6 py-4 font-medium text-stone-800 dark:text-stone-200">
                                {{ $request->name }}
                            </td>
                            <td class="px-6 py-4 text-stone-500 dark:text-stone-400">
                                {{ $request->email }}
                            </td>
                            <td class="px-6 py-4 text-stone-600 dark:text-stone-300">
                                {{ Str::limit($request->proposed_topic, 40) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($request->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Pending</span>
                                @elseif($request->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Approved</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Rejected</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-stone-500 dark:text-stone-400">
                                {{ $request->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.guest-requests.show', $request) }}"
                                   class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="text-stone-500 dark:text-stone-400 font-medium">No guest requests found</p>
                                    <p class="text-stone-400 dark:text-stone-500 text-sm mt-1">Guest blog requests will appear here when submitted.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($guestRequests->hasPages())
            <div class="px-6 py-4 border-t border-warm-200 dark:border-neutral-700">
                {{ $guestRequests->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
