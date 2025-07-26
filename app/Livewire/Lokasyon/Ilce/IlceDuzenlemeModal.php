<?php

namespace App\Livewire\Lokasyon\Ilce;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Sehir;

class IlceDuzenlemeModal extends Component
{
    public ?string $ilceId = null;
    public Ilce $ilce;
    public bool $loading = false;


    public $sehir_id;
    public $ad;
    public $aktif_mi;


    protected function rules()
    {
        return [
            'sehir_id' => 'required|exists:sehir,id',
            'ad' => 'required|string|min:3|max:35',
            'aktif_mi' => 'boolean',
        ];
    }


    protected function messages()
    {
        return [
            'ad.required' => 'Şehir adı gereklidir.',
            'ad.string' => 'Şehir adı metin olmalıdır.',
            'ad.min' => 'Şehir adı en az 3 karakter olmalıdır.',
            'ad.max' => 'Şehir adı en fazla 35 karakter olmalıdır.',
            'sehir_id.required' => 'Şehir seçilmelidir.',
            'sehir_id.exists' => 'Seçilen şehir geçerli değil.',
            'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
        ];
    }

    #[On('loadSehirForEdit')]
    public function handleLoadSehirForEdit($ilceId)
    {
        $this->loading = true;
        $this->ilceId = $ilceId;
        $this->loadIlce();
        $this->loading = false;
        $this->dispatch('open-modal', name: 'ilce-duzenleme-modal');
    }

    public function loadIlce()
    {
        if ($this->ilceId) {
            $this->ilce = Ilce::find($this->ilceId);

            if ($this->ilce) {
                $this->sehir_id = $this->ilce->sehir_id;
                $this->ad = $this->ilce->ad;
                $this->aktif_mi = $this->ilce->aktif_mi;
            }
        }
    }
    public function updateIlce()
    {
        $this->validate();

        if (!$this->ilce) {
            return;
        }

        $this->ilce->update([
            'ad' => $this->ad,
            'sehir_id' => $this->sehir_id,
            'aktif_mi' => $this->aktif_mi ? 1 : 0,
        ]);

        $this->dispatch('ilceGuncellendi');
        $this->dispatch('close-modal', name: 'ilce-duzenleme-modal');
        $this->reset(['ad', 'sehir_id', 'aktif_mi']);
    }
    public function render()
    {
        $sehirler = Sehir::aktif()->get();
        return view('livewire.lokasyon.ilce.ilce-duzenleme-modal', compact('sehirler'));
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'ilce-duzenleme-modal');
    }

    public function formuTemizle()
    {
        $this->reset(['ad', 'sehir_id', 'aktif_mi']);
    }

}
