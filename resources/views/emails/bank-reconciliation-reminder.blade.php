<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Banka Mutabakat Hatırlatması</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">

    <div style="width: 100%; text-align: right; margin-bottom: 20px;">
        Tarih: {{ now()->format('d.m.Y') }}
    </div>

    <p>Sayın Banka Yetkilisi</p>

    <p style="line-height: 1.6;">
        Daha önce tarafınıza ilettiğimiz <strong>{{ $customer->name }}</strong> şirketinin
        <strong>{{ $request->year }} YILI 31.12.{{ $request->year }}</strong> tarihi itibari banka mutabakat talebine
        henüz cevap alamadığımızı hatırlatmak isteriz.
    </p>

    <p style="line-height: 1.6;">
        İlgili mutabakat mektubunu aşağıda tekrar ekte sunuyoruz. Uygun gördüğünüz en kısa zamanda
        tarafımıza iletilmesini rica ederiz.
    </p>

    <p>Saygılarımızla.</p>

    <hr style="margin-top: 30px;">
    <p style="font-size: 12px; color: #666;">
        İdea Bağımsız Denetim A.Ş. | mutabakat@ideadenetim.com.tr | 0507 508 2094
    </p>
</body>
</html>
