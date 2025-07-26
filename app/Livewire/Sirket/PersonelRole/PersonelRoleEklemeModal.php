<?php

namespace App\Livewire\Sirket\PersonelRole;

use Flux\Flux;
use Livewire\Component;
use App\Models\Kisi\PersonelRol;

class PersonelRoleEklemeModal extends Component
{
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

    public function addPersonelRole()
    {
        $this->validate();

        PersonelRol::create([
            'ad' => $this->ad,
            'not' => $this->not,
            'siralama' => $this->siralama,
            'aktif_mi' => $this->aktif_mi,
        ]);

        $this->dispatch('personelRoleEklendi');
        Flux::modals()->close();
    }

    public function render()
    {
        return view('livewire.sirket.personel-role.personel-role-ekleme-modal');
    }
}
