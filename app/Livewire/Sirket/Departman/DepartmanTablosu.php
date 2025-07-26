<?php

namespace App\Livewire\Sirket\Departman;

use Livewire\Component;
use App\Models\Kisi\Departman;
use App\Livewire\BaseTablo;
use Livewire\Attributes\On;

class DepartmanTablosu extends BaseTablo
{
    protected function getModelClass(): string
    {
        return Departman::class;
    }

    protected function getDefaultSortField(): string
    {
        return 'ad';
    }

    protected function getViewName(): string
    {
        return 'livewire.sirket.departman.departman-tablosu';
    }

    protected function getDataVariableName(): string
    {
        return 'departmanlar';
    }

    protected function getQuery()
    {
        return parent::getQuery()->with(['yonetici.kisi']);
    }

    #[On('departmanSilindi')]
    #[On('departmanEklendi')]
    #[On('departmanGuncellendi')]
    public function refreshComponent()
    {
        // Bu metod sadece event'leri dinlemek için
        // Render otomatik olarak çağrılacak
    }

    public function editDepartman($departmanId)
    {
        $this->dispatch('loadDepartman', departmanId: $departmanId);
    }
}
