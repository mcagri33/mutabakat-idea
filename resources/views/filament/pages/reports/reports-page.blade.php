<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filtreler Formu --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            {{ $this->form }}
        </div>

        {{-- Özet İstatistikler --}}
        @if(!empty($this->summaryStats))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Talep</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->summaryStats['total'] ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Beklemede</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->summaryStats['pending'] ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full">
                        <x-heroicon-o-clock class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tamamlanan</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->summaryStats['completed'] ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Hata</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->summaryStats['failed'] ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                        <x-heroicon-o-exclamation-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Rapor Tablosu --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mutabakat Talepleri</h3>
                <div class="flex gap-2">
                    <x-filament::button
                        wire:click="exportExcel"
                        icon="heroicon-o-arrow-down-tray"
                        color="success"
                        size="sm"
                    >
                        Excel'e Aktar
                    </x-filament::button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Firma
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Yıl
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tip
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Durum
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Banka Sayısı
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Belge Sayısı
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Oluşturulma
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->reportData as $request)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $request->customer->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $request->year }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $request->type === 'banka' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                        {{ $request->type === 'banka' ? 'Banka' : 'Cari' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                            'mail_sent' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'partially' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'received' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        ];
                                        $statusLabels = [
                                            'pending' => 'Beklemede',
                                            'mail_sent' => 'Mail Gönderildi',
                                            'partially' => 'Kısmi Dönüş',
                                            'received' => 'Tam Dönüş',
                                            'completed' => 'Tamamlandı',
                                            'failed' => 'Hata',
                                        ];
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$request->status] ?? $request->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-center">
                                    {{ $request->banks_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-center">
                                    {{ $request->documents_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $request->created_at->format('d.m.Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Filtre kriterlerinize uygun kayıt bulunamadı.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(count($this->reportData) > 0)
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Toplam <strong>{{ count($this->reportData) }}</strong> kayıt gösteriliyor.
                </p>
            </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>




