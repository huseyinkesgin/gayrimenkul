<?php

namespace App\Livewire\Lokasyon\Ilce;

use App\Livewire\BaseTablo;
use Livewire\Attributes\On;
use App\Models\Lokasyon\Ilce;

class IlceTablosu extends BaseTablo
{
    protected function getModelClass(): string
    {
        return Ilce::class;
    }

    protected function getDefaultSortField(): string
    {
        return 'ad';
    }

    protected function getViewName(): string
    {
        return 'livewire.lokasyon.ilce.ilce-tablosu';
    }

    protected function getDataVariableName(): string
    {
        return 'ilceler';
    }

    #[On('ilceSilindi')]
    #[On('ilceEklendi')]
    #[On('ilceGuncellendi')]
    public function refreshComponent()
    {
        // Bu metod sadece event'leri dinlemek için
        // Render otomatik olarak çağrılacak
    }

    public function editIlce($ilceId)
    {
        $this->dispatch('loadIlceForEdit', ilceId: $ilceId);
    }
}
