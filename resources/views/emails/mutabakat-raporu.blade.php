<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mutabakat Raporu</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        h2 { margin-top: 24px; margin-bottom: 12px; font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; }
        th { background: #f5f5f5; font-weight: 600; }
        tr:nth-child(even) { background: #fafafa; }
        .meta { color: #666; font-size: 12px; margin-bottom: 20px; }
        .empty { color: #888; font-style: italic; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-sent { background: #dbeafe; color: #1e40af; }
        .badge-failed { background: #fee2e2; color: #b91c1c; }
        .badge-received { background: #d1fae5; color: #065f46; }
        .badge-completed { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

<div class="meta">
    Rapor tarihi: <strong>{{ $reportDate }}</strong>
</div>

<h2>1. Bankası Eklenmemiş Firmalar</h2>
<p>Aşağıdaki firmalara henüz banka tanımı eklenmemiştir.</p>
@if($customersWithoutBanks->isEmpty())
    <p class="empty">Tüm firmalarda en az bir banka tanımlı.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Firma Adı</th>
                <th>E-posta</th>
                <th>Telefon</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customersWithoutBanks as $customer)
            <tr>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->email ?? '-' }}</td>
                <td>{{ $customer->phone ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Toplam: {{ $customersWithoutBanks->count() }}</strong> firma</p>
@endif

<h2>2. Firma – Banka Bazlı Mail Raporu</h2>
<p>Gönderilen mutabakat mailleri, gönderim tarihi ve cevap durumu.</p>
@if(empty($mailReportRows))
    <p class="empty">Henüz mail kaydı bulunmuyor.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Firma</th>
                <th>Banka</th>
                <th>Yıl</th>
                <th>Gönderim Tarihi</th>
                <th>Mail Durumu</th>
                <th>Cevap Durumu</th>
                <th>Cevap Tarihi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mailReportRows as $row)
            <tr>
                <td>{{ $row['customer_name'] ?? '-' }}</td>
                <td>{{ $row['bank_name'] ?? '-' }}</td>
                <td>{{ $row['year'] ?? '-' }}</td>
                <td>{{ $row['mail_sent_at'] ?? '-' }}</td>
                <td>
                    @php
                        $mailStatus = $row['mail_status'] ?? 'pending';
                        $mailLabel = match($mailStatus) {
                            'sent' => 'Gönderildi',
                            'failed' => 'Hata',
                            default => 'Beklemede',
                        };
                        $mailClass = match($mailStatus) {
                            'sent' => 'badge-sent',
                            'failed' => 'badge-failed',
                            default => 'badge-pending',
                        };
                    @endphp
                    <span class="badge {{ $mailClass }}">{{ $mailLabel }}</span>
                </td>
                <td>
                    @php
                        $replyStatus = $row['reply_status'] ?? 'pending';
                        $replyLabel = match($replyStatus) {
                            'received' => 'Geldi',
                            'completed' => 'Tamamlandı',
                            default => 'Beklemede',
                        };
                        $replyClass = in_array($replyStatus, ['received', 'completed']) ? 'badge-received' : 'badge-pending';
                    @endphp
                    <span class="badge {{ $replyClass }}">{{ $replyLabel }}</span>
                </td>
                <td>{{ $row['reply_received_at'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Toplam: {{ count($mailReportRows) }}</strong> kayıt</p>
@endif

<hr style="margin-top: 30px; border: none; border-top: 1px solid #ddd;">
<p style="font-size: 12px; color: #888;">
    Bu rapor yalnızca admin kullanıcılara gönderilir. İdea Mutabakat Yönetim Sistemi.
</p>

</body>
</html>
