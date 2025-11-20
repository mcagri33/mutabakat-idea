<?php

namespace App\Filament\Resources\CustomerBankResource\Imports;

use App\Models\Customer;
use App\Models\CustomerBank;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CustomerBankImport extends Importer
{
    protected static ?string $model = CustomerBank::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('customer')
                ->label('Firma Adı')
                ->rules(['required', 'string'])
                ->example('Kanada Vizeniz'),
            
            ImportColumn::make('bank_name')
                ->label('Banka Adı')
                ->rules(['required', 'string', 'max:255'])
                ->example('Ziraat Bankası'),
            
            ImportColumn::make('branch_name')
                ->label('Şube Adı')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Kadıköy Şubesi'),
            
            ImportColumn::make('officer_name')
                ->label('Yetkili / Masa Memuru')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Ahmet Yılmaz'),
            
            ImportColumn::make('officer_email')
                ->label('E-posta')
                ->rules(['required', 'email:rfc,dns', 'max:255'])
                ->example('ahmet@ziraat.com.tr'),
            
            ImportColumn::make('officer_phone')
                ->label('Telefon')
                ->rules(['nullable', 'string', 'max:20'])
                ->example('+90 555 123 45 67'),
            
            ImportColumn::make('is_active')
                ->label('Aktif')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('1 veya Evet'),
        ];
    }

    public function resolveRecord(): ?CustomerBank
    {
        // Firma adına göre customer_id'yi bul
        $customerName = $this->data['customer'] ?? null;
        if (!$customerName) {
            return null;
        }

        $customer = Customer::where('name', $customerName)
            ->orWhere('company', $customerName)
            ->first();

        if (!$customer) {
            $this->record = null;
            return null;
        }

        // E-posta ile mevcut kaydı kontrol et
        $email = $this->data['officer_email'] ?? null;
        if ($email) {
            $existing = CustomerBank::where('customer_id', $customer->id)
                ->where('officer_email', $email)
                ->first();
            
            if ($existing) {
                $this->record = $existing;
                return $existing;
            }
        }

        return new CustomerBank();
    }

    protected function beforeFill(): void
    {
        // Firma adına göre customer_id'yi bul
        $customerName = $this->data['customer'] ?? null;
        if ($customerName) {
            $customer = Customer::where('name', $customerName)
                ->orWhere('company', $customerName)
                ->first();
            
            if ($customer) {
                $this->data['customer_id'] = $customer->id;
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Banka kayıtları başarıyla içe aktarıldı. ';

        if ($import->successful_rows > 0) {
            $body .= number_format($import->successful_rows) . ' kayıt başarılı. ';
        }

        if ($import->failed_rows > 0) {
            $body .= number_format($import->failed_rows) . ' kayıt başarısız.';
        }

        return $body;
    }
}

