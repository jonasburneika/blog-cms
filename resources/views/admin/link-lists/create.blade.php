@extends('blog::layouts.admin')

@section('page-title', 'New Link List')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.link-lists.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Link Lists</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">New</span>
@endsection

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200 mb-6">New Link List</h1>
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
            <form method="POST" action="{{ route('admin.link-lists.store') }}">
                @csrf
                @include('blog::admin.link-lists.partials.fields', ['list' => null])
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-warm-200 dark:border-neutral-700">
                    <a href="{{ route('admin.link-lists.index') }}" class="px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition">Create list</button>
                </div>
            </form>
        </div>
    </div>
@endsection
