<div class="space-y-4">
    <div>
        <h3 class="text-lg font-semibold mb-2">Mail Detayı</h3>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <strong>Gönderen:</strong> {{ $email->from_name ?? '-' }} ({{ $email->from_email }})
        </div>
        <div>
            <strong>Geliş Tarihi:</strong> {{ $email->received_at->format('d.m.Y H:i') }}
        </div>
        <div class="col-span-2">
            <strong>Konu:</strong> {{ $email->subject }}
        </div>
    </div>
    
    @if($email->bank)
    <div>
        <strong>Banka:</strong> {{ $email->bank->bank_name }}
    </div>
    @endif
    
    @if(!empty($email->attachments))
    <div>
        <strong>Ekler:</strong>
        <ul class="list-disc list-inside">
            @foreach($email->attachments as $attachment)
            <li>{{ $attachment['name'] }} ({{ number_format($attachment['size'] / 1024, 2) }} KB)</li>
            @endforeach
        </ul>
    </div>
    @endif
    
    <div>
        <strong>Mail İçeriği:</strong>
        <div class="mt-2 p-4 bg-gray-50 rounded border max-h-96 overflow-y-auto">
            @if($email->html_body)
                {!! $email->html_body !!}
            @else
                <pre class="whitespace-pre-wrap">{{ $email->body }}</pre>
            @endif
        </div>
    </div>
</div>

