<x-filament-widgets::widget>
    <x-filament::section heading="Mutabakat İlerleme">
        <div class="text-lg mb-2">Tamamlanma: {{ $percent }}%</div>
        <div class="w-full bg-gray-200 rounded h-4">
            <div class="bg-green-600 h-4 rounded" style="width: {{ $percent }}%"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
