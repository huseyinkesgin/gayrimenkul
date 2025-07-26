<?php

namespace App\Livewire\Sirket\Pozisyon;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Kisi\Pozisyon;

class PozisyonDuzenlemeModal extends Component
{
    public ?string $pozisyonId = null;
    public ?Pozisyon $pozisyon = null;
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
        'ad.required' => 'Pozisyon adı gereklidir.',
        'ad.string' => 'Pozisyon adı metin olmalıdır.',
        'ad.min' => 'Pozisyon adı en az 2 karakter olmalıdır.',
        'ad.max' => 'Pozisyon adı en fazla 100 karakter olmalıdır.',
        'not.string' => 'Not metin olmalıdır.',
        'not.max' => 'Not en fazla 500 karakter olmalıdır.',
        'siralama.integer' => 'Sıralama sayı olmalıdır.',
        'siralama.min' => 'Sıralama 0 veya daha büyük olmalıdır.',
        'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
    ];

    public function mount($pozisyonId = null)
    {
        $this->pozisyonId = $pozisyonId;
        
        if ($this->pozisyonId) {
            $this->loadPozisyon();
        }
    }

    public function loadPozisyon()
    {
        $this->pozisyon = Pozisyon::find($this->pozisyonId);
        
        if (!$this->pozisyon) {
            return;
        }

        $this->ad = $this->pozisyon->ad;
        $this->not = $this->pozisyon->not ?? '';
        $this->siralama = $this->pozisyon->siralama;
        $this->aktif_mi = $this->pozisyon->aktif_mi;
    }

    #[On('loadPozisyon')]
    public function handleLoadPozisyon($pozisyonId)
    {
        $this->loading = true;
        $this->pozisyonId = $pozisyonId;
        $this->loadPozisyon();
        $this->loading = false;
    }

    public function updatePozisyon()
    {
        $this->validate();

        if (!$this->pozisyon) {
            return;
        }

        $this->pozisyon->update([
            'ad' => $this->ad,
            'not' => $this->not,
            'siralama' => $this->siralama,
            'aktif_mi' => $this->aktif_mi,
        ]);

        $this->dispatch('pozisyonGuncellendi');
        $this->resetModal();
        Flux::modals()->close();
    }

    public function resetModal()
    {
        $this->pozisyonId = null;
        $this->pozisyon = null;
        $this->loading = false;
        
        $this->ad = '';
        $this->not = '';
        $this->siralama = 0;
        $this->aktif_mi = true;
    }

    public function render()
    {
        return view('livewire.sirket.pozisyon.pozisyon-duzenleme-modal');
    }
}
