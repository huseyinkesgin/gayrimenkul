<?php

namespace App\Livewire\Sirket\PersonelRole;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Kisi\PersonelRol;

class PersonelRoleDuzenlemeModal extends Component
{
    public ?string $personelRoleId = null;
    public ?PersonelRol $personelRole = null;
    public bool $loading = false;

    public string $ad = '';
    public string $not = '';
    public int $siralama = 0;
    public bool $aktif_mi = true;

    protected $rules = [
        'ad' => 'required|string|min:2|max:100',
        'not' => 'nullable|string|max:500',
        'siralama' => 'integer|min:0',
        'aktif_mi' => 'boolean',
    ];

    protected $messages = [
        'ad.required' => 'Rol adı gereklidir.',
        'ad.string' => 'Rol adı metin olmalıdır.',
        'ad.min' => 'Rol adı en az 2 karakter olmalıdır.',
        'ad.max' => 'Rol adı en fazla 100 karakter olmalıdır.',
        'not.string' => 'Not metin olmalıdır.',
        'not.max' => 'Not en fazla 500 karakter olmalıdır.',
        'siralama.integer' => 'Sıralama sayı olmalıdır.',
        'siralama.min' => 'Sıralama 0 veya daha büyük olmalıdır.',
        'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
    ];

    public function mount($personelRoleId = null)
    {
        $this->personelRoleId = $personelRoleId;
        
        if ($this->personelRoleId) {
            $this->loadPersonelRole();
        }
    }

    public function loadPersonelRole()
    {
        $this->personelRole = PersonelRol::find($this->personelRoleId);
        
        if (!$this->personelRole) {
            return;
        }

        $this->ad = $this->personelRole->ad;
        $this->not = $this->personelRole->not ?? '';
        $this->siralama = $this->personelRole->siralama;
        $this->aktif_mi = $this->personelRole->aktif_mi;
    }

    #[On('loadPersonelRole')]
    public function handleLoadPersonelRole($personelRoleId)
    {
        $this->loading = true;
        $this->personelRoleId = $personelRoleId;
        $this->loadPersonelRole();
        $this->loading = false;
    }

    public function updatePersonelRole()
    {
        $this->validate();

        if (!$this->personelRole) {
            return;
        }

        $this->personelRole->update([
            'ad' => $this->ad,
            'not' => $this->not,
            'siralama' => $this->siralama,
            'aktif_mi' => $this->aktif_mi,
        ]);

        $this->dispatch('personelRoleGuncellendi');
        $this->resetModal();
        Flux::modals()->close();
    }

    public function resetModal()
    {
        $this->personelRoleId = null;
        $this->personelRole = null;
        $this->loading = false;
        
        $this->ad = '';
        $this->not = '';
        $this->siralama = 0;
        $this->aktif_mi = true;
    }

    public function render()
    {
        return view('livewire.sirket.personel-role.personel-role-duzenleme-modal');
    }
}
