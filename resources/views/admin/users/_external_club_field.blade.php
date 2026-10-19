@php
    $fieldId = $fieldId ?? 'external_club_search';
    $initialName = $initialName ?? '';
    $initialId = $initialId ?? '';
@endphp

<div
    x-data="{
        query: @js($initialName),
        results: [],
        selectedId: @js($initialId ? (string) $initialId : ''),
        open: false,
        search() {
            this.selectedId = '';
            if (this.query.length < 2) { this.results = []; return; }
            fetch(`{{ route('admin.external-clubs.search') }}?q=${encodeURIComponent(this.query)}`)
                .then(r => r.json())
                .then(data => { this.results = data; this.open = true; });
        },
        select(item) {
            this.query = item.label;
            this.selectedId = String(item.id);
            this.results = [];
            this.open = false;
        },
    }"
    class="relative"
>
    <x-input-label :for="$fieldId" :value="__('Nome società')" />
    <input
        :id="'{{ $fieldId }}'"
        type="text"
        name="external_club_name"
        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full"
        autocomplete="off"
        x-model="query"
        @input.debounce.300ms="search()"
        @focusout="setTimeout(() => open = false, 150)"
        placeholder="{{ __('Digita il nome della società…') }}"
    >
    <input type="hidden" name="external_club_id" :value="selectedId">

    <ul
        x-show="open && results.length > 0"
        x-cloak
        class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-auto"
    >
        <template x-for="item in results" :key="item.id">
            <li
                @click="select(item)"
                class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-gray-700 cursor-pointer"
                x-text="item.label"
            ></li>
        </template>
    </ul>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        {{ __('Scegli una società esistente o digitane una nuova.') }}
    </p>
</div>
