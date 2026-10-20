<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Nuovo atleta') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    @include('admin.users._form_fields', ['user' => null])

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">
                            {{ __('Annulla') }}
                        </a>
                        <x-primary-button>{{ __('Crea utente') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
