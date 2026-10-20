<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Account email') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                @php
                    $isError = session('status') === 'email-account-test-failed';
                @endphp
                <div class="{{ $isError ? 'bg-red-50 dark:bg-red-900 text-red-700 dark:text-red-200' : 'bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200' }} text-sm rounded-md p-4">
                    @switch(session('status'))
                        @case('email-account-updated') {{ __('Account aggiornato.') }} @break
                        @case('email-account-test-sent') {{ __('Email di prova inviata.') }} @break
                        @case('email-account-test-failed')
                            {{ __('Invio di prova non riuscito.') }}
                            @if (session('testError'))
                                <div class="mt-1 text-xs font-mono break-all">{{ session('testError') }}</div>
                            @endif
                            @break
                    @endswitch
                </div>
            @endif

            @foreach ($purposes as $purpose)
                @php
                    $account = $accounts->get($purpose->value);
                    // I due account condividono gli stessi nomi di campo (from_address, host, ...):
                    // old()/$errors si applicano al form appena inviato, non per forza a questo.
                    // Il marcatore _purpose distingue quale dei due form ha fallito la validazione.
                    $isSubmittedForm = old('_purpose') === $purpose->value;
                    $value = fn (string $field, $default) => $isSubmittedForm ? old($field, $default) : $default;
                    $fieldErrors = fn (string $field) => $isSubmittedForm ? $errors->get($field) : [];
                @endphp
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $purpose->label() }}</h3>

                    <form method="POST" action="{{ route('admin.email-accounts.update', $purpose->value) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_purpose" value="{{ $purpose->value }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label :for="'from_address_'.$purpose->value" :value="__('Indirizzo mittente')" />
                                <x-text-input :id="'from_address_'.$purpose->value" name="from_address" type="email" class="mt-1 block w-full"
                                              :value="$value('from_address', $account?->from_address)" />
                                <x-input-error :messages="$fieldErrors('from_address')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'from_name_'.$purpose->value" :value="__('Nome mittente')" />
                                <x-text-input :id="'from_name_'.$purpose->value" name="from_name" type="text" class="mt-1 block w-full"
                                              :value="$value('from_name', $account?->from_name)" />
                                <x-input-error :messages="$fieldErrors('from_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'host_'.$purpose->value" :value="__('Host SMTP')" />
                                <x-text-input :id="'host_'.$purpose->value" name="host" type="text" class="mt-1 block w-full"
                                              :value="$value('host', $account?->host)" />
                                <x-input-error :messages="$fieldErrors('host')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'port_'.$purpose->value" :value="__('Porta')" />
                                <x-text-input :id="'port_'.$purpose->value" name="port" type="number" class="mt-1 block w-full"
                                              :value="$value('port', $account?->port)" />
                                <x-input-error :messages="$fieldErrors('port')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'encryption_'.$purpose->value" :value="__('Crittografia')" />
                                <select id="{{ 'encryption_'.$purpose->value }}" name="encryption"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    <option value="">—</option>
                                    @foreach (\App\Enums\SmtpEncryption::cases() as $encryption)
                                        <option value="{{ $encryption->value }}" @selected($value('encryption', $account?->encryption?->value) === $encryption->value)>
                                            {{ $encryption->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$fieldErrors('encryption')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'username_'.$purpose->value" :value="__('Username')" />
                                <x-text-input :id="'username_'.$purpose->value" name="username" type="text" class="mt-1 block w-full"
                                              :value="$value('username', $account?->username)" />
                                <x-input-error :messages="$fieldErrors('username')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'password_'.$purpose->value" :value="__('Password')" />
                                <x-text-input :id="'password_'.$purpose->value" name="password" type="password" class="mt-1 block w-full"
                                              autocomplete="new-password" placeholder="{{ $account?->password ? __('Lascia vuoto per non modificarla') : '' }}" />
                                <x-input-error :messages="$fieldErrors('password')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <x-primary-button>{{ __('Salva') }}</x-primary-button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.email-accounts.test', $purpose->value) }}" class="mt-2">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Invia email di prova') }}</x-secondary-button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
