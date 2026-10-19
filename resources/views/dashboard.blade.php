<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Bacheca') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($isAdmin)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <a href="{{ route('admin.certificates.index') }}" class="block bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Certificati da approvare') }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $pendingCount }}</p>
                    </a>

                    <a href="{{ route('admin.dashboard.non-compliant') }}" class="block bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Atleti non in regola') }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $nonCompliantCount }}</p>
                    </a>

                    <a href="{{ route('admin.dashboard.expiring') }}" class="block bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('In scadenza nei prossimi 30 giorni') }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $expiringCount }}</p>
                    </a>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        {{ __('Hai effettuato l\'accesso.') }}
                        <a href="{{ route('certificates.index') }}" class="underline text-indigo-600 dark:text-indigo-400">
                            {{ __('Controlla la copertura dei tuoi certificati.') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
