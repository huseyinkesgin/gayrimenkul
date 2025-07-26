<?php

namespace App\Livewire\Sirket\Departman;

use Flux\Flux;
use Livewire\Component;
use App\Models\Kisi\Departman;
use App\Models\Kisi\Personel;

class DepartmanEklemeModal extends Component
{
    public string $ad = '';
    public string $aciklama = '';
    public ?string $yonetici_id = null;
    public bool $aktif_mi = true;

    // Dropdown verileri
    public $personeller = [];

    protected $rules = [
        'ad' => 'required|string|min:2|max:100',
        'aciklama' => 'nullable|string|max:500',
        'yonetici_id' => 'nullable|uuid|exists:personel,id',
        'aktif_mi' => 'boolean',
    ];

    protected $messages = [
        'ad.required' => 'Departman adı gereklidir.',
        'ad.string' => 'Departman adı metin olmalıdır.',
        'ad.min' => 'Departman adı en az 2 karakter olmalıdır.',
        'ad.max' => 'Departman adı en fazla 100 karakter olmalıdır.',
        'aciklama.string' => 'Açıklama metin olmalıdır.',
        'aciklama.max' => 'Açıklama en fazla 500 karakter olmalıdır.',
        'yonetici_id.uuid' => 'Geçerli bir yönetici seçiniz.',
        'yonetici_id.exists' => 'Seçilen yönetici geçerli değil.',
        'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
    ];

    public function mount()
    {
        $this->personeller = Personel::with('kisi')
            ->whereHas('kisi', function($q) {
                $q->where('aktif_mi', true);
            })
            ->get();
    }

    public function addDepartman()
    {
        $this->validate();

        Departman::create([
            'ad' => $this->ad,
            'aciklama' => $this->aciklama,
            'yonetici_id' => $this->yonetici_id ?: null,
            'aktif_mi' => $this->aktif_mi,
        ]);

        $this->dispatch('departmanEklendi');
        Flux::modals()->close();
    }

    public function render()
    {
        return view('livewire.sirket.departman.departman-ekleme-modal');
    }
}
