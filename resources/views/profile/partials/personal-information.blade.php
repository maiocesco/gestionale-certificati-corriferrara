<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('I tuoi dati') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('I dati anagrafici sono gestiti dallo staff amministrativo. Per correggerli contatta la segreteria.') }}
        </p>
    </header>

    <dl class="mt-6 space-y-4">
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Nome e cognome') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->fullName() }}</dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->email }}</dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Ruolo') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->role->label() }}</dd>
        </div>
    </dl>
</section>
