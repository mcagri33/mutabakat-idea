<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Cari Hesap Mutabakat Hatırlatması</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">

    <div style="width: 100%; text-align: right; margin-bottom: 20px;">
        Tarih: {{ now()->format('d.m.Y') }}
    </div>

    <p>Sayın {{ $item->unvan }},</p>

    <p style="line-height: 1.6;">
        Daha önce tarafınıza ilettiğimiz <strong>{{ $customer->name }}</strong> şirketinin
        <strong>31.12.{{ $request->year }}</strong> yılı cari hesap mutabakat talebine henüz cevap alamadığımızı
        hatırlatmak isteriz.
    </p>

    <p style="line-height: 1.6;">
        Kayıtlarımızda aşağıdaki bilgiler yer almaktadır. Lütfen mutabakat cevabınızı aşağıdaki link üzerinden
        tarafımıza iletiniz.
    </p>

    <table style="border-collapse: collapse; width: 100%; margin: 20px 0;" border="1" cellpadding="8">
        <tr style="background: #f5f5f5;">
            <th style="text-align: left;">Cari Kodu</th>
            <th style="text-align: left;">Ünvan</th>
            <th style="text-align: left;">Mutabakat Dönemi</th>
            <th style="text-align: right;">Bakiye Tipi</th>
            <th style="text-align: right;">Bakiye</th>
            <th style="text-align: right;">Karşılığı</th>
        </tr>
        <tr>
            <td>{{ $item->cari_kodu }}</td>
            <td>{{ $item->unvan }}</td>
            <td>31.12.{{ $item->request?->year ?? now()->year }}</td>
            <td style="text-align: right;">{{ $item->bakiye_tipi }}</td>
            <td style="text-align: right;">{{ number_format($item->bakiye ?? 0, 2, ',', '.') }} {{ $item->pb ?? 'TL' }}</td>
            <td style="text-align: right;">{{ ($item->karsiligi !== null && $item->karsiligi != 0) ? number_format($item->karsiligi, 2, ',', '.') . ' ' . ($item->karsiligi_pb ?? 'TRY') : '-' }}</td>
        </tr>
    </table>

    <p style="margin: 25px 0;">
        <a href="{{ $replyUrl }}" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
            Mutabakat Cevabı Ver
        </a>
    </p>

    <p style="font-size: 12px; color: #666;">
        Bu link size özeldir ve güvenlik nedeniyle paylaşmayınız.
    </p>

    <hr style="margin-top: 30px;">
    <p style="font-size: 12px; color: #666;">
        İdea Bağımsız Denetim A.Ş. | mutabakat@ideadenetim.com.tr | 0507 508 2094
    </p>
</body>
</html>
