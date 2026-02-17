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

<h2>2. Firma Bazlı Mail Raporu</h2>
<p>Firmaların gönderim durumu, bankadan cevap durumu ve özet bilgileri.</p>
@if(empty($mailReportRows))
    <p class="empty">Henüz firma kaydı bulunmuyor.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Firma</th>
                <th>Yıl</th>
                <th>Gönderildi</th>
                <th>Bankadan Cevap Geldi</th>
                <th>Bankadan Cevap Bekliyor</th>
                <th>Durum / Özet</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mailReportRows as $row)
            <tr>
                <td>{{ $row['customer_name'] ?? '-' }}</td>
                <td>{{ $row['year'] ?? '-' }}</td>
                <td>
                    @php
                        $sent = $row['sent_count'] ?? 0;
                        $manual = $row['manual_count'] ?? 0;
                    @endphp
                    @if($sent > 0 && $manual > 0)
                        {{ $sent }} banka + {{ $manual }} manuel
                    @elseif($sent > 0)
                        {{ $sent }} banka
                    @elseif($manual > 0)
                        {{ $manual }} manuel
                    @else
                        -
                    @endif
                </td>
                <td>{{ ($row['reply_received_count'] ?? 0) > 0 ? ($row['reply_received_count'] . ' banka') : '-' }}</td>
                <td>{{ ($row['reply_pending_count'] ?? 0) > 0 ? ($row['reply_pending_count'] . ' banka') : '-' }}</td>
                <td>{{ $row['summary'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Toplam: {{ count($mailReportRows) }}</strong> firma</p>
@endif

<hr style="margin-top: 30px; border: none; border-top: 1px solid #ddd;">
<p style="font-size: 12px; color: #888;">
    Bu rapor yalnızca admin kullanıcılara gönderilir. İdea Mutabakat Yönetim Sistemi.
</p>

</body>
</html>
