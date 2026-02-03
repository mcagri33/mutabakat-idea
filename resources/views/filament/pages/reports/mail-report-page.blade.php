<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Firma – Banka Bazlı Mail Raporu</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gönderilen mutabakat mailleri, gönderim tarihi ve cevap durumu.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Firma</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Banka</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Yıl</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gönderim Tarihi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mail Durumu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cevap Durumu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cevap Tarihi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->mailReportRows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $row['customer_name'] ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $row['bank_name'] ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $row['year'] ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $row['mail_sent_at'] ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $mailStatus = $row['mail_status'] ?? 'pending';
                                        $mailLabel = match($mailStatus) {
                                            'sent' => 'Gönderildi',
                                            'failed' => 'Hata',
                                            default => 'Beklemede',
                                        };
                                        $mailColor = match($mailStatus) {
                                            'sent' => 'success',
                                            'failed' => 'danger',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-filament::badge :color="$mailColor" size="sm">{{ $mailLabel }}</x-filament::badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $replyStatus = $row['reply_status'] ?? 'pending';
                                        $replyLabel = match($replyStatus) {
                                            'received' => 'Geldi',
                                            'completed' => 'Tamamlandı',
                                            default => 'Beklemede',
                                        };
                                        $replyColor = in_array($replyStatus, ['received', 'completed']) ? 'success' : 'gray';
                                    @endphp
                                    <x-filament::badge :color="$replyColor" size="sm">{{ $replyLabel }}</x-filament::badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $row['reply_received_at'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Henüz mail kaydı bulunmuyor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(!empty($this->mailReportRows))
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Toplam <strong>{{ count($this->mailReportRows) }}</strong> kayıt.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
