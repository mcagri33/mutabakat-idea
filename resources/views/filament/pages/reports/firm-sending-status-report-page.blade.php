<x-filament-panels::page>
    <div class="space-y-6 fi-section gap-6">
        {{-- Filtreler --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        {{-- Tablo kartı --}}
        <div class="fi-section overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-6 sm:p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Firma Gönderim Durumu</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Firmaların yıl bazında banka sayısı, sistemden gönderilen ve manuel giriş sayıları ile gönderim durumu.</p>
            </div>

            <div class="px-4 sm:px-6 py-2 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2 bg-gray-50/50 dark:bg-gray-800/50">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Sayfa başına</label>
                    <select
                        wire:model.live="perPage"
                        class="fi-input block w-full max-w-[6rem] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                    >
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Toplam <strong>{{ $totalCount }}</strong> firma
                </p>
            </div>

            <div class="min-w-0 overflow-hidden" style="contain: layout;">
                <div class="overflow-x-auto overflow-y-visible" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="fi-table w-full table-auto divide-y divide-gray-200 dark:divide-gray-700" style="min-width: max-content;">
                        <thead class="divide-y divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-800">
                            <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                                <th class="fi-table-header-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Firma</th>
                                <th class="fi-table-header-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Yıl</th>
                                <th class="fi-table-header-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Banka Sayısı</th>
                                <th class="fi-table-header-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Sistemden Gönderilen</th>
                                <th class="fi-table-header-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Manuel Giriş</th>
                                <th class="fi-table-header-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Durum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                            @if(count($tableRows) > 0)
                                @foreach($tableRows as $row)
                                    <tr class="fi-table-row divide-x divide-gray-200 dark:divide-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="fi-table-cell px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">{{ $row['customer_name'] ?? '-' }}</td>
                                        <td class="fi-table-cell px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $row['year'] ?? '-' }}</td>
                                        <td class="fi-table-cell px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $row['bank_count'] ?? 0 }}</td>
                                        <td class="fi-table-cell px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $row['sent_count'] ?? 0 }}</td>
                                        <td class="fi-table-cell px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $row['manual_count'] ?? 0 }}</td>
                                        <td class="fi-table-cell px-4 py-3 whitespace-nowrap">
                                            @php
                                                $status = $row['status'] ?? 'gonderilmedi';
                                                $statusLabels = [
                                                    'hepsi_gonderildi' => 'Hepsi gönderildi',
                                                    'manuel_ile' => 'Manuel ile',
                                                    'kismen' => 'Kısmen',
                                                    'gonderilmedi' => 'Gönderilmedi',
                                                    'banka_eklenmemis' => 'Banka eklenmemiş',
                                                ];
                                                $statusColors = [
                                                    'hepsi_gonderildi' => 'success',
                                                    'manuel_ile' => 'warning',
                                                    'kismen' => 'warning',
                                                    'gonderilmedi' => 'danger',
                                                    'banka_eklenmemis' => 'gray',
                                                ];
                                                $statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : $status;
                                                $statusColor = isset($statusColors[$status]) ? $statusColors[$status] : 'gray';
                                            @endphp
                                            <x-filament::badge :color="$statusColor" size="sm">{{ $statusLabel }}</x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="fi-table-cell px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Bu yıl için firma bulunamadı veya veri yok.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="fi-section-footer flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 dark:border-gray-700 px-4 py-3 sm:px-6 bg-gray-50/50 dark:bg-gray-800/50">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if($totalCount > 0)
                        {{ $firstItem }} – {{ $lastItem }} / {{ $totalCount }}
                    @else
                        0 kayıt
                    @endif
                </p>
                @if($lastPage > 1)
                    <nav class="flex items-center gap-1">
                        @if($currentPage <= 1)
                            <span class="fi-btn relative grid-flow-dense fi-size-sm fi-btn-color-gray fi-style-outline inline-grid items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-icon px-2 py-2 opacity-50 cursor-not-allowed" aria-hidden="true">‹</span>
                        @else
                            <button type="button" wire:click="setPage({{ $currentPage - 1 }})" class="fi-btn relative grid-flow-dense fi-size-sm fi-btn-color-gray fi-style-outline inline-grid items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-icon px-2 py-2">‹ Önceki</button>
                        @endif
                        <span class="fi-table-cell px-2 py-1 text-sm text-gray-600 dark:text-gray-400">
                            Sayfa {{ $currentPage }} / {{ $lastPage }}
                        </span>
                        @if($currentPage < $lastPage)
                            <button type="button" wire:click="setPage({{ $currentPage + 1 }})" class="fi-btn relative grid-flow-dense fi-size-sm fi-btn-color-gray fi-style-outline inline-grid items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-icon px-2 py-2">Sonraki ›</button>
                        @else
                            <span class="fi-btn relative grid-flow-dense fi-size-sm fi-btn-color-gray fi-style-outline inline-grid items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-icon px-2 py-2 opacity-50 cursor-not-allowed" aria-hidden="true">›</span>
                        @endif
                    </nav>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
