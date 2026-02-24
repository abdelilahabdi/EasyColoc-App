<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Détails de la colocation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Colocation Name -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-900">
                        {{ $colocation->name }}
                    </h3>
                </div>
            </div>

            <!-- Members List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">
                        {{ __('Membres de la colocation') }}
                    </h4>

                    @if($colocation->users->count() > 0)
                        <ul class="divide-y divide-gray-200">
                            @foreach($colocation->users as $user)
                                <li class="py-4 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ substr($user->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $user->name }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{ $user->email }}
                                            </p>
                                        </div>
                                    </div>
                                    @if($user->pivot->role)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $user->pivot->role }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 text-sm">
                            {{ __('Aucun membre dans cette colocation pour le moment.') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>