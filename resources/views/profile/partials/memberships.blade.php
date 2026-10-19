<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('I tuoi tesseramenti') }}
        </h2>
    </header>

    @if ($user->memberships->isNotEmpty())
        <dl class="mt-4 space-y-3">
            @foreach ($user->memberships as $membership)
                <div class="text-sm text-gray-900 dark:text-gray-100">
                    <span class="font-medium">{{ $membership->entity->label() }}</span>
                    — {{ $membership->discipline->label() }}
                    — {{ $membership->clubName() }}
                    — {{ __('tessera') }} {{ $membership->card_number }}
                    — {{ $membership->is_competitive ? __('Agonistico') : __('Non agonistico') }}
                </div>
            @endforeach
        </dl>
    @else
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Nessun tesseramento registrato.') }}</p>
    @endif

    @if ($user->trainingLocation)
        <p class="mt-4 text-sm text-gray-900 dark:text-gray-100">
            <span class="font-medium">{{ __('Sede Runner\'s School') }}:</span> {{ $user->trainingLocation->displayName() }}
        </p>
    @endif
</section>
