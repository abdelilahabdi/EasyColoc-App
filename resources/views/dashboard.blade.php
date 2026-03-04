<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    @php
        $activeColocation = auth()->user()->colocations()->where('status', 'active')->whereNull('colocation_user.left_at')->first();
        $pendingInvitations = \App\Models\Invitation::where('email', auth()->user()->email)
            ->where('status', 'pending')
            ->with('colocation')
            ->get();
    @endphp

    <!-- Welcome Section -->
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-900">Bienvenue, {{ auth()->user()->name }} !</h2>
        <p class="mt-1 text-sm text-gray-500">Gérez vos colocations et suivez vos dépenses en toute simplicité.</p>
    </div>

    <!-- Invitations en attente -->
    @if($pendingInvitations->count() > 0 && !$activeColocation)
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Invitations en attente</h3>
            <div class="space-y-3">
                @foreach($pendingInvitations as $invitation)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 rounded-lg">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        Invitation à rejoindre <span class="font-semibold text-indigo-600">{{ $invitation->colocation->name }}</span>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Envoyée par {{ $invitation->sender->name ?? 'Utilisateur inconnu' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('invitations.accept', $invitation) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                        Accepter
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('invitations.decline', $invitation) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                                        Refuser
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if($activeColocation)
            <!-- Colocation Active Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 rounded-lg">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Votre Colocation</h3>
                            <p class="text-sm text-gray-500">{{ $activeColocation->name }}</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">Active</span>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2 border-t border-gray-100">
                        <span class="text-sm text-gray-600">Membres</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $activeColocation->users()->whereNull('colocation_user.left_at')->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-t border-gray-100">
                        <span class="text-sm text-gray-600">Dépenses totales</span>
                        <span class="text-sm font-semibold text-gray-900">{{ number_format($activeColocation->expenses()->sum('amount'), 2) }} €</span>
                    </div>
                </div>

                <a href="{{ route('colocations.show', $activeColocation->id) }}" class="mt-6 block w-full px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium text-center rounded-lg hover:bg-indigo-700 transition">
                    Accéder au dashboard
                </a>
            </div>

            <!-- Quick Stats Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistiques rapides</h3>
                
                @php
                    $balances = $activeColocation->calculateBalancesWithSettlements();
                    $userBalance = $balances[auth()->id()] ?? 0;
                @endphp

                <div class="space-y-4">
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Votre solde</p>
                        <p class="text-2xl font-bold {{ $userBalance > 0 ? 'text-green-600' : ($userBalance < 0 ? 'text-red-600' : 'text-gray-900') }}">
                            {{ $userBalance > 0 ? '+' : '' }}{{ number_format($userBalance, 2) }} €
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $userBalance > 0 ? 'Vous êtes créditeur' : ($userBalance < 0 ? 'Vous êtes débiteur' : 'Vous êtes à jour') }}
                        </p>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Votre réputation</p>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <span class="text-2xl font-bold text-gray-900">{{ auth()->user()->reputation }}</span>
                            <span class="text-xs text-gray-500">points</span>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- No Colocation Card -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                    <div class="flex justify-center mb-4">
                        <div class="flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Commencez maintenant !</h3>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">
                        Vous n'avez pas encore de colocation active. Créez-en une pour inviter vos amis et commencer à suivre vos dépenses.
                    </p>
                    <a href="{{ route('colocations.create') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Créer une colocation
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
