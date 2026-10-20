<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Log invii promemoria e avvisi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <form method="GET" class="flex items-end gap-4">
                    <div>
                        <x-input-label for="status" :value="__('Stato')" />
                        <select id="status" name="status" onchange="this.form.submit()"
                                class="mt-1 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="">{{ __('Tutti') }}</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 sm:p-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <th class="py-2 pr-4">{{ __('Data') }}</th>
                            <th class="py-2 pr-4">{{ __('Atleta') }}</th>
                            <th class="py-2 pr-4">{{ __('Tipo') }}</th>
                            <th class="py-2 pr-4">{{ __('Stato') }}</th>
                            <th class="py-2 pr-4">{{ __('Errore') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($notifications as $notification)
                            <tr>
                                <td class="py-2 pr-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $notification->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-900 dark:text-gray-100">{{ $notification->certificate->user->fullName() }}</td>
                                <td class="py-2 pr-4 text-sm text-gray-900 dark:text-gray-100">{{ $notification->type->label() }}</td>
                                <td class="py-2 pr-4 text-sm">
                                    @php
                                        $color = match ($notification->status) {
                                            \App\Enums\NotificationStatus::Sent => 'text-green-700 dark:text-green-400',
                                            \App\Enums\NotificationStatus::Failed => 'text-red-700 dark:text-red-400',
                                            \App\Enums\NotificationStatus::Queued => 'text-gray-500 dark:text-gray-400',
                                        };
                                    @endphp
                                    <span class="{{ $color }}">{{ $notification->status->label() }}</span>
                                </td>
                                <td class="py-2 pr-4 text-sm text-red-700 dark:text-red-400">{{ $notification->error_message }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Nessun invio registrato.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
