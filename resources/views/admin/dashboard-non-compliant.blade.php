<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Atleti non in regola') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <form method="GET" class="mb-4 space-y-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <select name="club_type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Società (tutte)') }}</option>
                            @foreach ($clubTypes as $clubType)
                                <option value="{{ $clubType->value }}" @selected(request('club_type') === $clubType->value)>{{ $clubType->label() }}</option>
                            @endforeach
                        </select>

                        <select name="entity" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Ente (tutti)') }}</option>
                            @foreach ($entities as $entity)
                                <option value="{{ $entity->value }}" @selected(request('entity') === $entity->value)>{{ $entity->label() }}</option>
                            @endforeach
                        </select>

                        <select name="discipline" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Disciplina (tutte)') }}</option>
                            @foreach ($disciplines as $discipline)
                                <option value="{{ $discipline->value }}" @selected(request('discipline') === $discipline->value)>{{ $discipline->label() }}</option>
                            @endforeach
                        </select>

                        <select name="training_location_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Sede (tutte)') }}</option>
                            @foreach ($trainingLocations as $location)
                                <option value="{{ $location->id }}" @selected((string) request('training_location_id') === (string) $location->id)>{{ $location->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-secondary-button type="submit">{{ __('Filtra') }}</x-secondary-button>
                        <a href="{{ route('admin.dashboard.non-compliant', array_merge(request()->query(), ['export' => 'csv'])) }}" class="text-sm text-indigo-600 dark:text-indigo-400 underline">
                            {{ __('Esporta CSV') }}
                        </a>
                    </div>
                </form>

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <th class="py-2 pr-4">{{ __('Nome') }}</th>
                            <th class="py-2 pr-4">{{ __('Email') }}</th>
                            <th class="py-2 pr-4">{{ __('Motivo') }}</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($nonCompliant as $row)
                            <tr>
                                <td class="py-2 pr-4 text-sm text-gray-900 dark:text-gray-100">{{ $row['user']->fullName() }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $row['user']->email }}</td>
                                <td class="py-2 pr-4 text-sm text-red-700 dark:text-red-400">{{ $row['gaps']->implode('; ') }}</td>
                                <td class="py-2 pr-4 text-sm text-right">
                                    <a href="{{ route('admin.users.edit', $row['user']) }}" class="text-indigo-600 dark:text-indigo-400 underline">
                                        {{ __('Apri') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-sm text-gray-500 dark:text-gray-400">{{ __('Nessun atleta non in regola.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
