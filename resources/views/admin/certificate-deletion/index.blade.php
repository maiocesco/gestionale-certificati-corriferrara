<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Certificati da eliminare') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'certificate-deleted')
                <div class="bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200 text-sm rounded-md p-4">
                    {{ __('Certificato eliminato: resta solo il record minimo a fini di tracciabilità.') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('Certificati non validi e non in attesa di verifica, oltre il periodo di conservazione configurato in') }}
                    <a href="{{ route('admin.retention-settings.index') }}" class="text-indigo-600 dark:text-indigo-400 underline">{{ __('periodi di conservazione') }}</a>.
                </p>

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <th class="py-2 pr-4">{{ __('Atleta') }}</th>
                            <th class="py-2 pr-4">{{ __('Tipo') }}</th>
                            <th class="py-2 pr-4">{{ __('Scadenza') }}</th>
                            <th class="py-2 pr-4">{{ __('Stato') }}</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($certificates as $certificate)
                            <tr>
                                <td class="py-2 pr-4 text-sm text-gray-900 dark:text-gray-100">{{ $certificate->user->fullName() }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($certificate->typeDescription()) }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $certificate->expires_at->format('d/m/Y') }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400">{{ $certificate->status->label() }}</td>
                                <td class="py-2 pr-4 text-sm text-right">
                                    <a href="{{ route('admin.certificate-deletion.confirm-delete', $certificate) }}" class="text-red-600 dark:text-red-400 underline">
                                        {{ __('Elimina') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Nessun certificato oltre il periodo di conservazione.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
