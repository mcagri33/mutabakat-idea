<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Cari Hesap Mutabakat Mektubu</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">

    <div style="width: 100%; text-align: right; margin-bottom: 20px;">
        Tarih: {{ now()->format('d.m.Y') }}
    </div>

    <p>Sayın {{ $item->unvan }},</p>

    <p style="line-height: 1.6;">
        <strong>{{ $customer->name }}</strong> şirketinin {{ $request->year }} yılı hesaplarına ilişkin
        cari hesap mutabakatı sürecinde sizinle iletişime geçmekteyiz.
    </p>

    <p style="line-height: 1.6;">
        Kayıtlarımızda aşağıdaki bilgiler yer almaktadır. Lütfen bu bilgileri kontrol ederek
        mutabakat cevabınızı aşağıdaki link üzerinden tarafımıza iletiniz.
    </p>

    <table style="border-collapse: collapse; width: 100%; margin: 20px 0;" border="1" cellpadding="8">
        <tr style="background: #f5f5f5;">
            <th style="text-align: left;">Cari Kodu</th>
            <th style="text-align: left;">Ünvan</th>
            <th style="text-align: left;">Tarih</th>
            <th style="text-align: left;">Mutabakat Dönemi</th>
            <th style="text-align: right;">Bakiye Tipi</th>
            <th style="text-align: right;">Bakiye</th>
        </tr>
        <tr>
            <td>{{ $item->cari_kodu }}</td>
            <td>{{ $item->unvan }}</td>
            <td>{{ $item->tarih?->format('d.m.Y') ?? now()->format('d.m.Y') }}</td>
            <td>31.12.{{ $item->request?->year ?? now()->year }}</td>
            <td style="text-align: right;">{{ $item->bakiye_tipi }}</td>
            <td style="text-align: right;">{{ number_format($item->bakiye ?? 0, 2, ',', '.') }} {{ $item->pb ?? 'TL' }}</td>
        </tr>
    </table>

    <p style="line-height: 1.6;">
        Mutabakat cevabınızı vermek ve gerekli belgeleri yüklemek için aşağıdaki linke tıklayınız:
    </p>

    <p style="margin: 25px 0;">
        <a href="{{ $replyUrl }}" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
            Mutabakat Cevabı Ver
        </a>
    </p>

    <p style="font-size: 12px; color: #666;">
        Bu link size özeldir ve güvenlik nedeniyle paylaşmayınız.
    </p>

    <hr style="margin-top: 30px;">

    <p>
        <strong>İdea Bağımsız Denetim Anonim Şirketi</strong><br>
        <strong>KGK Sicil No:</strong> BDK/2015/100<br>
        <strong>Adres:</strong> Ata Bulvarı 23 Nisan Mh. 241 Sk. Meriç Plaza No: 22 Kat 1/1 Nilüfer / Bursa<br>
        <strong>E-Mail:</strong> mutabakat@ideadenetim.com.tr<br>
        <strong>Telefon:</strong> 0507 508 2094
    </p>

</body>
</html>
