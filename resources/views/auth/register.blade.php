<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Créer un compte - EasyColoc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="flex justify-center mb-8">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-indigo-600">EasyColoc</span>
                </div>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-sm p-8">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Créer un compte</h2>
                    <p class="mt-1 text-sm text-gray-500">Remplissez les informations ci-dessous pour commencer</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                        <input 
                            id="name" 
                            type="text" 
                            name="name" 
                            value="{{ old('name') }}" 
                            required 
                            autofocus 
                            autocomplete="name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:bg-blue-50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input 
                            id="email" 
                            type="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            required 
                            autocomplete="username"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:bg-blue-50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition"
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                        <input 
                            id="password" 
                            type="password" 
                            name="password" 
                            required 
                            autocomplete="new-password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:bg-blue-50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition"
                        >
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                        <input 
                            id="password_confirmation" 
                            type="password" 
                            name="password_confirmation" 
                            required 
                            autocomplete="new-password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:bg-blue-50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition"
                        >
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-4">
                        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Déjà inscrit ?
                        </a>
                        <button 
                            type="submit" 
                            class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                        >
                            Créer mon compte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
