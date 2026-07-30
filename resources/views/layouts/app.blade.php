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
    <body class="font-sans antialiased bg-slate-100 text-slate-900 overflow-hidden">
        <div class="h-screen flex overflow-hidden">
            @if(Auth::check() && Auth::user()->role === 'Administrator')
                <!-- Admin Sidebar Layout for all Admin views -->
                @include('layouts.admin-sidebar')

                <div class="flex-1 flex flex-col min-w-0 bg-slate-100 h-screen overflow-y-auto">
                    <!-- Top Header Bar with User Profile Widget -->
                    <header class="bg-white border-b border-slate-200 py-3 px-6 sm:px-8 flex justify-end items-center shrink-0">
                        <!-- Top Right Corner Profile Dropdown -->
                        <div class="flex items-center space-x-4">
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
            @else
                <!-- Standard Top Navigation Layout for non-admins -->
                <div class="w-full flex flex-col min-h-screen">
                    @include('layouts.navigation')

                    <!-- Page Heading -->
                    @isset($header)
                        <header class="bg-white border-b border-slate-200">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <!-- Page Content -->
                    <main class="flex-1 p-6">
                        {{ $slot }}
                    </main>

                    @include('layouts.footer')
                </div>
            @endif
        </div>
    </body>
</html>
