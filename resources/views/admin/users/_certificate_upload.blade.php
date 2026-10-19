@php
    $hasFidalMembership = $user->hasFidalMembership();
@endphp

<section>
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Certificati') }}</h3>

    @if ($user->certificates->isNotEmpty())
        <ul class="mt-3 space-y-2">
            @foreach ($user->certificates->sortByDesc('created_at') as $certificate)
                <li class="text-sm text-gray-900 dark:text-gray-100 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <span>
                        {{ ucfirst($certificate->typeDescription()) }}
                        — {{ $certificate->status->label() }}
                        — {{ __('scadenza') }} {{ $certificate->expires_at->format('d/m/Y') }}
                    </span>
                    <a href="{{ route('certificates.file', $certificate) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">
                        {{ __('Visualizza') }}
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Nessun certificato caricato.') }}</p>
    @endif

    <h4 class="mt-6 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Carica certificato per conto dell\'atleta') }}</h4>

    @if ($hasFidalMembership)
        <div class="mt-3 bg-yellow-50 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-sm rounded-md p-4">
            {{ __('Questo atleta ha un tesseramento FIDAL: può avere solo un certificato agonistico per l\'atletica leggera.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.certificates.store', $user) }}" enctype="multipart/form-data"
          class="mt-4 space-y-4"
          x-data="{ type: @js($hasFidalMembership ? 'competitive' : '') }">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label :value="__('Data di scadenza')" />
                <x-text-input name="expires_at" type="date" class="mt-1 block w-full" required />
            </div>

            <div>
                <x-input-label :value="__('Tipo')" />
                <select name="type" x-model="type" required
                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm mt-1 block w-full">
                    <option value="">—</option>
                    @foreach (\App\Enums\CertificateType::cases() as $certType)
                        @unless ($hasFidalMembership && $certType->value === 'non_competitive')
                            <option value="{{ $certType->value }}">{{ $certType->label() }}</option>
                        @endunless
                    @endforeach
                </select>
            </div>

            <div x-show="type === 'competitive'" x-cloak>
                <x-input-label :value="__('Disciplina')" />
                <select name="discipline"
                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm mt-1 block w-full">
                    <option value="">—</option>
                    @foreach (\App\Enums\Discipline::cases() as $discipline)
                        <option value="{{ $discipline->value }}">{{ $discipline->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label :value="__('File (immagine o PDF)')" />
                <input name="file" type="file" accept=".jpg,.jpeg,.png,.heic,.heif,.pdf" required
                       class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300">
            </div>
        </div>

        @if ($errors->any())
            <div class="text-sm text-red-600 dark:text-red-400 space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <x-primary-button type="submit">{{ __('Carica certificato') }}</x-primary-button>
    </form>
</section>
