<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Sedi Runner\'s School') }}
            </h2>
            <a href="{{ route('admin.training-locations.create') }}">
                <x-primary-button>{{ __('Nuova sede') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200 text-sm rounded-md p-4">
                    @switch(session('status'))
                        @case('location-created') {{ __('Sede creata.') }} @break
                        @case('location-updated') {{ __('Sede aggiornata.') }} @break
                        @case('location-deleted') {{ __('Sede eliminata.') }} @break
                    @endswitch
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <th class="py-2 pr-4">{{ __('Nome') }}</th>
                            <th class="py-2 pr-4">{{ __('Città') }}</th>
                            <th class="py-2 pr-4">{{ __('Indirizzo') }}</th>
                            <th class="py-2 pr-4">{{ __('Atleti') }}</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($locations as $location)
                            <tr>
                                <td class="py-2 pr-4 text-sm text-gray-900 dark:text-gray-100">{{ $location->name }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $location->city }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $location->address ?? '—' }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $location->users_count }}</td>
                                <td class="py-2 pr-4 text-sm text-right">
                                    <a href="{{ route('admin.training-locations.edit', $location) }}" class="text-indigo-600 dark:text-indigo-400 underline">
                                        {{ __('Modifica') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Nessuna sede registrata.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
