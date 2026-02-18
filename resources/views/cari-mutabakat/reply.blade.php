<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cari Hesap Mutabakat Cevabı</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { font-size: 1.5rem; margin-top: 0; color: #1e3a5f; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table th, .info-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .info-table th { width: 140px; color: #666; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; }
        .form-group input[type="text"], .form-group input[type="file"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .radio-group { display: flex; gap: 20px; margin-top: 8px; }
        .radio-group label { font-weight: normal; display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        .error { color: #dc2626; font-size: 13px; margin-top: 4px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cari Hesap Mutabakat Cevabı</h1>
        <p>{{ $item->request->customer->name }} - {{ $item->request->year }} Yılı</p>

        <table class="info-table">
            <tr><th>Ünvan</th><td>{{ $item->unvan }}</td></tr>
            <tr><th>Cari Kodu</th><td>{{ $item->cari_kodu }}</td></tr>
            <tr><th>Tarih</th><td>{{ $item->tarih?->format('d.m.Y') ?? now()->format('d.m.Y') }}</td></tr>
            <tr><th>Mutabakat Dönemi</th><td>31.12.{{ $item->request->year ?? now()->year }}</td></tr>
            <tr><th>Bakiye</th><td>{{ number_format($item->bakiye ?? 0, 2, ',', '.') }} {{ $item->pb ?? 'TL' }} ({{ $item->bakiye_tipi }})</td></tr>
        </table>

        <form action="{{ route('cari-mutabakat.reply.store', $item->token) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label>Cevabınız *</label>
                <div class="radio-group">
                    <label><input type="radio" name="cevap" value="mutabıkız" required> Mutabıkız</label>
                    <label><input type="radio" name="cevap" value="mutabık_değiliz"> Mutabık değiliz</label>
                </div>
            </div>

            <div class="form-group">
                <label>Cevaplayan Ünvan</label>
                <input type="text" name="cevaplayan_unvan" value="{{ old('cevaplayan_unvan', $item->unvan) }}" placeholder="Örn: ABC Ltd. Şti.">
            </div>

            <div class="form-group">
                <label>Açıklama</label>
                <textarea name="aciklama" placeholder="Varsa ek açıklama...">{{ old('aciklama') }}</textarea>
            </div>

            <div class="form-group">
                <label>Ekstre (PDF, JPG, PNG - max 5MB)</label>
                <input type="file" name="ekstre" accept=".pdf,.jpg,.jpeg,.png">
                @error('ekstre')<span class="error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label>E-İmzalı Form (PDF, JPG, PNG - max 5MB)</label>
                <input type="file" name="e_imzali_form" accept=".pdf,.jpg,.jpeg,.png">
                @error('e_imzali_form')<span class="error">{{ $message }}</span>@enderror
            </div>

            <button type="submit" class="btn">Gönder</button>
        </form>

        <div class="footer">
            <strong>İdea Bağımsız Denetim Anonim Şirketi</strong><br>
            E-Mail: mutabakat@ideadenetim.com.tr | Tel: 0507 508 2094
        </div>
    </div>
</body>
</html>
