<?php

namespace App\Livewire\Lokasyon\Semt;

use App\Livewire\BaseTablo;
use Livewire\Attributes\On;
use App\Models\Lokasyon\Semt;

class SemtTablosu extends BaseTablo
{
    protected function getModelClass(): string
    {
        return Semt::class;
    }

    protected function getDefaultSortField(): string
    {
        return 'ad';
    }

    protected function getViewName(): string
    {
        return 'livewire.lokasyon.semt.semt-tablosu';
    }

    protected function getDataVariableName(): string
    {
        return 'semtler';
    }

    protected function getEagerLoadRelations(): array
    {
        return ['ilce.sehir', 'sehir'];
    }



    #[On('semtSilindi')]
    #[On('semtEklendi')]
    #[On('semtGuncellendi')]
    public function refreshComponent()
    {
        // Bu metod sadece event'leri dinlemek için
        // Render otomatik olarak çağrılacak
    }
}
