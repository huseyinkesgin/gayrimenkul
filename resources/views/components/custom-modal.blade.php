@props([
    'name' => '',
    'title' => '',
    'subtitle' => '',
    'size' => 'md',
    'saveAction' => null,
    'clearAction' => null,
    'closeAction' => 'closeModal',
    'showSaveButton' => true,
    'showClearButton' => true,
    'saveTitle' => 'Kaydet',
    'clearTitle' => 'Formu Temizle'
])

@php
    // Size mapping
    $sizeClasses = [
        'xs' => 'w-full max-w-sm',      // ~384px
        'sm' => 'w-full max-w-md',      // ~448px
        'md' => 'w-full max-w-2xl',     // ~672px
        'lg' => 'w-full max-w-4xl',     // ~896px
        '2xl' => 'w-full max-w-6xl',    // ~1152px
    ];

    $contentMaxHeight = 'max-h-96'; // Varsayılan
    $customStyle = '';

    // Eğer preset size ise class kullan, değilse manuel boyut
    if (array_key_exists($size, $sizeClasses)) {
        $modalSize = $sizeClasses[$size];
        // Preset boyutlar için içerik yüksekliği
        $contentHeights = [
            'xs' => 'max-h-64',   // 256px
            'sm' => 'max-h-80',   // 320px
            'md' => 'max-h-96',   // 384px
            'lg' => 'max-h-[500px]', // 500px
            '2xl' => 'max-h-[600px]', // 600px
        ];
        $contentMaxHeight = $contentHeights[$size];
    } else {
        // Manuel boyut (örn: "300x400", "500px", "50%")
        if (str_contains($size, 'x')) {
            // 300x400 formatı
            [$width, $height] = explode('x', $size);
            $modalSize = "w-full max-w-none";
            $customStyle = "width: {$width}px; height: {$height}px;";
            // İçerik için flex kullanacağız, scroll kaldıracağız
            $contentMaxHeight = "flex-1";
        } else {
            // Tek değer (sadece genişlik)
            $modalSize = "w-full max-w-none";
            $customStyle = "width: {$size};";
        }
    }
@endphp

<div x-data="{ show: false }"
     x-show="show"
     x-on:open-modal.window="if ($event.detail.name === '{{ $name }}') show = true"
     x-on:close-modal.window="if ($event.detail.name === '{{ $name }}') show = false"
     x-on:keydown.escape.window="show = false"
     class="fixed inset-0 z-50"
     style="display: none;">

    <!-- Şeffaf Arkaplan -->
    <div class="fixed inset-0 bg-black/40"></div>

    <!-- Modal -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white {{ $modalSize }} border border-gray-300 shadow-xl flex flex-col"
             @if(isset($customStyle)) style="{{ $customStyle }}" @endif
             @click.stop>

            <!-- Header -->
            <div class="flex items-center justify-between p-3 border-b bg-amber-500">

                <div class="text-white">
                    <h3 class="font-medium">{{ $title }}</h3>
                    @if($subtitle)
                        <p class="text-xs">{{ $subtitle }}</p>
                    @endif
                </div>

                <!-- Butonlar -->
                <div class="flex gap-1">
                    @if($showSaveButton && $saveAction)
                        <button wire:click="{{ $saveAction }}"
                                class="p-2 text-green-600 hover:bg-green-100 rounded"
                                title="{{ $saveTitle }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                    @endif

                    @if($showClearButton && $clearAction)
                        <button wire:click="{{ $clearAction }}"
                                class="p-2 text-blue-600 hover:bg-blue-100 rounded"
                                title="{{ $clearTitle }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                    @endif

                    <button wire:click="{{ $closeAction }}"
                            class="p-2 text-red-600 hover:bg-red-100 rounded"
                            title="Kapat">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- İçerik -->
            <div class="p-4 {{ $contentMaxHeight }} overflow-y-auto">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
