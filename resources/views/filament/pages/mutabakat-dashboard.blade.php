<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->getHeaderWidgets())
            <x-filament-widgets::widgets
                :widgets="$this->getHeaderWidgets()"
                :columns="$this->getHeaderWidgetsColumns()"
            />
        @endif

        @if($this->getFooterWidgets())
            <div class="space-y-6">
                <x-filament-widgets::widgets
                    :widgets="$this->getFooterWidgets()"
                    :columns="$this->getFooterWidgetsColumns()"
                />
            </div>
        @endif
    </div>
</x-filament-panels::page>
