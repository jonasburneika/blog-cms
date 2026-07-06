@extends('blog::layouts.admin')

@section('page-title', 'Create Redirect')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.redirects.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Redirects</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Create</span>
@endsection

@section('content')
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200 mb-6">Create Redirect</h1>

        @include('blog::admin.redirects.form', ['action' => route('admin.redirects.store'), 'pages' => $pages])
    </div>
@endsection
