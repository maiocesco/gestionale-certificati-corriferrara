@php
    $editingLocation = $location ?? null;
@endphp

<div>
    <x-input-label for="name" :value="__('Nome sede')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  :value="old('name', $editingLocation?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="city" :value="__('Città')" />
    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                  :value="old('city', $editingLocation?->city)" required />
    <x-input-error :messages="$errors->get('city')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="address" :value="__('Indirizzo')" />
    <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                  :value="old('address', $editingLocation?->address)" />
    <x-input-error :messages="$errors->get('address')" class="mt-2" />
</div>
