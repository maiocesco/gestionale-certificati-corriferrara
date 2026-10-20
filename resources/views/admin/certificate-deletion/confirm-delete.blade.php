<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Elimina certificato') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg space-y-4">
                <dl class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                    <div><dt class="inline font-medium">{{ __('Atleta:') }}</dt> <dd class="inline">{{ $certificate->user->fullName() }}</dd></div>
                    <div><dt class="inline font-medium">{{ __('Tipo:') }}</dt> <dd class="inline">{{ ucfirst($certificate->typeDescription()) }}</dd></div>
                    <div><dt class="inline font-medium">{{ __('Scadenza:') }}</dt> <dd class="inline">{{ $certificate->expires_at->format('d/m/Y') }}</dd></div>
                </dl>

                <div class="bg-yellow-50 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-sm rounded-md p-4">
                    {{ __('Il file del certificato verrà cancellato in modo permanente. Resterà solo un record minimo (atleta, tipo, scadenza, data ed esecutore dell\'eliminazione) a fini di tracciabilità.') }}
                </div>

                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('Confermi l\'eliminazione di questo certificato?') }}
                </p>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('admin.certificate-deletion.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">
                        {{ __('Annulla') }}
                    </a>
                    <form method="POST" action="{{ route('admin.certificate-deletion.destroy', $certificate) }}">
                        @csrf
                        @method('delete')
                        <x-danger-button type="submit">{{ __('Elimina definitivamente') }}</x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
