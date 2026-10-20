<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Certificati') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200 text-sm rounded-md p-4">
                    @switch(session('status'))
                        @case('certificate-approved') {{ __('Certificato approvato.') }} @break
                        @case('certificate-rejected') {{ __('Certificato rifiutato ed email inviata all\'atleta.') }} @break
                        @case('certificate-uploaded') {{ __('Certificato caricato.') }} @break
                    @endswitch
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <form method="GET" class="mb-4 space-y-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        <select name="status" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="" @selected($status === '')>{{ __('Da approvare') }}</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>

                        <select name="club_type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Società (tutte)') }}</option>
                            @foreach ($clubTypes as $clubType)
                                <option value="{{ $clubType->value }}" @selected(request('club_type') === $clubType->value)>{{ $clubType->label() }}</option>
                            @endforeach
                        </select>

                        <select name="entity" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Ente (tutti)') }}</option>
                            @foreach ($entities as $entity)
                                <option value="{{ $entity->value }}" @selected(request('entity') === $entity->value)>{{ $entity->label() }}</option>
                            @endforeach
                        </select>

                        <select name="discipline" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Disciplina (tutte)') }}</option>
                            @foreach ($disciplines as $discipline)
                                <option value="{{ $discipline->value }}" @selected(request('discipline') === $discipline->value)>{{ $discipline->label() }}</option>
                            @endforeach
                        </select>

                        <select name="training_location_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Sede (tutte)') }}</option>
                            @foreach ($trainingLocations as $location)
                                <option value="{{ $location->id }}" @selected((string) request('training_location_id') === (string) $location->id)>{{ $location->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-secondary-button type="submit">{{ __('Filtra') }}</x-secondary-button>
                        <a href="{{ route('admin.certificates.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="text-sm text-indigo-600 dark:text-indigo-400 underline">
                            {{ __('Esporta CSV') }}
                        </a>
                    </div>
                </form>

                @forelse ($certificates as $certificate)
                    <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-700 rounded-md p-4 mb-3">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                <span class="font-medium">{{ $certificate->user->fullName() }}</span>
                                — {{ ucfirst($certificate->typeDescription()) }}
                                — {{ __('scadenza') }} {{ $certificate->expires_at->format('d/m/Y') }}
                                — {{ $certificate->status->label() }}
                                <span class="text-gray-500 dark:text-gray-400">
                                    ({{ __('caricato da') }} {{ $certificate->uploadedBy->fullName() }})
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <a href="{{ route('certificates.file', $certificate) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">
                                    {{ __('Visualizza file') }}
                                </a>
                                @if ($certificate->status === \App\Enums\CertificateStatus::Pending)
                                    <button type="button" @click="open = ! open" class="text-indigo-600 dark:text-indigo-400 underline">
                                        {{ __('Verifica') }}
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($certificate->status === \App\Enums\CertificateStatus::Rejected)
                            <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ $certificate->rejection_reason }}</p>
                        @endif

                        @if ($certificate->status === \App\Enums\CertificateStatus::Pending)
                            <div x-show="open" x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <form method="POST" action="{{ route('admin.certificates.approve', $certificate) }}" class="space-y-3">
                                    @csrf
                                    @method('put')
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Approva (correggi se necessario)') }}</h4>
                                    <div>
                                        <x-input-label :value="__('Data di scadenza')" />
                                        <x-text-input name="expires_at" type="date" class="mt-1 block w-full text-sm"
                                                      value="{{ $certificate->expires_at->format('Y-m-d') }}" required />
                                    </div>
                                    <div x-data="{ type: @js($certificate->type->value) }">
                                        <x-input-label :value="__('Tipo')" />
                                        <select name="type" x-model="type" required
                                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm mt-1 block w-full text-sm">
                                            @foreach (\App\Enums\CertificateType::cases() as $type)
                                                <option value="{{ $type->value }}" @selected($certificate->type->value === $type->value)>{{ $type->label() }}</option>
                                            @endforeach
                                        </select>
                                        <div class="mt-2" x-show="type === 'competitive'" x-cloak>
                                            <x-input-label :value="__('Disciplina')" />
                                            <select name="discipline"
                                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm mt-1 block w-full text-sm">
                                                @foreach (\App\Enums\Discipline::cases() as $discipline)
                                                    <option value="{{ $discipline->value }}" @selected($certificate->discipline?->value === $discipline->value)>
                                                        {{ $discipline->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <x-primary-button type="submit">{{ __('Approva') }}</x-primary-button>
                                </form>

                                <form method="POST" action="{{ route('admin.certificates.reject', $certificate) }}" class="space-y-3">
                                    @csrf
                                    @method('put')
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Rifiuta') }}</h4>
                                    <div>
                                        <x-input-label :value="__('Motivazione (obbligatoria)')" />
                                        <textarea name="rejection_reason" rows="3" required
                                                  class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm mt-1 block w-full text-sm"></textarea>
                                    </div>
                                    <x-danger-button type="submit">{{ __('Rifiuta') }}</x-danger-button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Nessun certificato trovato.') }}</p>
                @endforelse

                <div class="mt-4">
                    {{ $certificates->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
