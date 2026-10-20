@php
    $membership = $membership ?? null;
    $formId = $formId ?? 'membership-form-new';
@endphp

<form
    method="POST"
    action="{{ $action }}"
    x-data="{
        clubType: @js(old('club_type', $membership?->club_type?->value ?? '')),
        entity: @js(old('entity', $membership?->entity?->value ?? '')),
    }"
    class="space-y-4"
>
    @csrf
    @if ($method === 'PUT')
        @method('put')
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="entity-{{ $formId }}" :value="__('Ente')" />
            <select id="entity-{{ $formId }}" name="entity" x-model="entity" required
                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
                <option value="">—</option>
                @foreach (\App\Enums\MembershipEntity::cases() as $entity)
                    <option value="{{ $entity->value }}">{{ $entity->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="discipline-{{ $formId }}" :value="__('Disciplina')" />
            <select id="discipline-{{ $formId }}" name="discipline" required
                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
                @foreach (\App\Enums\Discipline::membershipCases() as $discipline)
                    <option value="{{ $discipline->value }}" @selected(old('discipline', $membership?->discipline?->value) === $discipline->value)>
                        {{ $discipline->label() }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="entity === 'fidal'" x-cloak>
                {{ __('Il tesseramento FIDAL è ammesso solo per l\'atletica leggera.') }}
            </p>
        </div>

        <div>
            <x-input-label for="club_type-{{ $formId }}" :value="__('Società')" />
            <select id="club_type-{{ $formId }}" name="club_type" x-model="clubType" required
                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
                <option value="">—</option>
                @foreach (\App\Enums\ClubType::cases() as $clubType)
                    <option value="{{ $clubType->value }}">{{ $clubType->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="card_number-{{ $formId }}" :value="__('Numero tessera')" />
            <x-text-input id="card_number-{{ $formId }}" name="card_number" type="text" class="mt-1 block w-full"
                          :value="old('card_number', $membership?->card_number)" required />
        </div>

        <div class="sm:col-span-2" x-show="clubType === 'external'" x-cloak>
            @include('admin.users._external_club_field', [
                'fieldId' => 'external_club-'.$formId,
                'initialName' => $membership?->externalClub?->name ?? '',
                'initialId' => $membership?->external_club_id,
            ])
        </div>

        <div class="sm:col-span-2">
            <label class="inline-flex items-center">
                <input type="checkbox" name="is_competitive" value="1"
                       :disabled="entity === 'fidal'" :checked="entity === 'fidal'"
                       @checked(old('is_competitive', $membership?->is_competitive))
                       class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Agonistico') }}</span>
            </label>
        </div>
    </div>

    @if ($errors->any())
        <div class="text-sm text-red-600 dark:text-red-400 space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div>
        <x-primary-button type="submit">{{ $membership ? __('Salva tesseramento') : __('Aggiungi tesseramento') }}</x-primary-button>
    </div>
</form>
