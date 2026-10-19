<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Modifica utente') }}: {{ $user->fullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200 text-sm rounded-md p-4">
                    @switch(session('status'))
                        @case('membership-created') {{ __('Tesseramento aggiunto.') }} @break
                        @case('membership-updated') {{ __('Tesseramento aggiornato.') }} @break
                        @case('membership-deleted') {{ __('Tesseramento eliminato.') }} @break
                        @case('password-reset') {{ __('Password reimpostata ed email inviata.') }} @break
                        @case('user-activated') {{ __('Utente riattivato.') }} @break
                        @case('user-deactivated') {{ __('Utente disattivato.') }} @break
                    @endswitch
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('put')

                    @include('admin.users._form_fields', ['user' => $user])

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">
                            {{ __('Annulla') }}
                        </a>
                        <x-primary-button>{{ __('Salva modifiche') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                @include('admin.users._memberships', ['user' => $user])
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Reimposta password') }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Genera una nuova password provvisoria e la invia via email all\'utente.') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}"
                      onsubmit="return confirm('{{ __('Confermi la reimpostazione della password?') }}');">
                    @csrf
                    @method('put')
                    <x-secondary-button type="submit">{{ __('Reimposta password') }}</x-secondary-button>
                </form>
            </div>

            @if (! auth()->user()->is($user))
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ $user->is_active ? __('Disattiva account') : __('Riattiva account') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $user->is_active
                                ? __('L\'utente non potrà più accedere al gestionale.')
                                : __('L\'utente potrà di nuovo accedere al gestionale.') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                        @csrf
                        @method('put')
                        @if ($user->is_active)
                            <x-danger-button type="submit">{{ __('Disattiva') }}</x-danger-button>
                        @else
                            <x-primary-button type="submit">{{ __('Riattiva') }}</x-primary-button>
                        @endif
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
