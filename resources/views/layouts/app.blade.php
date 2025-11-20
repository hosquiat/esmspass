<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TeamVault') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50">
    <div id="app">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('records.index') }}" class="text-xl font-bold text-indigo-600">
                                {{ config('app.name', 'TeamVault') }}
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('records.index') }}"
                                class="{{ request()->routeIs('records.*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Records
                            </a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('settings.index') }}"
                                    class="{{ request()->routeIs('settings.*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Settings
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center">
                        @auth
                            <div class="flex items-center space-x-4">
                                <!-- User Info -->
                                <div class="flex items-center space-x-3">
                                    @if (auth()->user()->avatar)
                                        <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}"
                                            class="h-8 w-8 rounded-full" referrerpolicy="no-referrer">
                                    @endif
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-700">{{ auth()->user()->name }}</div>
                                        @if (auth()->user()->isAdmin())
                                            <div class="text-xs text-indigo-600 font-semibold">Admin</div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Logout Button -->
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-8">
            @yield('content')
        </main>
    </div>
</body>

</html>
