@php
    $key = $emailTemplate->keyEnum();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $key->label() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                @php($isError = session('status') === 'email-template-test-failed')
                <div class="{{ $isError ? 'bg-red-50 dark:bg-red-900 text-red-700 dark:text-red-200' : 'bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200' }} text-sm rounded-md p-4">
                    @switch(session('status'))
                        @case('email-template-updated') {{ __('Testo salvato.') }} @break
                        @case('email-template-reset') {{ __('Testo ripristinato al valore predefinito.') }} @break
                        @case('email-template-test-sent') {{ __('Email di prova inviata.') }} @break
                        @case('email-template-test-failed') {{ __('Invio di prova non riuscito.') }} @break
                    @endswitch
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Segnaposto disponibili') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    @foreach ($key->placeholderTokens() as $token => $required)
                        <code class="bg-gray-100 dark:bg-gray-900 rounded px-1">{{ $token }}</code>{{ $required ? ' ★' : '' }}@if (! $loop->last), @endif
                    @endforeach
                    @if (empty($key->placeholders()))
                        {{ __('Nessuno: questo testo non usa segnaposto.') }}
                    @endif
                </p>
                @if (! empty(array_filter($key->placeholders())))
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('★ segnaposto obbligatorio: il salvataggio è bloccato se manca dal testo.') }}</p>
                @endif
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('In fondo al messaggio viene sempre aggiunta la firma comune.') }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('admin.email-templates.update', $emailTemplate) }}">
                    @csrf

                    @if ($key->hasSubject())
                        <div>
                            <x-input-label for="subject" :value="__('Oggetto')" />
                            <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="$subjectValue" required />
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>
                    @endif

                    <div class="mt-4">
                        <x-input-label for="body_markdown" :value="__('Corpo (Markdown)')" />
                        <textarea id="body_markdown" name="body_markdown" rows="14"
                                  class="mt-1 block w-full font-mono text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ $bodyValue }}</textarea>
                        <x-input-error :messages="$errors->get('body_markdown')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route('admin.email-templates.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">
                            {{ __('Annulla') }}
                        </a>
                        <x-secondary-button type="submit" formaction="{{ route('admin.email-templates.preview', $emailTemplate) }}" formmethod="POST">
                            {{ __('Anteprima') }}
                        </x-secondary-button>
                        <x-primary-button>{{ __('Salva') }}</x-primary-button>
                    </div>
                </form>

                <div class="flex items-center justify-end mt-4 gap-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                    @if ($key->mailerPurpose() !== null)
                        <form method="POST" action="{{ route('admin.email-templates.test', $emailTemplate) }}">
                            @csrf
                            <x-secondary-button type="submit">{{ __('Invia di prova a me stesso') }}</x-secondary-button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.email-templates.reset', $emailTemplate) }}"
                          onsubmit="return confirm('{{ __('Ripristinare il testo di default? Le modifiche salvate andranno perse.') }}');">
                        @csrf
                        @method('PUT')
                        <x-danger-button>{{ __('Ripristina testo di default') }}</x-danger-button>
                    </form>
                </div>
            </div>

            @if ($preview)
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Anteprima con dati di esempio') }}</h3>
                    @if ($preview->subject)
                        <p class="mt-2 text-sm text-gray-900 dark:text-gray-100">
                            <span class="font-medium">{{ __('Oggetto:') }}</span> {{ $preview->subject }}
                        </p>
                    @endif
                    <div class="mt-3 border border-gray-200 dark:border-gray-700 rounded-md p-4 text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-900 [&_ol]:list-decimal [&_ul]:list-disc [&_ol]:ml-5 [&_ul]:ml-5 [&_p]:mt-2 first:[&_p]:mt-0">
                        {!! $preview->html !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
