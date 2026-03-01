<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $colocation->name }}
            </h2>
            <span class="text-sm text-gray-500">{{ __('Dashboard Colocation') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @php
                $userMembership = $colocation->users->where('id', auth()->id())->first();
                $isReadOnly = $colocation->status !== 'active' || ($userMembership && $userMembership->pivot->left_at !== null);
            @endphp

            <!-- Read-Only Banner -->
            @if($isReadOnly)
                <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm font-medium text-amber-800">
                            Cette colocation est archivée. Vous consultez l'historique en mode lecture seule.
                        </p>
                    </div>
                </div>
            @endif
            <!-- Main Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                <!-- Card 1: Membres -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-100 rounded-lg">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800">{{ __('Membres') }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="p-5">
                        @if($colocation->users->count() > 0)
                            <ul class="space-y-3">
                                @foreach($colocation->users as $user)
                                    <li
                                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center shadow-sm">
                                                <span class="text-sm font-bold text-white">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                                <p class="text-xs text-gray-500">
                                                    @if($user->pivot->role === 'owner')
                                                        <span class="text-indigo-600 font-medium">Proprietaire</span>
                                                    @else
                                                        Membre
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <!-- Reputation score avec badge coloré -->
                                            <div class="flex items-center gap-1 bg-gray-100 px-2 py-1 rounded">
                                                <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                                <span class="text-sm font-semibold {{ $user->reputation >= 50 ? 'text-green-600' : ($user->reputation >= 20 ? 'text-blue-600' : 'text-gray-600') }}">
                                                    {{ $user->reputation }}
                                                </span>
                                            </div>
                                            <!-- Remove member button (only for owner, not for self) -->
                                            @if(Gate::allows('update', $colocation) && $user->pivot->role !== 'owner' && $user->id !== auth()->id())
                                                <form method="POST"
                                                    action="{{ route('colocations.members.destroy', ['colocation' => $colocation->id, 'memberId' => $user->id]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm"
                                                        onclick="return confirm('Voulez-vous vraiment retirer ce membre ?')">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500 text-center py-4">Aucun membre</p>
                        @endif

                        <!-- Leave / Cancel buttons -->
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            @php
                                $userMembership = $colocation->users->where('id', auth()->id())->first();
                            @endphp

                            @if($userMembership && $userMembership->pivot->role !== 'owner')
                                <form method="POST" action="{{ route('colocations.leave', $colocation) }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                                        onclick="return confirm('Voulez-vous vraiment quitter cette colocation ?')">
                                        Quitter la colocation
                                    </button>
                                </form>
                            @endif

                            @if(Gate::allows('update', $colocation))
                                <form method="POST" action="{{ route('colocations.cancel', $colocation) }}" class="mt-2">
                                    @csrf
                                    <button type="submit"
                                        class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition"
                                        onclick="return confirm('Voulez-vous vraiment annuler cette colocation ? Cette action est irreversible.')">
                                        Annuler la colocation
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card 2: Bilan Financier Détaillé -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-100 rounded-lg">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ __('Bilan Financier') }}</h3>
                        </div>
                    </div>
                    <div class="p-5">
                        @php
                            $totalExpenses = $colocation->expenses()->sum('amount') ?? 0;
                            $memberCount = $colocation->activeMembers()->count();
                            $fairShare = $memberCount > 0 ? round($totalExpenses / $memberCount, 2) : 0;
                        @endphp
                        
                        @if($colocation->users->count() > 0)
                            <div class="space-y-3">
                                @foreach($colocation->users as $user)
                                    @php
                                        $memberPaid = $user->expenses->where('colocation_id', $colocation->id)->sum('amount') ?? 0;
                                        $balance = $balances[$user->id] ?? 0;
                                    @endphp
                                    <div class="p-3 rounded-lg border {{ $balance > 0 ? 'bg-green-50 border-green-200' : ($balance < 0 ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200') }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-medium text-gray-900">{{ $user->name }}</span>
                                            <span class="text-xs px-2 py-1 rounded {{ $balance > 0 ? 'bg-green-100 text-green-700' : ($balance < 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                                                {{ $balance > 0 ? 'Créditeur' : ($balance < 0 ? 'Débiteur' : 'Équilibré') }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 text-sm">
                                            <div>
                                                <p class="text-gray-500 text-xs">Payé</p>
                                                <p class="font-semibold text-gray-900">{{ number_format($memberPaid, 2) }} €</p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 text-xs">Part équitable</p>
                                                <p class="font-semibold text-gray-900">{{ number_format($fairShare, 2) }} €</p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 text-xs">Solde</p>
                                                <p class="font-bold {{ $balance > 0 ? 'text-green-600' : ($balance < 0 ? 'text-red-600' : 'text-gray-600') }}">
                                                    {{ $balance > 0 ? '+' : '' }}{{ number_format($balance, 2) }} €
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total des dépenses</span>
                                    <span class="font-bold text-gray-900">{{ number_format($totalExpenses, 2) }} €</span>
                                </div>
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-4">Aucun membre</p>
                        @endif
                    </div>
                </div>

                <!-- Card 3: Simplified Debts -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-green-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ __('Qui doit a qui ?') }}</h3>
                        </div>
                    </div>
                    <div class="p-5">
                        @if(!empty($simplifiedDebts))
                            <div class="space-y-3">
                                @foreach($simplifiedDebts as $debt)
                                    @php
                                        $debtor = $usersMap[$debt['from']] ?? null;
                                        $creditor = $usersMap[$debt['to']] ?? null;
                                    @endphp
                                    @if($debtor && $creditor)
                                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shadow-sm">
                                                        <span class="text-sm font-bold text-white">
                                                            {{ strtoupper(substr($debtor->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-900">{{ $debtor->name }}</p>
                                                        <p class="text-xs text-gray-500">doit payer</p>
                                                    </div>
                                                </div>
                                                
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                                </svg>
                                                
                                                <div class="flex items-center gap-3">
                                                    <div>
                                                        <p class="font-medium text-gray-900 text-right">{{ $creditor->name }}</p>
                                                        <p class="text-xs text-gray-500 text-right">doit recevoir</p>
                                                    </div>
                                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-sm">
                                                        <span class="text-sm font-bold text-white">
                                                            {{ strtoupper(substr($creditor->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3 pt-3 border-t border-gray-200 flex items-center justify-between">
                                                <span class="text-2xl font-bold text-green-600">{{ number_format($debt['amount'], 2) }} €</span>
                                                
                                                @if(!$isReadOnly)
                                                    <form method="POST" action="{{ route('settlements.store', $colocation) }}" onsubmit="return confirm('Confirmer que {{ $debtor->name }} a payé {{ number_format($debt['amount'], 2) }} € à {{ $creditor->name }} ?');">
                                                        @csrf
                                                        <input type="hidden" name="sender_id" value="{{ $debt['from'] }}">
                                                        <input type="hidden" name="receiver_id" value="{{ $debt['to'] }}">
                                                        <input type="hidden" name="amount" value="{{ $debt['amount'] }}">
                                                        <input type="hidden" name="settlement_date" value="{{ date('Y-m-d') }}">
                                                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                                            Marquer comme payé
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-4">Tout est regle ! Aucune dette.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card 4: Expense History with Month Filter -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-orange-50 to-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ __('Historique des depenses') }}</h3>
                        </div>

                        <!-- Month Filter -->
                        <form method="GET" class="flex items-center gap-2">
                            <select name="month" onchange="this.form.submit()"
                                class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tous les mois</option>
                                @for($i = 0; $i < 12; $i++)
                                    @php
                                        $date = now()->subMonths($i);
                                        $value = $date->format('Y-m');
                                        $label = $date->format('F Y');
                                    @endphp
                                    <option value="{{ $value }}" {{ $month === $value ? 'selected' : '' }}>
                                        {{ ucfirst($label) }}
                                    </option>
                                @endfor
                            </select>
                            @if($month)
                                <a href="{{ route('colocations.show', $colocation) }}"
                                    class="text-sm text-gray-500 hover:text-gray-700">
                                    Reinitialiser
                                </a>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="p-5">
                    @if($expenses->count() > 0)
                        <ul class="space-y-3">
                            @foreach($expenses as $expense)
                                <li
                                    class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors border border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-sm">
                                            <span class="text-sm font-bold text-white">
                                                {{ strtoupper(substr($expense->user->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $expense->title }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $expense->expense_date->format('d/m/Y') }} -
                                                @if($expense->category)
                                                    <span
                                                        class="bg-gray-100 px-2 py-0.5 rounded">{{ $expense->category->name }}</span>
                                                @endif
                                                - Par {{ $expense->user->name }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="font-bold text-orange-600">{{ number_format($expense->amount, 2) }}
                                            EUR</span>
                                        @if(!$isReadOnly)
                                            @can('update', $colocation)
                                                <form method="POST"
                                                    action="{{ route('expenses.destroy', ['colocation' => $colocation->id, 'expense' => $expense->id]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800"
                                                        onclick="return confirm('Voulez-vous vraiment supprimer cette depense ?')">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 text-center py-4">Aucune depense trouvee</p>
                    @endif
                </div>
            </div>

            <!-- Card 5: Add Expense -->
            @if(!$isReadOnly)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-white">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Ajouter une depense') }}</h3>
                    </div>
                </div>
                <div class="p-5">
                    <form method="POST" action="{{ route('expenses.store', $colocation) }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Titre</label>
                                <input type="text" name="title" id="title" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Montant
                                    (EUR)</label>
                                <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="expense_date" class="block text-sm font-medium text-gray-700">Date</label>
                                <input type="date" name="expense_date" id="expense_date" required
                                    value="{{ date('Y-m-d') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="category_id"
                                    class="block text-sm font-medium text-gray-700">Categorie</label>
                                <select name="category_id" id="category_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Selectionner une categorie</option>
                                    @foreach($colocation->categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                Ajouter la depense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <!-- Card 6: Invite Member -->
            @if(!$isReadOnly)
                @can('update', $colocation)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ __('Inviter un membre') }}</h3>
                        </div>
                    </div>
                    <div class="p-5">
                        <form method="POST" action="{{ route('colocations.invite', $colocation) }}">
                            @csrf
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email de
                                        l'invite</label>
                                    <input type="email" name="email" id="email" required placeholder="email@exemple.com"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                        Inviter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endcan
            @endif

            <!-- Card 7: Settlements -->
            @if(!$isReadOnly)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-teal-50 to-white">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-teal-100 rounded-lg">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Marquer comme paye') }}</h3>
                    </div>
                </div>
                <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @forelse($transactions as $debt)
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="font-medium text-gray-900">{{ $debt->debtor->name }}</span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                            </svg>
                                            <span class="font-medium text-gray-900">{{ $debt->creditor->name }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-lg font-bold text-green-600">{{ number_format($debt->amount, 2) }}
                                                EUR</span>
                                            <form action="{{ route('transaction.paid' , $debt->id) }}" method="post">
                                                @csrf
                                                @method('put')
                                                <button type="submit"
                                                class="px-3 py-1 bg-teal-600 text-white text-sm rounded hover:bg-teal-700 transition">
                                                Marquer paye
                                                </button>
                                            </form>
                                            
                                        </div>
                                    </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">Aucun paiement a effectuer</p>
                    @endforelse
                </div>
            </div>
            @endif

            <!-- Card 8: Category Management -->
            @if(!$isReadOnly)
                @can('update', $colocation)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-amber-100 rounded-lg">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ __('Gestion des Categories') }}</h3>
                        </div>
                    </div>
                    <div class="p-5">
                        @if($colocation->categories->count() > 0)
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($colocation->categories as $category)
                                    <span
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 rounded-full text-sm text-gray-700">
                                        {{ $category->name }}
                                        <form method="POST" action="{{ route('categories.destroy', [$colocation, $category]) }}"
                                            class="ml-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-sm mb-4">Aucune categorie pour le moment.</p>
                        @endif

                        <form method="POST" action="{{ route('categories.store', $colocation) }}"
                            class="flex items-center gap-3">
                            @csrf
                            <input type="text" name="name"
                                class="flex-1 px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm"
                                placeholder="Nom de la categorie" required>
                            <button type="submit"
                                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                                Ajouter
                            </button>
                        </form>
                    </div>
                </div>
                @endcan
            @endif
        </div>
    </div>

    <!-- Settlement Modal -->
    <div id="settlementModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"
            onclick="closeSettlementModal()"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <form id="settlementForm" method="POST" action="{{ route('settlements.store', $colocation) }}">
                        @csrf
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                        Confirmer le reglement
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <p class="text-sm text-gray-500">
                                            Vous etes sur le point de confirmer le paiement suivant :
                                        </p>
                                        <div class="bg-gray-50 p-4 rounded-xl">
                                            <p class="text-sm">
                                                <span class="font-semibold text-gray-900" id="senderName"></span>
                                                <span class="text-gray-500"> paie </span>
                                                <span class="font-semibold text-gray-900" id="receiverName"></span>
                                            </p>
                                            <p class="text-2xl font-bold text-green-600 mt-2">
                                                <span id="amountDisplay"></span> EUR
                                            </p>
                                        </div>

                                        <!-- Hidden inputs -->
                                        <input type="hidden" name="sender_id" id="senderId">
                                        <input type="hidden" name="receiver_id" id="receiverId">
                                        <input type="hidden" name="amount" id="amountInput">

                                        <div>
                                            <label for="settlement_date"
                                                class="block text-sm font-medium text-gray-700">
                                                Date du paiement
                                            </label>
                                            <input type="date" name="settlement_date" id="settlement_date" required
                                                class="mt-1 block w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm"
                                                value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit"
                                class="inline-flex w-full justify-center rounded-lg bg-green-600 px-3 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:ml-3 sm:w-auto transition-colors"
                                onclick="return confirm('Etes-vous sur de vouloir confirmer ce paiement ?');">
                                Confirmer
                            </button>
                            <button type="button"
                                class="mt-2 sm:mt-0 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:w-auto transition-colors"
                                onclick="closeSettlementModal()">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openSettlementModal(senderId, receiverId, amount, senderName, receiverName) {
            document.getElementById('senderId').value = senderId;
            document.getElementById('receiverId').value = receiverId;
            document.getElementById('amountInput').value = amount;
            document.getElementById('senderName').textContent = senderName;
            document.getElementById('receiverName').textContent = receiverName;
            document.getElementById('amountDisplay').textContent = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2 }).format(amount);
            document.getElementById('settlementModal').classList.remove('hidden');
        }

        function closeSettlementModal() {
            document.getElementById('settlementModal').classList.add('hidden');
        }

        // Close modal on escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSettlementModal();
            }
        });
    </script>
</x-app-layout>