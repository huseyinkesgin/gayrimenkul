@props(['ad'])

<div class="grid grid-cols-3 gap-4 m-2">
    <flux:label class="font-medium text-xs text-amber-800">{{ $ad }} *</flux:label>
    <div class="col-span-2 text-xs">
        {{ $slot }}
    </div>
</div>
