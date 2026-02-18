<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bildirim</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">

    <div style="width: 100%; text-align: right; margin-bottom: 20px;">
        Tarih: {{ now()->format('d.m.Y') }}
    </div>

    <div style="line-height: 1.6; white-space: pre-wrap;">{!! nl2br(e($body)) !!}</div>

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
