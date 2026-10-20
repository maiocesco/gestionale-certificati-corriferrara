@php
    $coverageService = app(\App\Services\CertificateCoverageService::class);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('I tuoi certificati') }}
            </h2>
            <a href="{{ route('certificates.create') }}">
                <x-primary-button>{{ __('Carica certificato') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'certificate-uploaded')
                <div class="bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200 text-sm rounded-md p-4">
                    {{ __('Certificato caricato. In attesa di verifica da parte della segreteria.') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Copertura') }}</h3>
                <p class="mt-1 text-sm {{ $isCompliant ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                    {{ $isCompliant ? __('Sei in regola.') : __('Non sei in regola: controlla i requisiti scoperti qui sotto.') }}
                </p>

                <ul class="mt-4 space-y-2">
                    @foreach ($coverage as $result)
                        <li class="text-sm flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                            <span class="text-gray-900 dark:text-gray-100">{{ $result->requirement->label() }}</span>
                            @if ($result->isCovered())
                                <span class="text-green-700 dark:text-green-400">
                                    {{ __('Coperto fino al :date', ['date' => $result->certificate->expires_at->format('d/m/Y')]) }}
                                </span>
                            @else
                                <span class="text-red-700 dark:text-red-400">{{ __('Scoperto') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Certificati validi') }}</h3>

                @if ($validCertificates->isNotEmpty())
                    <ul class="mt-4 space-y-2">
                        @foreach ($validCertificates as $certificate)
                            <li class="text-sm text-gray-900 dark:text-gray-100 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                                <span>
                                    {{ ucfirst($certificate->typeDescription()) }}
                                    — {{ __('scade il :date', ['date' => $certificate->expires_at->format('d/m/Y')]) }}
                                    ({{ __(':days giorni rimanenti', ['days' => now()->startOfDay()->diffInDays($certificate->expires_at)]) }})
                                </span>
                                <a href="{{ route('certificates.file', $certificate) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">
                                    {{ __('Visualizza') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Nessun certificato valido.') }}</p>
                @endif
            </div>

            @if ($pendingCertificates->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('In attesa di verifica') }}</h3>
                    <ul class="mt-4 space-y-2">
                        @foreach ($pendingCertificates as $certificate)
                            <li class="text-sm text-gray-900 dark:text-gray-100">
                                {{ ucfirst($certificate->typeDescription()) }}
                                — {{ __('caricato il :date', ['date' => $certificate->created_at->format('d/m/Y')]) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Storico') }}</h3>

                @if ($history->isNotEmpty())
                    <ul class="mt-4 space-y-3">
                        @foreach ($history as $certificate)
                            <li class="text-sm border-b border-gray-100 dark:border-gray-700 pb-3">
                                <div class="flex items-center justify-between text-gray-900 dark:text-gray-100">
                                    <span>
                                        {{ ucfirst($certificate->typeDescription()) }}
                                        — {{ $certificate->status->label() }}
                                        @if ($certificate->status === \App\Enums\CertificateStatus::Approved && $coverageService->isSuperseded($certificate))
                                            <span class="text-gray-500 dark:text-gray-400">({{ __('superato') }})</span>
                                        @endif
                                        @if ($certificate->isRetentionDeleted())
                                            <span class="text-gray-500 dark:text-gray-400">({{ __('eliminato per conservazione') }})</span>
                                        @endif
                                    </span>
                                    @if ($certificate->file_path)
                                        <a href="{{ route('certificates.file', $certificate) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">
                                            {{ __('Visualizza') }}
                                        </a>
                                    @endif
                                </div>
                                @if ($certificate->status === \App\Enums\CertificateStatus::Rejected && $certificate->rejection_reason)
                                    <p class="mt-1 text-red-700 dark:text-red-400">{{ $certificate->rejection_reason }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Nessun certificato caricato.') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
