<x-filament-widgets::widget>
    <x-filament::section heading="Mutabakat Zaman Çizelgesi">

        <ul class="border-l-2 border-gray-400 pl-4 space-y-4">

            <li>
                <strong>Talep oluşturuldu:</strong>
                {{ $record->created_at->format('d.m.Y H:i') }}
            </li>

            @foreach ($record->banks as $bank)
                @if ($bank->mail_sent_at)
                    <li>
                        <strong>{{ $bank->bank_name }} → Mail gönderildi:</strong>
                        {{ $bank->mail_sent_at->format('d.m.Y H:i') }}
                    </li>
                @endif

                @if ($bank->reply_received_at)
                    <li>
                        <strong>{{ $bank->bank_name }} → Cevap geldi:</strong>
                        {{ $bank->reply_received_at->format('d.m.Y H:i') }}
                    </li>
                @endif
            @endforeach

        </ul>

    </x-filament::section>
</x-filament-widgets::widget>
