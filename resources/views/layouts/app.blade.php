<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Forensic Toolkit') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Favicons & App Icons -->
        <link rel="icon" type="image/png" href="{{ asset('favicon/favicon-96x96.png') }}" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}" />
        <link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}" />
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}" />
        <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900 overflow-hidden" x-data="{ sidebarOpen: false }">
        <div class="h-screen flex overflow-hidden">
            @if(Auth::check())
                <!-- Universal Sidebar Layout for all authenticated roles -->
                @include('layouts.admin-sidebar')

                <div class="flex-1 flex flex-col min-w-0 bg-slate-100 h-screen overflow-y-auto">
                    <!-- Top Header Bar with Page Header Text & User Profile Widget -->
                    <header class="bg-white border-b border-slate-200 py-3 px-4 sm:px-8 flex items-center justify-between gap-4 shrink-0">
                        <div class="flex items-center space-x-3 min-w-0 overflow-hidden">
                            <!-- Mobile Sidebar Toggle Button -->
                            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-slate-500 hover:text-slate-700 p-1.5 rounded-md border border-slate-200 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                            </button>

                            <!-- Top Header Title Text -->
                            @isset($header)
                                <div class="min-w-0 truncate">
                                    {{ $header }}
                                </div>
                            @endisset
                        </div>

                        <!-- Top Right Corner Notifications & Profile Dropdown -->
                        <div class="flex items-center space-x-3 shrink-0">
                            <!-- In-App Notification Center Dropdown -->
                            @php
                                $unreadNotifications = \App\Models\UserNotification::where('user_id', Auth::id())->whereNull('read_at')->latest()->get();
                            @endphp
                            <x-dropdown align="right" width="80">
                                <x-slot name="trigger">
                                    <button class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg border border-slate-200 transition focus:outline-none">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                        @if($unreadNotifications->isNotEmpty())
                                            <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-rose-600 rounded-full animate-pulse ring-2 ring-white"></span>
                                        @endif
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="p-3 border-b border-slate-100 flex items-center justify-between">
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Alert Center</h4>
                                        <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full">{{ $unreadNotifications->count() }} New</span>
                                    </div>
                                    <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 text-xs">
                                        @forelse($unreadNotifications as $notif)
                                            <a href="{{ $notif->target_url ?: '#' }}" class="block p-3 hover:bg-slate-50 transition">
                                                <p class="font-bold text-slate-900 leading-snug">{{ $notif->title }}</p>
                                                <p class="text-slate-600 text-[11px] mt-0.5 leading-normal">{{ $notif->message }}</p>
                                                <span class="text-[10px] text-slate-400 font-mono mt-1 block">{{ $notif->created_at->diffForHumans() }}</span>
                                            </a>
                                        @empty
                                            <div class="p-4 text-center text-slate-400 text-xs">
                                                No unread operational alerts.
                                            </div>
                                        @endforelse
                                    </div>
                                </x-slot>
                            </x-dropdown>

                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="flex items-center space-x-3 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition focus:outline-none">
                                        <div class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold uppercase">
                                            {{ substr(Auth::user()->name, 0, 2) }}
                                        </div>
                                        <div class="text-left text-xs">
                                            <p class="font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</p>
                                            <p class="text-[10px] text-blue-600 font-semibold leading-tight">{{ Auth::user()->role }}</p>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')">
                                        {{ __('Profile Settings') }}
                                    </x-dropdown-link>

                                    <!-- Authentication -->
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault();
                                                            this.closest('form').submit();">
                                            {{ __('Log Out') }}
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </header>

                    <!-- Page Content -->
                    <main class="flex-1 p-6 overflow-y-auto flex flex-col justify-between">
                        <div>
                            {{ $slot }}
                        </div>
                        @include('layouts.footer')
                    </main>
                </div>
            @endif
        </div>
    </body>
</html>
