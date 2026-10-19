<section>
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Tesseramenti') }}</h3>

    @if ($user->memberships->isNotEmpty())
        <div class="mt-4 space-y-3">
            @foreach ($user->memberships as $membership)
                <div x-data="{ editing: false }" class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
                    <div class="flex items-center justify-between" x-show="!editing">
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                            <span class="font-medium">{{ $membership->entity->label() }}</span>
                            — {{ $membership->discipline->label() }}
                            — {{ $membership->clubName() }}
                            — {{ __('tessera') }} {{ $membership->card_number }}
                            — {{ $membership->is_competitive ? __('Agonistico') : __('Non agonistico') }}
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <button type="button" @click="editing = true" class="text-indigo-600 dark:text-indigo-400 underline">
                                {{ __('Modifica') }}
                            </button>
                            <form method="POST" action="{{ route('admin.users.memberships.destroy', [$user, $membership]) }}"
                                  onsubmit="return confirm('{{ __('Eliminare questo tesseramento?') }}');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-red-600 dark:text-red-400 underline">{{ __('Elimina') }}</button>
                            </form>
                        </div>
                    </div>

                    <div x-show="editing" x-cloak class="mt-2">
                        @include('admin.users._membership_form', [
                            'membership' => $membership,
                            'action' => route('admin.users.memberships.update', [$user, $membership]),
                            'method' => 'PUT',
                            'formId' => 'edit-'.$membership->id,
                        ])
                        <button type="button" @click="editing = false" class="mt-2 text-sm text-gray-600 dark:text-gray-400 underline">
                            {{ __('Annulla') }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Nessun tesseramento registrato.') }}</p>
    @endif

    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('Nuovo tesseramento') }}</h4>
        @include('admin.users._membership_form', [
            'membership' => null,
            'action' => route('admin.users.memberships.store', $user),
            'method' => 'POST',
            'formId' => 'new',
        ])
    </div>
</section>
