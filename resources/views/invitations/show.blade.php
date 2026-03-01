<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Invitation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-4">
                            Invitation à rejoindre "{{ $colocation->name }}"
                        </h3>

                        <p class="text-gray-600 mb-6">
                            Vous avez été invité à rejoindre cette colocation.
                        </p>

                        <div class="flex justify-center gap-4">
                            <form method="POST" action="{{ route('invitations.accept', $invitation) }}">
                                @csrf
                                <button type="submit"
                                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                    Accepter l'invitation
                                </button>
                            </form>

                            <form method="POST" action="{{ route('invitations.decline', $invitation) }}">
                                @csrf
                                <button type="submit"
                                    class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                    Refuser l'invitation
                                </button>
                            </form>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-800">
                                Retour au dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>