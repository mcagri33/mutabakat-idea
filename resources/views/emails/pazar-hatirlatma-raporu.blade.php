<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pazar Hatırlatma Raporu</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        h2 { margin-top: 24px; margin-bottom: 12px; font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; }
        th { background: #f5f5f5; font-weight: 600; }
        tr:nth-child(even) { background: #fafafa; }
        .meta { color: #666; font-size: 12px; margin-bottom: 20px; }
        .empty { color: #888; font-style: italic; }
        .warning { background: #fef3c7; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>

<div class="meta">Rapor tarihi: <strong>{{ $reportDate }}</strong></div>

<h2>Hatırlatma Maili Gönderilenler</h2>
<p>Bu bankalara otomatik hatırlatma maili gönderildi (cevap bekleniyor, kaşe talep edilmedi).</p>
@if(empty($sentItems))
    <p class="empty">Gönderilecek banka bulunamadı.</p>
@else
    <table>
        <thead><tr><th>Firma</th><th>Banka</th><th>Yıl</th></tr></thead>
        <tbody>
            @foreach($sentItems as $row)
            <tr>
                <td>{{ $row['firma'] ?? '-' }}</td>
                <td>{{ $row['banka'] ?? '-' }}</td>
                <td>{{ $row['yil'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Toplam: {{ count($sentItems) }}</strong> bankaya hatırlatma gönderildi.</p>
@endif

@if(!empty($excludedKase))
<h2>Hatırlatma Gönderilmedi – Firmadan Kaşe Bekleniyor</h2>
<p>Bu bankalar firmadan kaşe imzalı mektup beklediği için hatırlatma maili gönderilmedi.</p>
<table>
    <thead><tr><th>Firma</th><th>Banka</th></tr></thead>
    <tbody>
        @foreach($excludedKase as $row)
        <tr><td>{{ $row['firma'] ?? '-' }}</td><td>{{ $row['banka'] ?? '-' }}</td></tr>
        @endforeach
    </tbody>
</table>
@endif

@if(!empty($excludedReceived))
<h2>Hatırlatma Gönderilmedi – Cevap Gelmiş</h2>
<p>Bu bankalardan cevap alındığı için hatırlatma maili gönderilmedi.</p>
<table>
    <thead><tr><th>Firma</th><th>Banka</th></tr></thead>
    <tbody>
        @foreach($excludedReceived as $row)
        <tr><td>{{ $row['firma'] ?? '-' }}</td><td>{{ $row['banka'] ?? '-' }}</td></tr>
        @endforeach
    </tbody>
</table>
@endif

@if(!empty($failedItems))
<h2>Gönderilemeyenler</h2>
<div class="warning">Aşağıdaki bankalara hatırlatma maili gönderilemedi.</div>
<table>
    <thead><tr><th>Firma</th><th>Banka</th><th>Hata</th></tr></thead>
    <tbody>
        @foreach($failedItems as $row)
        <tr>
            <td>{{ $row['firma'] ?? '-' }}</td>
            <td>{{ $row['banka'] ?? '-' }}</td>
            <td>{{ $row['hata'] ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<hr style="margin-top: 30px; border: none; border-top: 1px solid #ddd;">
<p style="font-size: 12px; color: #888;">İdea Mutabakat Yönetim Sistemi – Pazar otomatik hatırlatma raporu.</p>
</body>
</html>
