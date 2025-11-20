<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerBank;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomerBankImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // WithHeadingRow başlıkları küçük harfe çevirir ve boşlukları alt çizgiye çevirir
        // "Firma Adı" -> "firma_adi", "E-posta" -> "e_posta" vb.
        $customerName = $row['firma_adi'] ?? $row['firma adi'] ?? null;
        if (!$customerName) {
            return null;
        }

        $customer = Customer::where('name', $customerName)
            ->orWhere('company', $customerName)
            ->first();

        if (!$customer) {
            return null;
        }

        // E-posta ile mevcut kaydı kontrol et
        $email = $row['e_posta'] ?? $row['e-posta'] ?? null;
        $existing = null;
        if ($email) {
            $existing = CustomerBank::where('customer_id', $customer->id)
                ->where('officer_email', $email)
                ->first();
        }

        if ($existing) {
            // Mevcut kaydı güncelle
            $existing->update([
                'bank_name' => $row['banka_adi'] ?? $row['banka adi'] ?? '',
                'branch_name' => $row['sube_adi'] ?? $row['sube adi'] ?? null,
                'officer_name' => $row['yetkili_masa_memuru'] ?? $row['yetkili / masa memuru'] ?? null,
                'officer_email' => $email,
                'officer_phone' => $row['telefon'] ?? null,
                'is_active' => $this->parseBoolean($row['aktif'] ?? '1'),
            ]);
            return null; // Güncelleme yapıldı, yeni kayıt oluşturma
        }

        // Yeni kayıt oluştur
        return new CustomerBank([
            'customer_id' => $customer->id,
            'bank_name' => $row['banka_adi'] ?? $row['banka adi'] ?? '',
            'branch_name' => $row['sube_adi'] ?? $row['sube adi'] ?? null,
            'officer_name' => $row['yetkili_masa_memuru'] ?? $row['yetkili / masa memuru'] ?? null,
            'officer_email' => $email,
            'officer_phone' => $row['telefon'] ?? null,
            'is_active' => $this->parseBoolean($row['aktif'] ?? '1'),
        ]);
    }

    public function rules(): array
    {
        return [
            'firma_adi' => 'required',
            'banka_adi' => 'required',
            'e_posta' => 'required|email',
        ];
    }

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'evet', 'yes', 'aktif']);
    }
}

