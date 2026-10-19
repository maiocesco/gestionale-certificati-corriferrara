<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Elimina sede') }}: {{ $location->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg space-y-4">
                @if ($athletesCount > 0)
                    <div class="bg-yellow-50 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-sm rounded-md p-4">
                        <p>
                            {{ trans_choice(
                                'Questa sede ha :count atleta assegnato. Eliminandola, resterà senza alcuna sede.|Questa sede ha :count atleti assegnati. Eliminandola, resteranno senza alcuna sede.',
                                $athletesCount,
                                ['count' => $athletesCount]
                            ) }}
                        </p>
                        <p class="mt-2">
                            <a href="{{ route('admin.users.index', ['training_location_id' => $location->id]) }}" class="underline font-medium">
                                {{ __('Vedi l\'elenco per riassegnarli prima dell\'eliminazione') }}
                            </a>
                        </p>
                    </div>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Nessun atleta è assegnato a questa sede.') }}
                    </p>
                @endif

                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('Confermi l\'eliminazione della sede :name?', ['name' => $location->name]) }}
                </p>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('admin.training-locations.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">
                        {{ __('Annulla') }}
                    </a>
                    <form method="POST" action="{{ route('admin.training-locations.destroy', $location) }}">
                        @csrf
                        @method('delete')
                        <x-danger-button type="submit">{{ __('Elimina definitivamente') }}</x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
