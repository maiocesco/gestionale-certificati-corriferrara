<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Certificati in scadenza nei prossimi 30 giorni') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <div class="mb-4">
                    <a href="{{ route('admin.dashboard.expiring', ['export' => 'csv']) }}" class="text-sm text-indigo-600 dark:text-indigo-400 underline">
                        {{ __('Esporta CSV') }}
                    </a>
                </div>

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <th class="py-2 pr-4">{{ __('Atleta') }}</th>
                            <th class="py-2 pr-4">{{ __('Tipo') }}</th>
                            <th class="py-2 pr-4">{{ __('Scadenza') }}</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($certificates as $certificate)
                            <tr>
                                <td class="py-2 pr-4 text-sm text-gray-900 dark:text-gray-100">{{ $certificate->user->fullName() }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($certificate->typeDescription()) }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $certificate->expires_at->format('d/m/Y') }}</td>
                                <td class="py-2 pr-4 text-sm text-right">
                                    <a href="{{ route('admin.users.edit', $certificate->user) }}" class="text-indigo-600 dark:text-indigo-400 underline">
                                        {{ __('Apri') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-sm text-gray-500 dark:text-gray-400">{{ __('Nessun certificato in scadenza.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
