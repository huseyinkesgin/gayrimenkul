<?php

namespace App\Livewire\Lokasyon\Sehir;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Lokasyon\Sehir;

class SehirDuzenlemeModal extends Component
{
    public ?string $sehirId = null;
    public ?Sehir $sehir = null;
    public bool $loading = false;

    public $ad;
    public $plaka_kodu;
    public $telefon_kodu;
    public $aktif_mi = null;


    protected function rules()
    {
        return [
            'ad' => 'required|string|min:3|max:35',
            'plaka_kodu' => 'nullable|digits:2|numeric|unique:sehir,plaka_kodu,' . $this->sehir->id,
            'telefon_kodu' => 'nullable|digits:3|numeric|unique:sehir,telefon_kodu,' . $this->sehir->id,
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
            'plaka_kodu.digits' => 'Plaka kodu tam olarak 2 rakam olmalıdır.',
            'plaka_kodu.unique' => 'Plaka kodu daha önce eklenmiştir.',
            'plaka_kodu.numeric' => 'Plaka kodu sadece rakamlardan oluşmalıdır.',
            'telefon_kodu.digits' => 'Telefon kodu tam olarak 3 rakam olmalıdır.',
            'telefon_kodu.numeric' => 'Telefon kodu sadece rakamlardan oluşmalıdır.',
            'telefon_kodu.unique' => 'Telefon kodu daha önce eklenmiştir.',
            'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
        ];
    }


    #[On('loadSehirForEdit')]
    public function handleLoadSehirForEdit($sehirId)
    {
        $this->loading = true;
        $this->sehirId = $sehirId;
        $this->loadSehir();
        $this->loading = false;
        $this->dispatch('open-modal', name: 'sehir-duzenleme-modal');
    }

    public function loadSehir()
    {
        if ($this->sehirId) {
            $this->sehir = Sehir::find($this->sehirId);

            if ($this->sehir) {
                $this->ad = $this->sehir->ad;
                $this->plaka_kodu = $this->sehir->plaka_kodu;
                $this->telefon_kodu = $this->sehir->telefon_kodu;
                $this->aktif_mi = $this->sehir->aktif_mi;
            }
        }
    }
    public function updateSehir()
    {
        $this->validate();

        if (!$this->sehir) {
            return;
        }

        $this->sehir->update([
            'ad' => $this->ad,
            'plaka_kodu' => $this->plaka_kodu,
            'telefon_kodu' => $this->telefon_kodu,
            'aktif_mi' => $this->aktif_mi ? 1 : 0,
        ]);

        $this->dispatch('sehirGuncellendi');
        $this->dispatch('close-modal', name: 'sehir-duzenleme-modal');
        $this->resetModal();
    }

    public function clearForm()
    {
        if ($this->sehir) {
            $this->loadSehir(); // Orijinal değerleri yükle
        }
    }

    public function resetModal()
    {
        $this->sehirId = null;
        $this->sehir = null;
        $this->loading = false;

        $this->ad = '';
        $this->plaka_kodu = '';
        $this->telefon_kodu = '';
        $this->not = '';
        $this->aktif_mi = null;
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'sehir-duzenleme-modal');
        $this->resetModal();
    }

    public function render()
    {
        return view('livewire.lokasyon.sehir.sehir-duzenleme-modal');
    }

}
