<?php

namespace App\Livewire\Lokasyon\Sehir;

use App\Livewire\BaseTablo;
use Livewire\Attributes\On;
use App\Models\Lokasyon\Sehir;

class SehirTablosu extends BaseTablo
{
    protected function getModelClass(): string
    {
        return Sehir::class;
    }

    protected function getDefaultSortField(): string
    {
        return 'plaka_kodu';
    }

    protected function getViewName(): string
    {
        return 'livewire.lokasyon.sehir.sehir-tablosu';
    }

    protected function getDataVariableName(): string
    {
        return 'sehirler';
    }

    #[On('sehirSilindi')]
    #[On('sehirEklendi')]
    #[On('sehirGuncellendi')]
    public function refreshComponent()
    {
        // Bu metod sadece event'leri dinlemek için
        // Render otomatik olarak çağrılacak
    }

    public function editSehir($sehirId)
    {
        $this->dispatch('loadSehirForEdit', sehirId: $sehirId);
    }
}
