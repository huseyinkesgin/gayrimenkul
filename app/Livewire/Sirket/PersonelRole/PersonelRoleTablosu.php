<?php

namespace App\Livewire\Sirket\PersonelRole;

use Livewire\Component;
use App\Models\Kisi\PersonelRol;
use App\Livewire\BaseTablo;
use Livewire\Attributes\On;

class PersonelRoleTablosu extends BaseTablo
{
    protected function getModelClass(): string
    {
        return PersonelRol::class;
    }

    protected function getDefaultSortField(): string
    {
        return 'siralama';
    }

    protected function getViewName(): string
    {
        return 'livewire.sirket.personel-role.personel-role-tablosu';
    }

    protected function getDataVariableName(): string
    {
        return 'personelRoller';
    }

    #[On('personelRoleSilindi')]
    #[On('personelRoleEklendi')]
    #[On('personelRoleGuncellendi')]
    public function refreshComponent()
    {
        // Bu metod sadece event'leri dinlemek için
        // Render otomatik olarak çağrılacak
    }

    public function editPersonelRole($personelRoleId)
    {
        $this->dispatch('loadPersonelRole', personelRoleId: $personelRoleId);
    }
}
