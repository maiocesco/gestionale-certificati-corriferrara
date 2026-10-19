@php
    $editingUser = $user ?? null;
@endphp

<div>
    <x-input-label for="first_name" :value="__('Nome')" />
    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full"
                  :value="old('first_name', $editingUser?->first_name)" required autofocus />
    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="last_name" :value="__('Cognome')" />
    <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full"
                  :value="old('last_name', $editingUser?->last_name)" required />
    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                  :value="old('email', $editingUser?->email)" required />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="birth_date" :value="__('Data di nascita')" />
    <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full"
                  :value="old('birth_date', $editingUser?->birth_date?->format('Y-m-d'))" required />
    <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
</div>

<div class="mt-4">
    @include('admin.users._birth_place_field', ['user' => $editingUser])
</div>

<div class="mt-4">
    <x-input-label for="fiscal_code" :value="__('Codice fiscale')" />
    <x-text-input id="fiscal_code" name="fiscal_code" type="text" class="mt-1 block w-full uppercase"
                  :value="old('fiscal_code', $editingUser?->fiscal_code)" required maxlength="16" />
    <x-input-error :messages="$errors->get('fiscal_code')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="role" :value="__('Ruolo')" />
    <select id="role" name="role" required
            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
        @foreach ($roles as $role)
            <option value="{{ $role->value }}" @selected(old('role', $editingUser?->role?->value) === $role->value)>
                {{ $role->label() }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>
