<x-filament::page>
    <div class="space-y-4">
        <div class="text-lg font-bold">
            Conversa com {{ $conversation->sender }}
        </div>
        <div class="space-y-3">
            @foreach ($messages as $msg)
                <div class="
                    p-3 rounded-lg border
                    @if($msg->direction === 'in')
                        bg-blue-50 border-blue-300
                    @else
                        bg-green-50 border-green-300
                    @endif
                ">
                    <div class="text-xs text-gray-600">
                        <strong>
                            {{ $msg->direction === 'in' ? 'Cliente' : 'Bot/Humano' }}
                        </strong>
                        · {{ $msg->sent_at }}
                    </div>
                    <div class="mt-1 text-sm">
                        {{ $msg->text }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament::page>
