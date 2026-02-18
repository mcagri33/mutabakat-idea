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
        'vergi_no' => '4700620239',
        'adres' => '23 Nisan mahallesi, 241. Sk. Meriç Plaza D:22, PK. 16140 Nilüfer/Bursa',
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

        // Başlık
        $section->addText('CARİ HESAP MUTABAKATI', ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        // Gönderen (İDEA)
        $section->addText('Gönderen', ['bold' => true]);
        $section->addText($this->ideaBilgileri['unvan']);
        $section->addText('Vergi No: ' . $this->ideaBilgileri['vergi_no']);
        $section->addText('Adres: ' . $this->ideaBilgileri['adres']);
        $section->addTextBreak(1);

        $customer = $item->request->customer;
        $referans = $item->referans ?? '-';

        $section->addText('Referans No: ' . $referans);
        $section->addTextBreak(1);

        // Mutabakat İçeriği
        $section->addText('Mutabakat İçeriği:', ['bold' => true]);
        $section->addTextBreak(1);

        $icerikData = [
            ['Denetlenen Firma', $customer->company ?? $customer->name ?? '-'],
            ['Vergi No', $item->vergi_no ?? '-'],
            ['Adres', '-'],
            ['Tarih', $item->tarih?->format('d.m.Y') ?? '-'],
            ['Hesap Tipi', $item->hesap_tipi ?? '-'],
            ['Cari Kod', $item->cari_kodu ?? '-'],
            ['Ünvan', $item->unvan ?? '-'],
            ['Bakiye Tipi', $item->bakiye_tipi ?? '-'],
            ['Bakiye', $this->formatBakiye($item)],
            ['Yabancı PB Karşılığı', $item->karsiligi ? number_format((float) $item->karsiligi, 2, ',', '.') : '-'],
        ];

        $this->addKeyValueTable($section, $icerikData);
        $section->addTextBreak(1);

        // Mutabakat Cevabı
        $section->addText('Mutabakat Cevabı:', ['bold' => true]);
        $section->addTextBreak(1);

        $cevapLabel = $reply->cevap === 'mutabıkız' ? 'Mutabıkız' : 'Mutabık Değiliz';
        $ekstreDurum = $reply->ekstre_path ? 'Yüklendi' : 'Yok';
        $eImzaliDurum = $reply->e_imzali_form_path ? 'Yüklendi' : 'Yok';

        $cevapData = [
            ['Cevaplayan Firma', $reply->cevaplayan_unvan ?? '-'],
            ['Vergi No', $reply->cevaplayan_vergi_no ?? '-'],
            ['Cevap', $cevapLabel],
            ['Açıklama', $reply->aciklama ?? '-'],
            ['Ekstre Yükle', $ekstreDurum],
            ['E-imzalı Form Yükle', $eImzaliDurum],
        ];

        $this->addKeyValueTable($section, $cevapData);

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

    protected function formatBakiye(CariMutabakatItem $item): string
    {
        $bakiye = $item->bakiye ?? 0;
        $pb = $item->pb ?? 'TL';
        return number_format((float) $bakiye, 2, ',', '.') . ' ' . $pb;
    }

    protected function addKeyValueTable($section, array $data): void
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 50,
        ]);

        foreach ($data as [$key, $value]) {
            $table->addRow();
            $table->addCell(3000)->addText($key, ['bold' => true]);
            $table->addCell(5000)->addText((string) $value);
        }
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
