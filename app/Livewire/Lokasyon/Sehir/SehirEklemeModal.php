<?php

namespace App\Livewire\Lokasyon\Sehir;

use Flux\Flux;
use Livewire\Component;
use App\Models\Lokasyon\Sehir;

class SehirEklemeModal extends Component
{
    public string $ad;
    public string $plaka_kodu;
    public string $telefon_kodu;
    public bool $aktif_mi = true;

    protected $rules = [
        'ad' => 'required|string|min:3|max:35',
        'plaka_kodu' => ['required', 'digits:2','unique:sehir,plaka_kodu','numeric'],
        'telefon_kodu' => ['nullable', 'digits:3','numeric','unique:sehir,telefon_kodu'],
        'aktif_mi' => 'boolean',
    ];

    protected $messages = [
        'ad.required' => 'Şehir adı gereklidir.',
        'ad.string' => 'Şehir adı metin olmalıdır.',
        'ad.min' => 'Şehir adı en az 3 karakter olmalıdır.',
        'ad.max' => 'Şehir adı en fazla 35 karakter olmalıdır.',
        'plaka_kodu.required' => 'Plaka kodu gereklidir.',
        'plaka_kodu.digits' => 'Plaka kodu tam olarak 2 rakam olmalıdır.',
        'plaka_kodu.unique' => 'Plaka kodu daha önce eklenmiştir.',
        'plaka_kodu.numeric' => 'Plaka kodu sadece rakamlardan oluşmalıdır.',
        'telefon_kodu.digits' => 'Telefon kodu tam olarak 3 rakam olmalıdır.',
        'telefon_kodu.numeric' => 'Telefon kodu sadece rakamlardan oluşmalıdır.',
        'telefon_kodu.unique' => 'Telefon kodu daha önce eklenmiştir.',
        'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
    ];

    public function kaydet()
    {
        $this->validate();

        Sehir::create([
            'ad' => $this->ad,
            'plaka_kodu' => $this->plaka_kodu,
            'telefon_kodu' => $this->telefon_kodu,
            'aktif_mi' => $this->aktif_mi ? 1 : 0,
        ]);

        $this->dispatch('sehirEklendi');
        $this->dispatch('close-modal', name: 'sehir-ekleme-modal');
        $this->reset(['ad', 'plaka_kodu', 'telefon_kodu', 'aktif_mi']);
    }

    public function render()
    {
        return view('livewire.lokasyon.sehir.sehir-ekleme-modal');
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'sehir-ekleme-modal');
    }

    public function formuTemizle()
    {
        $this->reset(['ad', 'plaka_kodu', 'telefon_kodu', 'aktif_mi']);
    }
}
