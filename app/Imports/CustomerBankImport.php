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
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Dosya boş kontrolü
            if (empty($rows)) {
                $results['errors'][] = "Dosya boş veya geçersiz.";
                return $results;
            }

            // İlk satır başlık, atla
            $headers = array_shift($rows);
            
            // Başlıkları normalize et (küçük harf, boşlukları alt çizgiye çevir)
            $normalizedHeaders = [];
            foreach ($headers as $index => $header) {
                $normalizedHeaders[$index] = strtolower(str_replace([' ', '-', '/'], '_', trim((string)$header)));
            }

            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2; // Excel satır numarası (başlık + 1)
                
                // Boş satır kontrolü - tüm hücreler boşsa atla
                $isEmptyRow = true;
                foreach ($row as $cell) {
                    if ($cell !== null && trim((string)$cell) !== '') {
                        $isEmptyRow = false;
                        break;
                    }
                }
                if ($isEmptyRow) {
                    continue; // Boş satırı atla
                }
                
                try {
                    // Satırı başlıklarla eşleştir
                    $rowData = [];
                    foreach ($normalizedHeaders as $colIndex => $header) {
                        // Satır uzunluğu kontrolü
                        $rowData[$header] = isset($row[$colIndex]) ? $row[$colIndex] : null;
                        // Null ve boş string kontrolü
                        if ($rowData[$header] !== null) {
                            $rowData[$header] = trim((string)$rowData[$header]);
                            if ($rowData[$header] === '') {
                                $rowData[$header] = null;
                            }
                        }
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

                    // Firma bul - önce name'e göre, bulamazsa company'e göre ara
                    $customer = Customer::where('name', $customerName)->first();
                    if (!$customer) {
                        $customer = Customer::where('company', $customerName)->first();
                    }

                    if (!$customer) {
                        $results['skipped']++;
                        $results['errors'][] = "Satır {$rowNumber}: Firma bulunamadı: {$customerName}";
                        continue;
                    }

                    // Her zaman yeni kayıt oluştur (güncelleme yapılmaz)
                    $data = [
                        'customer_id' => $customer->id,
                        'bank_name' => $bankName,
                        'branch_name' => $this->getValue($rowData, ['sube_adi', 'sube adi']),
                        'officer_name' => $this->getValue($rowData, ['yetkili_masa_memuru', 'yetkili / masa memuru', 'yetkili']),
                        'officer_email' => $email,
                        'officer_phone' => $this->getValue($rowData, ['telefon', 'phone']),
                        'is_active' => $this->parseBoolean($this->getValue($rowData, ['aktif', 'active'], '1')),
                    ];

                    // Yeni kayıt oluştur
                    CustomerBank::create($data);
                    $results['success']++;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Veritabanı hatası (unique constraint, foreign key vb.)
                    $results['skipped']++;
                    $errorMsg = "Satır {$rowNumber}: Veritabanı hatası";
                    if (str_contains($e->getMessage(), 'foreign key')) {
                        $errorMsg .= " - Firma ID geçersiz";
                    } elseif (str_contains($e->getMessage(), 'constraint')) {
                        $errorMsg .= " - Veri kısıtlaması hatası";
                    } else {
                        $errorMsg .= " - " . $e->getMessage();
                    }
                    $results['errors'][] = $errorMsg;
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
                return trim((string)$rowData[$key]);
            }
        }
        return $default;
    }

    private function parseBoolean($value): bool
    {
        // Null kontrolü
        if ($value === null || $value === '') {
            return true; // Varsayılan olarak true
        }
        
        if (is_bool($value)) {
            return $value;
        }
        
        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'evet', 'yes', 'aktif', 'active'], true);
    }
}

