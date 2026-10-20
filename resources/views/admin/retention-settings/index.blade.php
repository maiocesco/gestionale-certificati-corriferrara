<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Conservazione certificati') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'retention-setting-updated')
                <div class="bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200 text-sm rounded-md p-4">
                    {{ __('Periodo di conservazione aggiornato.') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('Anni di conservazione dei certificati scaduti dopo la data di scadenza, per ente di tesseramento. Se l\'atleta ha più tesseramenti vale il periodo più lungo tra quelli applicabili; senza tesseramenti si applica "Nessun tesseramento".') }}
                </p>

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <th class="py-2 pr-4">{{ __('Ente') }}</th>
                            <th class="py-2 pr-4">{{ __('Anni') }}</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($categories as $category)
                            @php
                                $setting = $settings->get($category->value);
                                $isSubmittedForm = old('_category') === $category->value;
                            @endphp
                            <tr>
                                <td class="py-2 pr-4 text-sm text-gray-900 dark:text-gray-100">{{ $category->label() }}</td>
                                <td class="py-2 pr-4">
                                    <form method="POST" action="{{ route('admin.retention-settings.update', $category->value) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_category" value="{{ $category->value }}">
                                        <x-text-input type="number" name="years" min="1" max="100" class="w-24"
                                                      :value="$isSubmittedForm ? old('years', $setting?->years) : $setting?->years" />
                                        <x-secondary-button type="submit">{{ __('Salva') }}</x-secondary-button>
                                    </form>
                                    <x-input-error :messages="$isSubmittedForm ? $errors->get('years') : []" class="mt-1" />
                                </td>
                                <td class="py-2 pr-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('default: :years anni', ['years' => $category->defaultYears()]) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
