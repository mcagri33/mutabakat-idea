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
            
            // Başlıkları normalize et (küçük harf, boşlukları alt çizgiye çevir, Türkçe karakterleri dönüştür)
            $normalizedHeaders = [];
            foreach ($headers as $index => $header) {
                $normalized = mb_strtolower(trim((string)$header), 'UTF-8');
                $normalized = str_replace([' ', '-', '/'], '_', $normalized);
                // Türkçe karakterleri normalize et
                $turkishChars = ['ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c'];
                $normalized = strtr($normalized, $turkishChars);
                $normalizedHeaders[$index] = $normalized;
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

                    // Gerekli alanları kontrol et - Türkçe karakterler normalize edildiği için sadece 'i' versiyonlarını kontrol et
                    $customerName = $this->getValue($rowData, ['firma_adi', 'firma adi', 'firma']);
                    $bankName = $this->getValue($rowData, ['banka_adi', 'banka adi', 'banka']);
                    $email = $this->getValue($rowData, ['e_posta', 'e-posta', 'eposta', 'email', 'e_mail']);

                    if (empty($customerName) || empty($bankName) || empty($email)) {
                        $results['skipped']++;
                        $missingFields = [];
                        if (empty($customerName)) $missingFields[] = 'Firma Adı';
                        if (empty($bankName)) $missingFields[] = 'Banka Adı';
                        if (empty($email)) $missingFields[] = 'E-posta';
                        
                        // Debug: Mevcut başlıkları göster
                        $availableHeaders = array_keys($rowData);
                        $results['errors'][] = "Satır {$rowNumber}: Eksik bilgi (" . implode(', ', $missingFields) . "). Mevcut başlıklar: " . implode(', ', $availableHeaders);
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
            if (isset($rowData[$key]) && $rowData[$key] !== null) {
                $value = trim((string)$rowData[$key]);
                // Trim sonrası boş string kontrolü
                if ($value !== '') {
                    return $value;
                }
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

