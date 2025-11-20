<x-filament-widgets::widget>
    <div class="grid grid-cols-4 gap-4">

        <x-filament::section heading="Firma">
            <div class="text-xl font-semibold text-primary">
                {{ $record->customer->name }}
            </div>
        </x-filament::section>

        <x-filament::section heading="Yıl">
            <div class="text-xl font-bold">
                {{ $record->year }}
            </div>
        </x-filament::section>

        <x-filament::section heading="Tip">
            <div class="text-xl font-semibold">
                {{ $record->type === 'banka' ? 'Banka Mutabakatı' : 'Cari Mutabakat' }}
            </div>
        </x-filament::section>

        <x-filament::section heading="Durum">
            <x-filament::badge :color="$record->status">
                {{ strtoupper($record->status) }}
            </x-filament::badge>
        </x-filament::section>

    </div>
</x-filament-widgets::widget>
