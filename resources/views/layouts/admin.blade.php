<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="$store.darkMode.on ? 'dark' : ''">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('page-title', 'Admin') — {{ config('app.name', 'AlausDarykla') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Jodit WYSIWYG Editor -->
        <link rel="stylesheet" href="https://unpkg.com/jodit@4.2.27/es2021/jodit.min.css">
        <script src="https://unpkg.com/jodit@4.2.27/es2021/jodit.min.js"></script>

        <!-- Alpine.js -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('darkMode', {
                    on: localStorage.getItem('darkMode') === 'true',
                    toggle() {
                        this.on = !this.on;
                        localStorage.setItem('darkMode', this.on);
                    }
                });
            });
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-warm-50 dark:bg-neutral-900 flex">

            {{-- Mobile sidebar backdrop --}}
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-40 bg-black/50 lg:hidden"
                @click="sidebarOpen = false"
            ></div>

            {{-- Sidebar --}}
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-50 w-[260px] flex flex-col bg-sand-100 dark:bg-neutral-800 border-r border-sand-200 dark:border-neutral-700 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:z-auto"
            >
                {{-- Logo / Brand --}}
                <div class="flex items-center gap-3 px-6 py-5 border-b border-sand-200 dark:border-neutral-700">
                    <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-earth-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.2 0-2.4.6-3 1.5C8.4 3.6 7.2 3 6 3 3.8 3 2 4.8 2 7c0 4 5 8.5 10 12 5-3.5 10-8 10-12 0-2.2-1.8-4-4-4-1.2 0-2.4.6-3 1.5-.6-.9-1.8-1.5-3-1.5z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-base font-bold text-warm-900 dark:text-warm-100 tracking-tight">AlausDarykla</span>
                        <span class="block text-xs text-sand-600 dark:text-sand-400 -mt-0.5">Admin</span>
                    </div>
                    {{-- Close button on mobile --}}
                    <button @click="sidebarOpen = false" class="ml-auto lg:hidden p-1 rounded-lg text-sand-500 hover:text-warm-800 dark:hover:text-warm-200 hover:bg-sand-200 dark:hover:bg-neutral-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 admin-scrollbar">
                    @php
                        $currentRoute = request()->routeIs('*') ? Route::currentRouteName() : '';
                    @endphp

                    {{-- Dashboard --}}
                    <a href="{{ route('admin.dashboard') ?? '#' }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('admin.dashboard') ? 'bg-warm-400/20 text-earth-700 dark:text-warm-300 dark:bg-warm-500/15' : 'text-sand-700 dark:text-sand-300 hover:bg-sand-200 dark:hover:bg-neutral-700 hover:text-warm-800 dark:hover:text-warm-200' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>
                        </svg>
                        Dashboard
                    </a>

                    {{-- Blog Posts --}}
                    <a href="{{ route('admin.posts.index') ?? '#' }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('admin.posts.*') ? 'bg-warm-400/20 text-earth-700 dark:text-warm-300 dark:bg-warm-500/15' : 'text-sand-700 dark:text-sand-300 hover:bg-sand-200 dark:hover:bg-neutral-700 hover:text-warm-800 dark:hover:text-warm-200' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Blog Posts
                    </a>

                    {{-- Categories --}}
                    <a href="{{ route('admin.categories.index') ?? '#' }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('admin.categories.*') ? 'bg-warm-400/20 text-earth-700 dark:text-warm-300 dark:bg-warm-500/15' : 'text-sand-700 dark:text-sand-300 hover:bg-sand-200 dark:hover:bg-neutral-700 hover:text-warm-800 dark:hover:text-warm-200' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        Categories
                    </a>

                    {{-- Guest Requests --}}
                    <a href="{{ route('admin.guest-requests.index') ?? '#' }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('admin.guest-requests.*') ? 'bg-warm-400/20 text-earth-700 dark:text-warm-300 dark:bg-warm-500/15' : 'text-sand-700 dark:text-sand-300 hover:bg-sand-200 dark:hover:bg-neutral-700 hover:text-warm-800 dark:hover:text-warm-200' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        Guest Requests
                        @isset($guestRequestCount)
                            @if($guestRequestCount > 0)
                                <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-earth-500 text-white">
                                    {{ $guestRequestCount }}
                                </span>
                            @endif
                        @endisset
                    </a>

                    {{-- Hook Templates --}}
                    <a href="/admin/hook-templates"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->is('admin/hook-templates*') ? 'bg-warm-400/20 text-earth-700 dark:text-warm-300 dark:bg-warm-500/15' : 'text-sand-700 dark:text-sand-300 hover:bg-sand-200 dark:hover:bg-neutral-700 hover:text-warm-800 dark:hover:text-warm-200' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        Hook Templates
                    </a>

                    {{-- Translations --}}
                    <a href="{{ route('admin.translations.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('admin.translations.*') ? 'bg-warm-400/20 text-earth-700 dark:text-warm-300 dark:bg-warm-500/15' : 'text-sand-700 dark:text-sand-300 hover:bg-sand-200 dark:hover:bg-neutral-700 hover:text-warm-800 dark:hover:text-warm-200' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5a17.92 17.92 0 01-8.716-2.247m0 0A8.966 8.966 0 013 12c0-1.777.514-3.434 1.401-4.832"/>
                        </svg>
                        Translations
                    </a>

                    {{-- Divider --}}
                    <div class="my-3 border-t border-sand-200 dark:border-neutral-700"></div>

                    {{-- Settings --}}
                    <a href="{{ route('admin.settings.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('admin.settings.*') ? 'bg-warm-400/20 text-earth-700 dark:text-warm-300 dark:bg-warm-500/15' : 'text-sand-700 dark:text-sand-300 hover:bg-sand-200 dark:hover:bg-neutral-700 hover:text-warm-800 dark:hover:text-warm-200' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Settings
                    </a>
                </nav>

                {{-- User Profile Area --}}
                <div class="border-t border-sand-200 dark:border-neutral-700 px-3 py-4">
                    <div class="flex items-center gap-3 px-3">
                        <div class="w-8 h-8 rounded-full bg-earth-400 dark:bg-earth-600 flex items-center justify-center text-white text-sm font-semibold flex-shrink-0">
                            {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-warm-900 dark:text-warm-100 truncate">{{ Auth::user()->name ?? 'Admin' }}</p>
                            <p class="text-xs text-sand-500 dark:text-sand-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="p-1.5 rounded-lg text-sand-400 hover:text-earth-600 dark:hover:text-earth-400 hover:bg-sand-200 dark:hover:bg-neutral-700 transition-colors" title="Logout">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- Main Content --}}
            <div class="flex-1 flex flex-col min-w-0">

                {{-- Top Header Bar --}}
                <header class="sticky top-0 z-30 bg-white/80 dark:bg-neutral-800/80 backdrop-blur-md border-b border-sand-200 dark:border-neutral-700">
                    <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">

                        {{-- Left: Hamburger + Page title + Breadcrumbs --}}
                        <div class="flex items-center gap-4 min-w-0">
                            {{-- Hamburger for mobile --}}
                            <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 rounded-lg text-sand-500 hover:text-warm-800 dark:hover:text-warm-200 hover:bg-sand-100 dark:hover:bg-neutral-700 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>

                            <div class="min-w-0">
                                <h1 class="text-lg font-bold text-warm-900 dark:text-warm-100 truncate">
                                    @yield('page-title', 'Dashboard')
                                </h1>
                                @hasSection('breadcrumbs')
                                    <nav class="flex text-xs text-sand-500 dark:text-sand-400 mt-0.5">
                                        @yield('breadcrumbs')
                                    </nav>
                                @endif
                            </div>
                        </div>

                        {{-- Right: Actions --}}
                        <div class="flex items-center gap-2">

                            {{-- Dark / Light Mode Toggle --}}
                            <button
                                @click="$store.darkMode.toggle()"
                                class="p-2 rounded-lg text-sand-500 hover:text-warm-700 dark:text-sand-400 dark:hover:text-warm-300 hover:bg-sand-100 dark:hover:bg-neutral-700 transition-colors"
                                title="Toggle dark mode"
                            >
                                {{-- Sun icon (shown in dark mode) --}}
                                <svg x-show="$store.darkMode.on" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-13.66l-.71.71M4.05 19.95l-.71.71M21 12h-1M4 12H3m16.66 7.66l-.71-.71M4.05 4.05l-.71-.71M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                {{-- Moon icon (shown in light mode) --}}
                                <svg x-show="!$store.darkMode.on" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.005 9.005 0 0012 21a9.005 9.005 0 008.354-5.646z"/>
                                </svg>
                            </button>

                            {{-- Notification Bell --}}
                            <button class="relative p-2 rounded-lg text-sand-500 hover:text-warm-700 dark:text-sand-400 dark:hover:text-warm-300 hover:bg-sand-100 dark:hover:bg-neutral-700 transition-colors" title="Notifications">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                                </svg>
                                @isset($notificationCount)
                                    @if($notificationCount > 0)
                                        <span class="absolute top-1 right-1 w-2 h-2 bg-earth-500 rounded-full"></span>
                                    @endif
                                @endisset
                            </button>

                            {{-- User Avatar Dropdown --}}
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-sand-100 dark:hover:bg-neutral-700 transition-colors">
                                    <div class="w-8 h-8 rounded-full bg-earth-400 dark:bg-earth-600 flex items-center justify-center text-white text-sm font-semibold">
                                        {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                                    </div>
                                    <svg class="w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div
                                    x-show="open"
                                    @click.outside="open = false"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute right-0 mt-2 w-56 rounded-xl bg-white dark:bg-neutral-800 border border-sand-200 dark:border-neutral-700 shadow-lg py-1 z-50"
                                >
                                    <div class="px-4 py-3 border-b border-sand-100 dark:border-neutral-700">
                                        <p class="text-sm font-medium text-warm-900 dark:text-warm-100">{{ Auth::user()->name ?? 'Admin' }}</p>
                                        <p class="text-xs text-sand-500 dark:text-sand-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                                    </div>
                                    <a href="{{ route('profile.edit') ?? '#' }}" class="block px-4 py-2 text-sm text-sand-700 dark:text-sand-300 hover:bg-sand-50 dark:hover:bg-neutral-700 transition-colors">Profile Settings</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-sand-700 dark:text-sand-300 hover:bg-sand-50 dark:hover:bg-neutral-700 transition-colors">
                                            Sign Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                {{-- Flash Messages --}}
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mx-4 sm:mx-6 lg:mx-8 mt-4">
                        <div class="rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mx-4 sm:mx-6 lg:mx-8 mt-4">
                        <div class="rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif

                {{-- Main Content --}}
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    @yield('content')
                </main>
            </div>
        </div>

        {{-- Alpine x-cloak style --}}
        <style>[x-cloak] { display: none !important; }</style>

        @stack('scripts')
    </body>
</html>
