<?php

namespace App\Livewire\Lokasyon\Mahalle;

use App\Livewire\BaseTablo;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Models\Lokasyon\Mahalle;
use Illuminate\Database\Eloquent\Builder;

class MahalleTablosu extends BaseTablo
{
    protected function getModelClass(): string
    {
        return Mahalle::class;
    }

    protected function getDefaultSortField(): string
    {
        return 'ad';
    }

    protected function getViewName(): string
    {
        return 'livewire.lokasyon.mahalle.mahalle-tablosu';
    }

    protected function getDataVariableName(): string
    {
        return 'mahalleler';
    }

    #[On('mahalleSilindi')]
    #[On('mahalleEklendi')]
    #[On('mahalleGuncellendi')]
    public function refreshComponent()
    {
        // Bu metod sadece event'leri dinlemek için
        // Render otomatik olarak çağrılacak
    }
}
