<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Carica certificato') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                @if ($hasFidalMembership)
                    <div class="mb-6 bg-yellow-50 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-sm rounded-md p-4">
                        {{ __('Hai un tesseramento FIDAL: puoi caricare solo un certificato agonistico per l\'atletica leggera. Un certificato agonistico per l\'atletica leggera copre comunque tutta l\'attività non agonistica.') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('certificates.store') }}" enctype="multipart/form-data"
                      x-data="{ type: @js(old('type', $hasFidalMembership ? 'competitive' : '')) }">
                    @csrf

                    <div>
                        <x-input-label for="expires_at" :value="__('Data di scadenza')" />
                        <x-text-input id="expires_at" name="expires_at" type="date" class="mt-1 block w-full"
                                      :value="old('expires_at')" required />
                        <x-input-error :messages="$errors->get('expires_at')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="type" :value="__('Tipo')" />
                        <select id="type" name="type" x-model="type" required
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
                            <option value="">—</option>
                            @foreach ($types as $type)
                                @unless ($hasFidalMembership && $type->value === 'non_competitive')
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endunless
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>

                    <div class="mt-4" x-show="type === 'competitive'" x-cloak>
                        <x-input-label for="discipline" :value="__('Disciplina')" />
                        <select id="discipline" name="discipline"
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
                            <option value="">—</option>
                            @foreach ($disciplines as $discipline)
                                <option value="{{ $discipline->value }}" @selected(old('discipline') === $discipline->value)>
                                    {{ $discipline->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('discipline')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="file" :value="__('File (immagine o PDF)')" />
                        <input id="file" name="file" type="file" accept=".jpg,.jpeg,.png,.heic,.heif,.pdf" required
                               class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300">
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route('certificates.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">
                            {{ __('Annulla') }}
                        </a>
                        <x-primary-button>{{ __('Carica certificato') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
