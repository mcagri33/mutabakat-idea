<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerBank;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerBankImport
{
    public function import($filePath): array
    {
        $results = [
            'success' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // İlk satır başlık, atla
            $headers = array_shift($rows);
            
            // Başlıkları normalize et (küçük harf, boşlukları alt çizgiye çevir)
            $normalizedHeaders = [];
            foreach ($headers as $index => $header) {
                $normalizedHeaders[$index] = strtolower(str_replace([' ', '-', '/'], '_', trim($header)));
            }

            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2; // Excel satır numarası (başlık + 1)
                
                try {
                    // Satırı başlıklarla eşleştir
                    $rowData = [];
                    foreach ($normalizedHeaders as $colIndex => $header) {
                        $rowData[$header] = $row[$colIndex] ?? null;
                    }

                    // Gerekli alanları kontrol et
                    $customerName = $this->getValue($rowData, ['firma_adi', 'firma adi']);
                    $bankName = $this->getValue($rowData, ['banka_adi', 'banka adi']);
                    $email = $this->getValue($rowData, ['e_posta', 'e-posta', 'eposta']);

                    if (!$customerName || !$bankName || !$email) {
                        $results['skipped']++;
                        $results['errors'][] = "Satır {$rowNumber}: Eksik bilgi (Firma, Banka veya E-posta)";
                        continue;
                    }

                    // Firma bul
                    $customer = Customer::where('name', $customerName)
                        ->orWhere('company', $customerName)
                        ->first();

                    if (!$customer) {
                        $results['skipped']++;
                        $results['errors'][] = "Satır {$rowNumber}: Firma bulunamadı: {$customerName}";
                        continue;
                    }

                    // E-posta ile mevcut kaydı kontrol et
                    $existing = CustomerBank::where('customer_id', $customer->id)
                        ->where('officer_email', $email)
                        ->first();

                    $data = [
                        'customer_id' => $customer->id,
                        'bank_name' => $bankName,
                        'branch_name' => $this->getValue($rowData, ['sube_adi', 'sube adi']),
                        'officer_name' => $this->getValue($rowData, ['yetkili_masa_memuru', 'yetkili / masa memuru', 'yetkili']),
                        'officer_email' => $email,
                        'officer_phone' => $this->getValue($rowData, ['telefon', 'phone']),
                        'is_active' => $this->parseBoolean($this->getValue($rowData, ['aktif', 'active'], '1')),
                    ];

                    if ($existing) {
                        // Mevcut kaydı güncelle
                        $existing->update($data);
                        $results['updated']++;
                    } else {
                        // Yeni kayıt oluştur
                        CustomerBank::create($data);
                        $results['success']++;
                    }
                } catch (\Exception $e) {
                    $results['skipped']++;
                    $results['errors'][] = "Satır {$rowNumber}: " . $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = "Dosya okuma hatası: " . $e->getMessage();
        }

        return $results;
    }

    private function getValue(array $rowData, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($rowData[$key]) && $rowData[$key] !== null && $rowData[$key] !== '') {
                return trim($rowData[$key]);
            }
        }
        return $default;
    }

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'evet', 'yes', 'aktif', 'active']);
    }
}

