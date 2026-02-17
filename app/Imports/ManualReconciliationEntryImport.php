<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\ManualReconciliationEntry;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ManualReconciliationEntryImport
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

            if (empty($rows)) {
                $results['errors'][] = "Dosya boş veya geçersiz.";
                return $results;
            }

            $headers = array_shift($rows);

            $normalizedHeaders = [];
            foreach ($headers as $index => $header) {
                $normalized = mb_strtolower(trim((string) $header), 'UTF-8');
                $normalized = str_replace([' ', '-', '/'], '_', $normalized);
                $turkishChars = ['ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c'];
                $normalized = strtr($normalized, $turkishChars);
                $normalizedHeaders[$index] = $normalized;
            }

            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;

                $isEmptyRow = true;
                foreach ($row as $cell) {
                    if ($cell !== null && trim((string) $cell) !== '') {
                        $isEmptyRow = false;
                        break;
                    }
                }
                if ($isEmptyRow) {
                    continue;
                }

                try {
                    $rowData = [];
                    foreach ($normalizedHeaders as $colIndex => $header) {
                        $rowData[$header] = $row[$colIndex] ?? null;
                        if ($rowData[$header] !== null) {
                            $rowData[$header] = trim((string) $rowData[$header]);
                            if ($rowData[$header] === '') {
                                $rowData[$header] = null;
                            }
                        }
                    }

                    $customerName = $this->getValue($rowData, ['firma_adi', 'firma_adi', 'firma']);
                    $bankName = $this->getValue($rowData, ['banka_adi', 'banka_adi', 'banka']);
                    $year = $this->getValue($rowData, ['yil', 'yıl', 'year']);

                    if (empty($customerName) || empty($bankName)) {
                        $results['skipped']++;
                        $missingFields = [];
                        if (empty($customerName)) {
                            $missingFields[] = 'Firma Adı';
                        }
                        if (empty($bankName)) {
                            $missingFields[] = 'Banka Adı';
                        }
                        $availableHeaders = array_keys($rowData);
                        $results['errors'][] = "Satır {$rowNumber}: Eksik bilgi (" . implode(', ', $missingFields) . "). Mevcut başlıklar: " . implode(', ', $availableHeaders);
                        continue;
                    }

                    $customer = Customer::where('name', $customerName)->first();
                    if (! $customer) {
                        $customer = Customer::where('company', $customerName)->first();
                    }

                    if (! $customer) {
                        $results['skipped']++;
                        $results['errors'][] = "Satır {$rowNumber}: Firma bulunamadı: {$customerName}";
                        continue;
                    }

                    $yearInt = $this->parseYear($year);
                    if ($yearInt === null) {
                        $yearInt = (int) now()->year;
                    }
                    if ($yearInt < 2020 || $yearInt > now()->year + 1) {
                        $results['skipped']++;
                        $results['errors'][] = "Satır {$rowNumber}: Yıl 2020 ile " . (now()->year + 1) . " arasında olmalı.";
                        continue;
                    }

                    $requestedAt = $this->parseDate($this->getValue($rowData, ['talep_tarihi', 'talep tarihi']));
                    $replyReceivedAt = $this->parseDate($this->getValue($rowData, ['banka_donus_tarihi', 'banka donus tarihi', 'banka_donus_tarihi']));

                    $data = [
                        'customer_id' => $customer->id,
                        'bank_name' => $bankName,
                        'branch_name' => $this->getValue($rowData, ['sube', 'sube_adi', 'şube']),
                        'year' => $yearInt,
                        'requested_at' => $requestedAt,
                        'reply_received_at' => $replyReceivedAt,
                        'notes' => $this->getValue($rowData, ['not', 'notes']),
                    ];

                    ManualReconciliationEntry::create($data);
                    $results['success']++;
                } catch (\Illuminate\Database\QueryException $e) {
                    $results['skipped']++;
                    $errorMsg = "Satır {$rowNumber}: Veritabanı hatası";
                    if (str_contains($e->getMessage(), 'foreign key')) {
                        $errorMsg .= ' - Firma ID geçersiz';
                    } elseif (str_contains($e->getMessage(), 'constraint')) {
                        $errorMsg .= ' - Veri kısıtlaması hatası';
                    } else {
                        $errorMsg .= ' - ' . $e->getMessage();
                    }
                    $results['errors'][] = $errorMsg;
                } catch (\Exception $e) {
                    $results['skipped']++;
                    $results['errors'][] = "Satır {$rowNumber}: " . $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = 'Dosya okuma hatası: ' . $e->getMessage();
        }

        return $results;
    }

    private function getValue(array $rowData, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($rowData[$key]) && $rowData[$key] !== null) {
                $value = trim((string) $rowData[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return $default;
    }

    private function parseYear($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) trim((string) $value) ?: null;
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 0) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);

                return Carbon::instance($date)->format('Y-m-d');
            } catch (\Exception $e) {
                // Fall through to string parse
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
