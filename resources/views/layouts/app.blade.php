<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'EasyColoc') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="antialiased bg-gray-50">
        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <aside class="hidden lg:flex lg:flex-shrink-0">
                <div class="flex flex-col w-64 bg-white border-r border-gray-200">
                    <!-- Logo -->
                    <div class="flex items-center h-16 px-6 border-b border-gray-200">
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-gray-900">EasyColoc</span>
                        </a>
                    </div>

                    <!-- Navigation -->
                    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                        <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Dashboard
                        </a>

                        <a href="{{ route('colocations.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('colocations.index') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Mes Colocations
                        </a>

                        <!-- MES ESPACES Section -->
                        @if(isset($userColocations) && $userColocations->isNotEmpty())
                            <div class="pt-4 pb-2">
                                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Mes Espaces</p>
                            </div>
                            
                            <div class="space-y-1 max-h-64 overflow-y-auto">
                                @foreach($userColocations as $colocation)
                                    <a href="{{ route('colocations.show', $colocation) }}" 
                                       class="flex items-center px-3 py-2 text-sm rounded-lg group {{ request()->route('colocation')?->id === $colocation->id ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">
                                        <!-- Initial Circle -->
                                        <div class="flex-shrink-0 w-7 h-7 rounded-full {{ $colocation->status === 'active' && !$colocation->user_left_at ? 'bg-indigo-100' : 'bg-gray-100' }} flex items-center justify-center mr-3">
                                            <span class="text-xs font-semibold {{ $colocation->status === 'active' && !$colocation->user_left_at ? 'text-indigo-600' : 'text-gray-500' }}">
                                                {{ strtoupper(substr($colocation->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        
                                        <!-- Name -->
                                        <span class="flex-1 truncate font-medium">{{ $colocation->name }}</span>
                                        
                                        <!-- Status Dot -->
                                        <span class="flex-shrink-0 w-2 h-2 rounded-full {{ $colocation->status === 'active' && !$colocation->user_left_at ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if(auth()->user()->isAdmin())
                            <div class="pt-4">
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.*') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                    Administration
                                </a>
                            </div>
                        @endif
                    </nav>

                    <!-- User Profile -->
                    <div class="flex-shrink-0 px-3 py-4 border-t border-gray-200">
                        <a href="{{ route('profile.edit') }}" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50">
                            <div class="flex items-center justify-center w-8 h-8 mr-3 text-white bg-indigo-600 rounded-full">
                                <span class="text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 truncate">Profil</p>
                            </div>
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="flex items-center w-full px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex flex-col flex-1 overflow-hidden">
                <!-- Top Header -->
                <header class="bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between h-16 px-6">
                        <div class="flex items-center">
                            <!-- Mobile menu button -->
                            <button type="button" class="lg:hidden -ml-2 mr-2 p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>

                            @isset($header)
                                <h1 class="text-2xl font-semibold text-gray-900">{{ $header }}</h1>
                            @endisset
                        </div>

                        <div class="flex items-center space-x-4">
                            <!-- Notifications -->
                            <button class="p-2 text-gray-400 rounded-lg hover:bg-gray-100 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                            </button>

                            <!-- User Avatar -->
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 text-white bg-indigo-600 rounded-full">
                                    <span class="text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto bg-gray-50">
                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="mx-6 mt-6">
                            <div class="p-4 bg-green-50 border border-green-200 rounded-xl">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mx-6 mt-6">
                            <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="p-6">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
