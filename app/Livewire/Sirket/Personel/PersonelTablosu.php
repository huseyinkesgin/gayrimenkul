<?php

namespace App\Livewire\Sirket\Personel;

use Livewire\Component;
use App\Models\Kisi\Personel;
use App\Livewire\BaseTablo;
use Livewire\Attributes\On;

class PersonelTablosu extends BaseTablo
{
    protected function getModelClass(): string
    {
        return Personel::class;
    }

    protected function getDefaultSortField(): string
    {
        return 'personel_no';
    }

    protected function getViewName(): string
    {
        return 'livewire.sirket.personel.personel-tablosu';
    }

    protected function getDataVariableName(): string
    {
        return 'personeller';
    }

    protected function getQuery()
    {
        return parent::getQuery()->with([
            'kisi', 
            'sube', 
            'departman', 
            'pozisyon', 
            'roller',
            'avatar'
        ]);
    }

    #[On('personelSilindi')]
    #[On('personelEklendi')]
    #[On('personelGuncellendi')]
    #[On('personelAdresEklendi')]
    #[On('personelAdresGuncellendi')]
    #[On('personelAdresSilindi')]
    public function refreshComponent()
    {
        // Bu metod sadece event'leri dinlemek için
        // Render otomatik olarak çağrılacak
    }

    public function editPersonel($personelId)
    {
        $this->dispatch('loadPersonelForEdit', personelId: $personelId);
    }

    public function addAdresForPersonel($personelId)
    {
        $this->dispatch('loadPersonelForAdres', personelId: $personelId);
    }

    public function showPersonelDetay($personelId)
    {
        $this->dispatch('loadPersonelDetay', personelId: $personelId);
    }
}
