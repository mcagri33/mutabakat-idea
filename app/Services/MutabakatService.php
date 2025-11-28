<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MutabakatService
{
    /**
     * DOCX template'ini doldur (sadece temel bilgiler) ve PDF'e çevir
     * Kaşe resmi eklenecek (üzerinde imza var)
     * Hesap bilgileri kısmına dokunulmayacak
     *
     * @param \App\Models\ReconciliationRequest $request
     * @param \App\Models\Customer $customer
     * @param \App\Models\ReconciliationBank $bank
     * @return string PDF dosya yolu
     * @throws \Exception
     */
    public function generatePdf($request, $customer, $bank): string
    {
        // Template yolu
        $templatePath = storage_path('app/mutabakat_templates/mutabakat_banka_sablon.docx');
        
        if (!file_exists($templatePath)) {
            throw new \Exception("DOCX template bulunamadı: {$templatePath}");
        }

        // Output dizini oluştur
        $outputDir = storage_path('app/mutabakat/output');
        if (!is_dir($outputDir)) {
            Storage::makeDirectory('mutabakat/output');
        }

        // Template yükle
        try {
            $templateProcessor = new TemplateProcessor($templatePath);
        } catch (\Exception $e) {
            Log::error('DOCX template yüklenemedi', [
                'error' => $e->getMessage(),
                'path' => $templatePath
            ]);
            throw new \Exception("DOCX template yüklenemedi: " . $e->getMessage());
        }

        // Tarih formatı
        $date = now()->format('d.m.Y');

        // Sadece template'deki mevcut placeholder'ları doldur
        $templateProcessor->setValue('tarih', $date);
        $templateProcessor->setValue('yetkili', $bank->officer_name ?? '.....');
        $templateProcessor->setValue('musteri_adi', $customer->name ?? '');
        $templateProcessor->setValue('yil', $request->year ?? date('Y'));
        $templateProcessor->setValue('banka_adi', $bank->bank_name ?? '');

        // Kaşe resmini ekle (üzerinde imza var, ayrı imza gerekmez)
        $this->addStampImage($templateProcessor);

        // Hesap bilgileri kısmına DOKUNULMAYACAK
        // Template'deki mevcut metin olduğu gibi kalacak

        // Geçici DOCX dosyası kaydet
        $tempDocxName = 'mutabakat_' . $bank->id . '_' . time() . '.docx';
        $tempDocxPath = storage_path('app/mutabakat/output/' . $tempDocxName);
        
        try {
            $templateProcessor->saveAs($tempDocxPath);
        } catch (\Exception $e) {
            Log::error('Doldurulmuş DOCX kaydedilemedi', [
                'error' => $e->getMessage(),
                'path' => $tempDocxPath
            ]);
            throw new \Exception("DOCX kaydedilemedi: " . $e->getMessage());
        }

        // PDF'e çevir
        $pdfPath = $this->convertToPdf($tempDocxPath, $outputDir);

        // Geçici DOCX'i sil
        if (file_exists($tempDocxPath)) {
            @unlink($tempDocxPath);
        }

        return $pdfPath;
    }

    /**
     * Kaşe resmini ekle (üzerinde imza da var)
     */
    protected function addStampImage(TemplateProcessor $templateProcessor): void
    {
        // Kaşe resmi yolu (PNG, JPG, JPEG formatları desteklenir)
        $stampPaths = [
            storage_path('app/mutabakat_templates/kase.png'),
            storage_path('app/mutabakat_templates/kase.jpg'),
            storage_path('app/mutabakat_templates/kase.jpeg'),
        ];

        $stampPath = null;
        foreach ($stampPaths as $path) {
            if (file_exists($path)) {
                $stampPath = $path;
                break;
            }
        }

        if ($stampPath) {
            try {
                // Template'de ${kase} placeholder'ı varsa resim ekle
                $templateProcessor->setImageValue('kase', [
                    'path' => $stampPath,
                    'width' => 150,   // Genişlik (piksel) - istediğiniz boyuta ayarlayın
                    'height' => 150,  // Yükseklik (piksel) - istediğiniz boyuta ayarlayın
                    'ratio' => true,  // Oranı koru
                ]);
                Log::info('Kaşe resmi eklendi', ['path' => $stampPath]);
            } catch (\Exception $e) {
                // Placeholder yoksa veya resim eklenemezse uyarı ver ama hata fırlatma
                Log::warning('Kaşe resmi eklenemedi veya placeholder bulunamadı', [
                    'error' => $e->getMessage(),
                    'path' => $stampPath
                ]);
            }
        } else {
            Log::warning('Kaşe resmi bulunamadı', [
                'checked_paths' => $stampPaths
            ]);
        }
    }

    /**
     * DOCX'i LibreOffice ile PDF'e çevir
     */
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

        Log::info('DOCX PDF\'e çevriliyor', [
            'command' => $command,
            'docx' => $docxPath
        ]);

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('PDF dönüşümü başarısız', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output)
            ]);
            throw new \Exception("PDF dönüşümü başarısız. Hata kodu: {$returnCode}");
        }

        $pdfName = pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . $pdfName;

        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF dosyası oluşturulamadı: {$pdfPath}");
        }

        Log::info('PDF başarıyla oluşturuldu', ['pdf_path' => $pdfPath]);

        return $pdfPath;
    }

    /**
     * LibreOffice yükleme yolunu bul
     */
    protected function findLibreOffice(): ?string
    {
        // Linux/Mac için
        $libreOfficeCheck = shell_exec('which libreoffice 2>&1');
        if (!empty($libreOfficeCheck) && strpos($libreOfficeCheck, 'not found') === false) {
            return 'libreoffice';
        }

        // Windows yolları
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