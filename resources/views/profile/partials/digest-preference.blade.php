<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Riepilogo giornaliero') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Ricevi ogni mattina un\'email con le scadenze e i certificati da approvare, solo nei giorni in cui ce ne sono.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.digest-preference.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <label class="flex items-center gap-2">
            <input type="checkbox" name="daily_digest_enabled" value="1" @checked($user->daily_digest_enabled)
                   class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Attiva il riepilogo giornaliero via email') }}</span>
        </label>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Salva') }}</x-primary-button>

            @if (session('status') === 'digest-preference-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Salvato.') }}</p>
            @endif
        </div>
    </form>
</section>
