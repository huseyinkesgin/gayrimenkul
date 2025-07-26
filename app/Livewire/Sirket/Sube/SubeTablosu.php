<?php

namespace App\Livewire\Sirket\Sube;

use Flux\Flux;
use Livewire\Component;
use App\Models\Kisi\Sube;
use App\Livewire\BaseTablo;
use Livewire\Attributes\On;

class SubeTablosu extends BaseTablo
{
    protected function getModelClass(): string
    {
        return Sube::class;
    }

    protected function getQuery()
    {
        return parent::getQuery()->with(['adresler.sehir', 'adresler.ilce']);
    }

    protected function getDefaultSortField(): string
    {
        return 'ad';
    }

    protected function getViewName(): string
    {
        return 'livewire.sirket.sube.sube-tablosu';
    }

    protected function getDataVariableName(): string
    {
        return 'subeler';
    }

    #[On('subeSilindi')]
    #[On('subeEklendi')]
    #[On('subeGuncellendi')]
    public function refreshComponent()
    {
        // Bu metod sadece event'leri dinlemek için
        // Render otomatik olarak çağrılacak
    }

    public function editSube($subeId)
    {
        $this->dispatch('loadSube', subeId: $subeId);
    }


}
