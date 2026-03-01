<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Paiements') }} - {{ $colocation->name }}
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
                    @if($payments->count() > 0)
                        <ul class="space-y-4">
                            @foreach($payments as $payment)
                                <li class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex items-center gap-4">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900">
                                                {{ $payment->fromUser->name }} → {{ $payment->toUser->name }}
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                {{ $payment->payment_date->format('d/m/Y') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="font-bold text-green-600 text-lg">
                                            {{ number_format($payment->amount, 2) }} EUR
                                        </span>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                            Payé
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 text-center py-8">Aucun paiement enregistré</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>