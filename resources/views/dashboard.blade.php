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


                

<div class="p-6 border-t border-gray-200">
    @php
        // ابحث عن مجموعة السكن النشطة للمستخدم الحالي
        $activeColocation = auth()->user()->colocations()->where('status', 'active')->first();
    @endphp

    @if ($activeColocation)
        {{-- إذا كان المستخدم يمتلك مجموعة سكن، اعرض له رابطاً لعرضها --}}
        <h3 class="text-lg font-semibold">مجموعة السكن الخاصة بك</h3>
        <p class="mt-2">أنت عضو في: <strong>{{ $activeColocation->name }}</strong></p>
        <a href="{{ route('colocations.show', $activeColocation->id) }}" class="inline-block mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
               Aller au groupe de logement
        </a>
    @else
        
        <h3 class="text-lg font-semibold"> start now!</h3>
        <p class="mt-2">Vous n'avez pas encore de groupe de colocation actif. Créez-en un pour inviter vos amis et commencer à suivre vos dépenses.</p>
        <a href="{{ route('colocations.create') }}" class="inline-block mt-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
               Création d'un nouveau complexe résidentiel
        </a>
    @endif
</div>

            </div>
        </div>
    </div>
</x-app-layout>
