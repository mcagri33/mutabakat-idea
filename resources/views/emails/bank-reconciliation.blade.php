<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Banka Mutabakat Mektubu</title>
</head>

<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">

    <!-- Tarih sağda -->
    <div style="width: 100%; text-align: right; margin-bottom: 20px;">
        Tarih: {{ now()->format('d.m.Y') }}
    </div>

    <!-- Hitap -->
    <p>SAYIN; {{ $bank->officer_name ?? '.....' }}</p>

    <!-- Ana paragraf -->
    <p style="line-height: 1.6;">
        Bankanız nezdindeki <strong>{{ $customer->name }}</strong> şirketinin hesaplara ilişkin
        (aşağıda listelenen detayda, kaşe ve imzalı olarak)
        <strong>{{ $request->year }} YILI 31.12.{{ $request->year }}</strong> tarihi itibari kayıtlarımızda görünen
        bilgilerin ve hesap bakiyelerinin 7 (yedi) gün içerisinde tarafımıza iletilmesini rica ederiz.
    </p>

    <hr style="margin: 30px 0;">

    <!-- Alt bölüm -->
    <p style="font-weight: bold;">Bağımsız Denetim Şirketi Bilgileri;</p>

    <p style="line-height: 1.6;">
        {{ $request->year }} yılı hesaplarımız TC. Kamu Gözetimi Kurumu (KGK) tarafından yetkilendirilmiş
        <strong>İDEA BAĞIMSIZ DENETİM ANONİM ŞİRKETİ</strong> tarafından denetlenmektedir.
    </p>

    <br>

    <p>
        <strong>İdea Bağımsız Denetim Anonim Şirketi</strong><br>
        <strong>KGK Sicil No:</strong> BDK/2015/100<br>
        <strong>Adres:</strong> Ata Bulvarı 23 Nisan Mh. 241 Sk. Meriç Plaza No: 22 Kat 1/1 Nilüfer / Bursa<br>
        <strong>E-Mail:</strong> mutabakat@ideadenetim.com.tr
    </p>

    <hr style="margin-top: 30px;">

</body>
</html>
