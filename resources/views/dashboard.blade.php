<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>


                

   {{-- ======================================================== --}}
{{--      Ajoutez le code suivant ici                         --}}
{{-- ======================================================== --}}

<div class="p-6 border-t border-gray-200">
    @php
        // Recherche la colocation active de l'utilisateur authentifié
        $activeColocation = auth()->user()->colocations()->where('status', 'active')->first();
    @endphp

    @if ($activeColocation)
        {{-- Si l'utilisateur a une colocation, afficher un lien pour la voir --}}
        <h3 class="text-lg font-semibold">Votre Colocation</h3>
        <p class="mt-2">Vous êtes membre de : <strong>{{ $activeColocation->name }}</strong></p>
        <a href="{{ route('colocations.show', $activeColocation->id) }}" class="inline-block mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Accéder à la colocation
        </a>
    @else
        {{-- Si l'utilisateur n'a pas de colocation, afficher un lien pour en créer une --}}
        <h3 class="text-lg font-semibold">Commencez maintenant !</h3>
        <p class="mt-2">Vous n'avez pas encore de colocation active. Créez-en une pour inviter vos amis et commencer à suivre vos dépenses.</p>
        <a href="{{ route('colocations.create') }}" class="inline-block mt-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
            Créer une nouvelle colocation
        </a>
    @endif
</div>


            </div>
        </div>
    </div>
</x-app-layout>
