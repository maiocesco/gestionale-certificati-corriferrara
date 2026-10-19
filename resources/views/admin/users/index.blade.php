<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Utenti') }}
            </h2>
            <a href="{{ route('admin.users.create') }}">
                <x-primary-button>{{ __('Nuovo utente') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200 text-sm rounded-md p-4">
                    @switch(session('status'))
                        @case('user-created') {{ __('Utente creato ed email di benvenuto inviata.') }} @break
                        @case('user-updated') {{ __('Utente aggiornato.') }} @break
                        @case('password-reset') {{ __('Password reimpostata ed email inviata.') }} @break
                        @case('user-activated') {{ __('Utente riattivato.') }} @break
                        @case('user-deactivated') {{ __('Utente disattivato.') }} @break
                    @endswitch
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <form method="GET" class="mb-4 space-y-4">
                    <x-text-input name="q" type="search" class="w-full sm:w-96" value="{{ $search }}"
                                  placeholder="{{ __('Cerca per nome, email o codice fiscale…') }}" />

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <select name="club_type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Società (tutte)') }}</option>
                            @foreach ($clubTypes as $clubType)
                                <option value="{{ $clubType->value }}" @selected(($filters['club_type'] ?? '') === $clubType->value)>
                                    {{ $clubType->label() }}
                                </option>
                            @endforeach
                        </select>

                        <select name="external_club_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Altra società (tutte)') }}</option>
                            @foreach ($externalClubs as $club)
                                <option value="{{ $club->id }}" @selected(($filters['external_club_id'] ?? '') == $club->id)>
                                    {{ $club->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="entity" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Ente (tutti)') }}</option>
                            @foreach ($entities as $entity)
                                <option value="{{ $entity->value }}" @selected(($filters['entity'] ?? '') === $entity->value)>
                                    {{ $entity->label() }}
                                </option>
                            @endforeach
                        </select>

                        <select name="discipline" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Disciplina (tutte)') }}</option>
                            @foreach ($disciplines as $discipline)
                                <option value="{{ $discipline->value }}" @selected(($filters['discipline'] ?? '') === $discipline->value)>
                                    {{ $discipline->label() }}
                                </option>
                            @endforeach
                        </select>

                        <select name="training_location_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Sede (tutte)') }}</option>
                            @foreach ($trainingLocations as $location)
                                <option value="{{ $location->id }}" @selected(($filters['training_location_id'] ?? '') == $location->id)>
                                    {{ $location->displayName() }}
                                </option>
                            @endforeach
                        </select>

                        <x-text-input name="card_number" type="text" class="text-sm" value="{{ $filters['card_number'] ?? '' }}"
                                      placeholder="{{ __('Numero tessera') }}" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-secondary-button type="submit">{{ __('Filtra') }}</x-secondary-button>
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">
                            {{ __('Azzera filtri') }}
                        </a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <th class="py-2 pr-4">{{ __('Nome') }}</th>
                                <th class="py-2 pr-4">{{ __('Email') }}</th>
                                <th class="py-2 pr-4">{{ __('Ruolo') }}</th>
                                <th class="py-2 pr-4">{{ __('Sede') }}</th>
                                <th class="py-2 pr-4">{{ __('Stato') }}</th>
                                <th class="py-2 pr-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="py-2 pr-4 text-sm text-gray-900 dark:text-gray-100">{{ $user->fullName() }}</td>
                                    <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                                    <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $user->role->label() }}</td>
                                    <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $user->trainingLocation?->displayName() ?? '—' }}</td>
                                    <td class="py-2 pr-4 text-sm">
                                        @if ($user->is_active)
                                            <span class="text-green-700 dark:text-green-400">{{ __('Attivo') }}</span>
                                        @else
                                            <span class="text-red-700 dark:text-red-400">{{ __('Disattivato') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 text-sm text-right">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 dark:text-indigo-400 underline">
                                            {{ __('Modifica') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Nessun utente trovato.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
