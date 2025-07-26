<?php

namespace App\Livewire\Lokasyon\Mahalle;

use Flux\Flux;
use Livewire\Component;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Semt;
use App\Models\Lokasyon\Sehir;
use App\Models\Lokasyon\Mahalle;

class MahalleEklemeModal extends Component
{
    public string $sehir_id = '';
    public string $ilce_id = '';
    public string $semt_id = '';
    public string $ad = '';
    public string $posta_kodu = '';
    public string $not = '';
    public bool $aktif_mi = true;

    public $sehirler;
    public $ilceler = [];
    public $semtler = [];

    public function mount()
    {
        $this->sehirler = Sehir::aktif()->get();
    }

    protected $rules = [
        'ad' => 'required|string|min:3|max:35',
        'semt_id' => 'required|exists:semt,id',
        'posta_kodu' => 'nullable|string|max:10',
        'not' => 'nullable|string|max:500',
        'aktif_mi' => 'boolean',
    ];

    protected $messages = [
        'ad.required' => 'Mahalle adı gereklidir.',
        'ad.string' => 'Mahalle adı metin olmalıdır.',
        'ad.min' => 'Mahalle adı en az 3 karakter olmalıdır.',
        'ad.max' => 'Mahalle adı en fazla 35 karakter olmalıdır.',
        'semt_id.required' => 'Semt seçilmelidir.',
        'semt_id.exists' => 'Seçilen semt geçerli değil.',
        'posta_kodu.string' => 'Posta kodu metin olmalıdır.',
        'posta_kodu.max' => 'Posta kodu en fazla 10 karakter olmalıdır.',
        'not.string' => 'Not metin olmalıdır.',
        'not.max' => 'Not en fazla 500 karakter olmalıdır.',
        'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
    ];

    public function updatedSehirId($value)
    {
        $this->ilceler = $value ? Ilce::where('sehir_id', $value)->get() : [];
        $this->ilce_id = '';
        $this->semt_id = '';
        $this->semtler = [];
    }

    public function updatedIlceId($value)
    {
        $this->semtler = $value ? Semt::where('ilce_id', $value)->get() : [];
        $this->semt_id = '';
    }

    public function addMahalle()
    {
        $this->validate();

        Mahalle::create([
            'ad' => $this->ad,
            'semt_id' => $this->semt_id,
            'posta_kodu' => $this->posta_kodu,
            'not' => $this->not,
            'aktif_mi' => $this->aktif_mi ? 1 : 0,
        ]);

        $this->dispatch('mahalleEklendi');
        Flux::modals()->close();
        $this->reset(['ad', 'semt_id', 'posta_kodu', 'not', 'aktif_mi']);
    }

    public function render()
    {
        return view('livewire.lokasyon.mahalle.mahalle-ekleme-modal');
    }
}
