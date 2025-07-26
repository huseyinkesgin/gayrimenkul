<?php

namespace App\Livewire\Lokasyon\Ilce;

use Flux\Flux;
use Livewire\Component;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Sehir;

class IlceEklemeModal extends Component
{
    public string $sehir_id = '';
    public string $ad = '';
    public bool $aktif_mi = true;

    protected $rules = [
        'ad' => 'required|string|min:3|max:35',
        'sehir_id' => 'required|exists:sehir,id',
        'aktif_mi' => 'boolean',
    ];

    protected $messages = [
        'ad.required' => 'Şehir adı gereklidir.',
        'ad.string' => 'Şehir adı metin olmalıdır.',
        'ad.min' => 'Şehir adı en az 3 karakter olmalıdır.',
        'ad.max' => 'Şehir adı en fazla 35 karakter olmalıdır.',
        'sehir_id.required' => 'Şehir seçilmelidir.',
        'sehir_id.exists' => 'Seçilen şehir geçerli değil.',
        'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
    ];

    public function addIlce()
    {
        $this->validate();

        Ilce::create([
            'ad' => $this->ad,
            'sehir_id' => $this->sehir_id,
            'aktif_mi' => $this->aktif_mi ? 1 : 0,
        ]);

        $this->dispatch('ilceEklendi');
        $this->dispatch('close-modal', name: 'ilce-ekleme-modal');
        $this->reset(['ad', 'sehir_id', 'aktif_mi']);
    }

    public function render()
    {
        $sehirler = Sehir::aktif()->get();
        return view('livewire.lokasyon.ilce.ilce-ekleme-modal', compact('sehirler'));
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'ilce-ekleme-modal');
    }

    public function formuTemizle()
    {
        $this->reset(['ad', 'sehir_id', 'aktif_mi']);
    }
}
