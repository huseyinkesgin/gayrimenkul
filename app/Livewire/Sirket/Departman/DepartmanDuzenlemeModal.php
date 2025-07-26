<?php

namespace App\Livewire\Sirket\Departman;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Kisi\Departman;
use App\Models\Kisi\Personel;

class DepartmanDuzenlemeModal extends Component
{
    public ?string $departmanId = null;
    public ?Departman $departman = null;
    public bool $loading = false;

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

    public function mount($departmanId = null)
    {
        $this->departmanId = $departmanId;
        $this->personeller = Personel::with('kisi')
            ->whereHas('kisi', function($q) {
                $q->where('aktif_mi', true);
            })
            ->get();
        
        if ($this->departmanId) {
            $this->loadDepartman();
        }
    }

    public function loadDepartman()
    {
        $this->departman = Departman::find($this->departmanId);
        
        if (!$this->departman) {
            return;
        }

        $this->ad = $this->departman->ad;
        $this->aciklama = $this->departman->aciklama ?? '';
        $this->yonetici_id = $this->departman->yonetici_id;
        $this->aktif_mi = $this->departman->aktif_mi;
    }

    #[On('loadDepartman')]
    public function handleLoadDepartman($departmanId)
    {
        $this->loading = true;
        $this->departmanId = $departmanId;
        $this->loadDepartman();
        $this->loading = false;
    }

    public function updateDepartman()
    {
        $this->validate();

        if (!$this->departman) {
            return;
        }

        $this->departman->update([
            'ad' => $this->ad,
            'aciklama' => $this->aciklama,
            'yonetici_id' => $this->yonetici_id ?: null,
            'aktif_mi' => $this->aktif_mi,
        ]);

        $this->dispatch('departmanGuncellendi');
        $this->resetModal();
        Flux::modals()->close();
    }

    public function resetModal()
    {
        $this->departmanId = null;
        $this->departman = null;
        $this->loading = false;
        
        $this->ad = '';
        $this->aciklama = '';
        $this->yonetici_id = null;
        $this->aktif_mi = true;
    }

    public function render()
    {
        return view('livewire.sirket.departman.departman-duzenleme-modal');
    }
}
