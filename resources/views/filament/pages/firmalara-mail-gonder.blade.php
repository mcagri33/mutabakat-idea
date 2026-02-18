<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filtreler ve Mail İçeriği Formu --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        {{-- Firma Listesi --}}
        <div class="fi-section overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-6 sm:p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Firma Listesi</h3>
                <div class="flex items-center gap-2">
                    <x-filament::button
                        wire:click="toggleSelectAll"
                        color="gray"
                        size="sm"
                        outlined
                    >
                        {{ count($selectedCustomerIds) >= count($tableRows) ? 'Seçimi Kaldır' : 'Tümünü Seç' }}
                    </x-filament::button>
                    <x-filament::button
                        wire:click="sendMails"
                        wire:confirm="Seçilen {{ count($selectedCustomerIds) }} firmaya mail gönderilecek. CC: mutabakat@ideadenetim.com.tr, okanyurdbulan@hotmail.com, okany@ideadenetim.com.tr. Devam edilsin mi?"
                        color="success"
                        size="sm"
                        icon="heroicon-o-paper-airplane"
                    >
                        Seçilenlere Mail Gönder ({{ count($selectedCustomerIds) }})
                    </x-filament::button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="fi-table w-full table-auto divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-12"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Firma</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Yıl</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Banka</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gönderilen</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">E-Posta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                        @forelse($tableRows as $row)
                            @php
                                $customerId = $row['customer_id'] ?? 0;
                                $customer = \App\Models\Customer::find($customerId);
                                $hasEmail = $customer && $customer->email && filter_var($customer->email, FILTER_VALIDATE_EMAIL);
                                $statusLabels = [
                                    'hepsi_gonderildi' => 'Hepsi gönderildi',
                                    'manuel_ile' => 'Manuel ile',
                                    'kismen' => 'Kısmen',
                                    'gonderilmedi' => 'Gönderilmedi',
                                    'banka_eklenmemis' => 'Banka eklenmemiş',
                                ];
                                $status = $statusLabels[$row['status'] ?? ''] ?? $row['status'] ?? '-';
                            @endphp
                            <tr class="divide-x divide-gray-200 dark:divide-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ !$hasEmail ? 'opacity-60' : '' }}">
                                <td class="px-4 py-3">
                                    @if($hasEmail)
                                        <input
                                            type="checkbox"
                                            wire:click="toggleCustomer({{ $customerId }})"
                                            @checked($this->isSelected($customerId))
                                            class="rounded border-gray-300 dark:border-gray-600"
                                        >
                                    @else
                                        <span class="text-gray-400" title="E-posta yok">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $row['customer_name'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['year'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['bank_count'] ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['sent_count'] ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <x-filament::badge :color="match($row['status'] ?? '') {
                                        'hepsi_gonderildi' => 'success',
                                        'manuel_ile' => 'warning',
                                        'kismen' => 'warning',
                                        'gonderilmedi' => 'danger',
                                        'banka_eklenmemis' => 'gray',
                                        default => 'gray',
                                    }" size="sm">{{ $status }}</x-filament::badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    @if($hasEmail)
                                        {{ Str::limit($customer->email, 25) }}
                                    @else
                                        <span class="text-amber-600">E-posta yok</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Firma bulunamadı. Önce "Firmaları Yükle" butonuna tıklayın.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
