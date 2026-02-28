<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Hatırlatma Raporu</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        h2 { margin-top: 24px; margin-bottom: 12px; font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; }
        th { background: #f5f5f5; font-weight: 600; }
        tr:nth-child(even) { background: #fafafa; }
        .meta { color: #666; font-size: 12px; margin-bottom: 20px; }
        .empty { color: #888; font-style: italic; }
    </style>
</head>
<body>

<div class="meta">Rapor tarihi: <strong>{{ $reportDate }}</strong></div>

<h2>Hatırlatma Maili Kuyruğa Alınanlar</h2>
<p>Cevap bekleyen bu alıcı/satıcılara cari hatırlatma maili kuyruğa alındı. Mailler arka planda gönderilecek.</p>
@if(empty($sentItems))
    <p class="empty">Gönderilecek cari bulunamadı.</p>
@else
    <table>
        <thead><tr><th>Firma</th><th>Ünvan</th><th>Yıl</th></tr></thead>
        <tbody>
            @foreach($sentItems as $row)
            <tr>
                <td>{{ $row['firma'] ?? '-' }}</td>
                <td>{{ $row['unvan'] ?? '-' }}</td>
                <td>{{ $row['yil'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Toplam: {{ count($sentItems) }}</strong> cariye hatırlatma kuyruğa alındı.</p>
@endif

<hr style="margin-top: 30px; border: none; border-top: 1px solid #ddd;">
<p style="font-size: 12px; color: #888;">İdea Mutabakat Yönetim Sistemi – Cari haftalık hatırlatma raporu.</p>
</body>
</html>
