<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Nouveau Paiement') }} - {{ $colocation->name }}
            </h2>
            <a href="{{ route('colocations.show', $colocation) }}" class="text-sm text-gray-500 hover:text-gray-700">
                Retour à la colocation
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('colocations.payments.store', $colocation) }}">
                        @csrf

                        <div class="space-y-4">
                            <div>
                                <label for="to_user_id" class="block text-sm font-medium text-gray-700">
                                    Bénéficiaire
                                </label>
                                <select name="to_user_id" id="to_user_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sélectionner un membre</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                                @error('to_user_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">
                                    Montant (EUR)
                                </label>
                                <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="payment_date" class="block text-sm font-medium text-gray-700">
                                    Date du paiement
                                </label>
                                <input type="date" name="payment_date" id="payment_date" required
                                    value="{{ date('Y-m-d') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('payment_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6 flex gap-4">
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                Créer le paiement
                            </button>
                            <a href="{{ route('colocations.show', $colocation) }}"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>