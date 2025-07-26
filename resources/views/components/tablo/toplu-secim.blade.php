@props(['selected'])

{{-- This component displays a selection summary and an action button for deleting selected records --}}
{{-- It is used in the context of a table where multiple records can be selected for batch actions --}}

<div class="flex items-center gap-2 h-9">
     <span class="text-xs text-gray-600">{{ count($selected) }} kayıt seçildi</span>
     @if (count($selected) > 0)
         <flux:button variant="danger" size="sm" wire:click="deleteSelected">
             Seçili kayıtları sil
         </flux:button>
     @endif
 </div>
