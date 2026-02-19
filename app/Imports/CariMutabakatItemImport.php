<?php

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\IOFactory;

class CariMutabakatItemImport
{
    /**
     * Excel dosyasını parse eder ve form için items dizisi döndürür.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $filePath): array
    {
        $items = [];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return [];
            }

            $headers = array_shift($rows);
            $normalizedHeaders = $this->normalizeHeaders($headers);

            foreach ($rows as $rowIndex => $row) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $rowData = [];
                foreach ($normalizedHeaders as $colIndex => $header) {
                    $rowData[$header] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : null;
                    if ($rowData[$header] === '') {
                        $rowData[$header] = null;
                    }
                }

                $cariKodu = $this->getValue($rowData, ['cari_kodu', 'carikodu', 'cari_kod', 'cari']);
                $unvan = $this->getValue($rowData, ['unvan', 'unvan_adi', 'firma_adi', 'firma']);
                $email = $this->getValue($rowData, ['e_posta', 'e_posta', 'eposta', 'email', 'e_mail', 'mail']);

                if (empty($cariKodu) || empty($unvan) || empty($email)) {
                    continue;
                }

                $bakiyeTipi = $this->getValue($rowData, ['b_a', 'bakiye_tipi', 'bakiye_tip', 'borc_alacak'], 'Borç');
                $bakiyeTipi = in_array($bakiyeTipi, ['Alacak', 'alacak']) ? 'Alacak' : 'Borç';

                $items[] = [
                    'hesap_tipi' => $this->getValue($rowData, ['hesap_tipi', 'hesap tipi']),
                    'referans' => $this->getValue($rowData, ['referans']),
                    'cari_kodu' => $cariKodu,
                    'unvan' => $unvan,
                    'email' => $email,
                    'cc_email' => $this->getValue($rowData, ['cc_e_posta', 'cc e-posta', 'cc_email', 'cc']),
                    'tel_no' => $this->getValue($rowData, ['tel_no', 'telefon', 'phone']),
                    'bakiye_tipi' => $bakiyeTipi,
                    'bakiye' => $this->parseNumeric($this->getValue($rowData, ['bakiye', 'bakiye_tutari', 'tutar', 'balance']), 0),
                    'pb' => $this->getValue($rowData, ['pb', 'para_birimi'], 'TL'),
                    'karsiligi' => $this->parseNumeric($this->getValue($rowData, ['karsiligi', 'yabanci_pb_karsiligi'])),
                    'karsiligi_pb' => $this->getValue($rowData, ['karsiligi_pb', 'karsiligi pb'], 'TRY'),
                ];
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Excel dosyası okunamadı: ' . $e->getMessage());
        }

        return $items;
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        $turkishChars = ['ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c'];

        foreach ($headers as $index => $header) {
            $h = mb_strtolower(trim((string) $header), 'UTF-8');
            $h = str_replace([' ', '-', '/'], '_', $h);
            $h = strtr($h, $turkishChars);
            $normalized[$index] = $h;
        }

        return $normalized;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function getValue(array $rowData, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($rowData[$key]) && $rowData[$key] !== null && $rowData[$key] !== '') {
                return trim((string) $rowData[$key]);
            }
        }
        return $default;
    }

    private function parseNumeric($value, $default = null)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $value = (string) $value;
        // Binlik ayraçlarını kaldır (boşluk, nokta, virgül) - hem Türkçe (288.507.530) hem US (288,507,530) formatını destekler
        $value = str_replace([' ', '.', ','], ['', '', ''], $value);
        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * Örnek Excel şablonu için başlık satırı.
     */
    public static function getTemplateHeaders(): array
    {
        return [
            'Hesap Tipi',
            'Referans',
            'Cari Kodu',
            'Ünvan',
            'E-Posta',
            'CC E-Posta',
            'Tel No',
            'B/A',
            'Bakiye',
            'PB',
            'Karşılığı',
            'Karşılığı PB',
        ];
    }
}
