<?php

namespace App\Livewire\Sirket\Pozisyon;

use Livewire\Component;
use App\Models\Kisi\Pozisyon;
use App\Livewire\BaseTablo;
use Livewire\Attributes\On;

class PozisyonTablosu extends BaseTablo
{
    protected function getModelClass(): string
    {
        return Pozisyon::class;
    }

    protected function getDefaultSortField(): string
    {
        return 'siralama';
    }

    protected function getViewName(): string
    {
        return 'livewire.sirket.pozisyon.pozisyon-tablosu';
    }

    protected function getDataVariableName(): string
    {
        return 'pozisyonlar';
    }

    #[On('pozisyonSilindi')]
    #[On('pozisyonEklendi')]
    #[On('pozisyonGuncellendi')]
    public function refreshComponent()
    {
        // Bu metod sadece event'leri dinlemek için
        // Render otomatik olarak çağrılacak
    }

    public function editPozisyon($pozisyonId)
    {
        $this->dispatch('loadPozisyon', pozisyonId: $pozisyonId);
    }
}
