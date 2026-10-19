<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Per motivi di sicurezza devi scegliere una nuova password prima di continuare.') }}
    </div>

    <form method="post" action="{{ route('password.change.update') }}">
        @csrf
        @method('put')

        <div>
            <x-input-label for="password" :value="__('Nuova password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                           type="password"
                           name="password"
                           required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Minimo 8 caratteri, con almeno una maiuscola, un numero e un simbolo.') }}
            </p>
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Conferma nuova password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                           type="password"
                           name="password_confirmation"
                           required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Imposta nuova password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
