<?php

namespace App\Services;

use App\Models\CariMutabakatItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class CariMutabakatPdfService
{
    protected array $ideaBilgileri = [
        'unvan' => 'İDEA BAĞIMSIZ DENETİM A.Ş.',
        'telefon' => '(224) 261-1530',
        'ilgili_kisi' => '',
    ];

    public function generatePdf(CariMutabakatItem $item): string
    {
        $item->load(['request.customer', 'reply']);
        $reply = $item->reply;

        if (!$reply) {
            throw new \Exception('Cevap bulunamadı. PDF oluşturmak için önce cevap alınmalı.');
        }

        $outputDir = storage_path('app/cari-mutabakat-pdfs');
        if (!is_dir($outputDir)) {
            Storage::makeDirectory('cari-mutabakat-pdfs');
        }

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection(['marginTop' => 600, 'marginBottom' => 600]);
        
        $section->addText(
            'Cari Hesap Mutabakati',
            ['bold' => true, 'size' => 18],
            ['align' => 'center']
        );

        $section->addTextBreak(2);
        
        $request = $item->request;
        $year = $request->year ?? now()->year;
        $donem = '31.12.' . $year;
        $mutabakatTarihi = $reply->replied_at?->format('d.m.Y H:i:s') ?? '-';
        $onaylayanKisi = $reply->cevaplayan_unvan ?? $item->email ?? '-';
        $onayDurumu = $reply->cevap === 'mutabıkız' ? 'Mutabıkız' : 'Mutabık Değiliz';
        $musteriSaticiKodu = $item->cari_kodu ?? $item->referans ?? '-';
        $eImzaliFormDurum = $reply->e_imzali_form_path ? 'Yüklendi' : '-';

        // Gönderen (İDEA)
        $section->addText('Gönderen: ' . $this->ideaBilgileri['unvan']);
        $section->addText('Telefon: ' . $this->ideaBilgileri['telefon']);
        if ($this->ideaBilgileri['ilgili_kisi']) {
            $section->addText('İlgili Kişi: ' . $this->ideaBilgileri['ilgili_kisi']);
        }
        $section->addTextBreak(1);

        // Alıcı
        $section->addText('Alıcı: ' . ($item->unvan ?? '-'));
        $section->addText('Telefon: ' . ($item->tel_no ?? '-'));
        $section->addTextBreak(1);

        // Konu
        $section->addText('Konu: ' . $year . ' Dönemi Cari Hesap Mutabakatı');
        $section->addText('Mutabakat Dönemi: ' . $donem);
        $section->addText('Tarih: ' . ($item->tarih?->format('d.m.Y') ?? '-'));
        $section->addTextBreak(1);

        // Mutabakat Bilgileri
        $section->addText('Mutabakat Bilgileri', ['bold' => true]);
        $rows = [
            ['Mutabakat Dönemi', $donem],
            ['Müşteri-Satıcı Kodu', $musteriSaticiKodu],
            ['Tutar', $this->formatBakiye($item)],
            ['Borç/Alacak', $item->bakiye_tipi ?? '-'],
        ];
        if ($item->karsiligi !== null && $item->karsiligi != 0) {
            $rows[] = ['Yabancı PB Karşılığı', $this->formatKarsiligi($item)];
        }
        $this->addLabelValueRows($section, $rows);
        $section->addTextBreak(1);

        // Onay Bilgileri
        $section->addText('Onay Bilgileri', ['bold' => true]);
        $this->addLabelValueRows($section, [
            ['Onay Durumu', $onayDurumu],
            ['Mutabakat Gönderen Kişi', ''],
            ['Mutabakat Dönemi', $donem],
            ['Onaylayan Kişi', $onaylayanKisi],
            ['Onay Tarihi', $mutabakatTarihi],
            ['E-imzalı Form', $eImzaliFormDurum],
        ]);
        $section->addTextBreak(1);

        // Açıklama
        $aciklama = trim($reply->aciklama ?? '');
        $section->addText('Açıklama', ['bold' => true]);
        $section->addText($aciklama ?: '-');
        $section->addTextBreak(1);

        // Footer
        $section->addText(now()->format('d.m.Y H:i') . "\t" . 'Ideadocs Portal');
        $section->addText('www.ideadocs.com.tr');

        // Geçici DOCX kaydet
        $tempDocxName = 'cari_geri_donus_' . $item->id . '_' . time() . '.docx';
        $tempDocxPath = $outputDir . DIRECTORY_SEPARATOR . $tempDocxName;

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempDocxPath);

        // PDF'e çevir
        $pdfPath = $this->convertToPdf($tempDocxPath, $outputDir);

        // Geçici DOCX sil
        if (file_exists($tempDocxPath)) {
            @unlink($tempDocxPath);
        }

        // PDF'i kalıcı konuma taşı (storage/app/cari-mutabakat-pdfs/item_id/)
        $relativeDir = 'cari-mutabakat-pdfs/' . $item->id;
        Storage::makeDirectory($relativeDir);
        $finalFileName = 'cari_geri_donus_' . $item->id . '.pdf';
        $relativePath = $relativeDir . '/' . $finalFileName;
        $finalFullPath = Storage::path($relativePath);

        if (file_exists($pdfPath)) {
            rename($pdfPath, $finalFullPath);
        }

        Log::info('Cari geri dönüş PDF oluşturuldu', ['item_id' => $item->id, 'path' => $relativePath]);

        return $relativePath;
    }

    protected function addLabelValueRows($section, array $rows): void
    {
        $table = $section->addTable(['borderSize' => 0]);
        foreach ($rows as [$label, $value]) {
            $table->addRow();
            $table->addCell(3500)->addText($label);
            $table->addCell(4500)->addText((string) $value);
        }
    }

    protected function formatBakiye(CariMutabakatItem $item): string
    {
        $bakiye = $item->bakiye ?? 0;
        $pb = $item->pb ?? 'TRY';
        return number_format((float) $bakiye, 2, ',', '.') . ' ' . $pb;
    }

    protected function formatKarsiligi(CariMutabakatItem $item): string
    {
        $karsiligi = $item->karsiligi ?? 0;
        $pb = $item->karsiligi_pb ?? 'TRY';
        return number_format((float) $karsiligi, 2, ',', '.') . ' ' . $pb;
    }

    protected function convertToPdf(string $docxPath, string $outputDir): string
    {
        $libreOfficeCmd = $this->findLibreOffice();

        if (!$libreOfficeCmd) {
            throw new \Exception("LibreOffice bulunamadı. Lütfen LibreOffice'i yükleyin.");
        }

        $escapedDocx = escapeshellarg($docxPath);
        $escapedOutputDir = escapeshellarg($outputDir);

        $command = sprintf(
            '%s --headless --convert-to pdf %s --outdir %s',
            $libreOfficeCmd,
            $escapedDocx,
            $escapedOutputDir
        );

        Log::info('Cari DOCX PDF\'e çevriliyor', ['command' => $command]);

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('Cari PDF dönüşümü başarısız', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output),
            ]);
            throw new \Exception("PDF dönüşümü başarısız. Hata kodu: {$returnCode}");
        }

        $pdfName = pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . $pdfName;

        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF dosyası oluşturulamadı: {$pdfPath}");
        }

        return $pdfPath;
    }

    protected function findLibreOffice(): ?string
    {
        $libreOfficeCheck = shell_exec('which libreoffice 2>&1');
        if (!empty($libreOfficeCheck) && strpos($libreOfficeCheck, 'not found') === false) {
            return 'libreoffice';
        }

        $windowsPaths = [
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        ];

        foreach ($windowsPaths as $path) {
            if (file_exists($path)) {
                return '"' . $path . '"';
            }
        }

        return null;
    }
}
