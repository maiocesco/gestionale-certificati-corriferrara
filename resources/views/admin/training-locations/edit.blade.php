<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Modifica sede') }}: {{ $location->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('admin.training-locations.update', $location) }}">
                    @csrf
                    @method('put')

                    @include('admin.training-locations._form_fields', ['location' => $location])

                    <div class="flex items-center justify-between mt-6">
                        <a href="{{ route('admin.training-locations.confirm-delete', $location) }}" class="text-sm text-red-600 dark:text-red-400 underline">
                            {{ __('Elimina sede') }}
                        </a>

                        <div class="flex items-center gap-4">
                            <a href="{{ route('admin.training-locations.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">
                                {{ __('Annulla') }}
                            </a>
                            <x-primary-button>{{ __('Salva modifiche') }}</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
