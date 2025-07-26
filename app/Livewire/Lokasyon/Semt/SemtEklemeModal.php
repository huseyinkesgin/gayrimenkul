<?php

namespace App\Livewire\Lokasyon\Semt;

use Flux\Flux;
use Livewire\Component;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Semt;
use App\Models\Lokasyon\Sehir;

class SemtEklemeModal extends Component
{
    public string $sehir_id = '';
    public string $ilce_id = '';
    public string $ad = '';
    public string $not = '';
    public bool $aktif_mi = true;



    public $sehirler;
    public $ilceler = [];


    public function mount()
    {
        $this->sehirler = Sehir::aktif()->get();
    }

    protected $rules = [
        'ad' => 'required|string|min:3|max:35',
        'ilce_id' => 'required|exists:ilce,id',
        'not' => 'nullable|string|max:500',
        'aktif_mi' => 'boolean',
    ];

    protected $messages = [
        'ad.required' => 'Semt adı gereklidir.',
        'ad.string' => 'Semt adı metin olmalıdır.',
        'ad.min' => 'Semt adı en az 3 karakter olmalıdır.',
        'ad.max' => 'Semt adı en fazla 35 karakter olmalıdır.',
        'ilce_id.required' => 'İlçe seçilmelidir.',
        'ilce_id.exists' => 'Seçilen ilçe geçerli değil.',
        'not.string' => 'Not metin olmalıdır.',
        'not.max' => 'Not en fazla 500 karakter olmalıdır.',
        'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
    ];

    public function updatedSehirId($value)
    {
        $this->ilceler = $value ? Ilce::where('sehir_id', $value)->get() : [];
        $this->ilce_id = '';

    }

    public function addSemt()
    {
        $this->validate();

        Semt::create([
            'ad' => $this->ad,
            'ilce_id' => $this->ilce_id,
            'not' => $this->not,
            'aktif_mi' => $this->aktif_mi ? 1 : 0,
        ]);

        $this->dispatch('semtEklendi');
        Flux::modals()->close();
        $this->reset(['ad', 'ilce_id', 'not', 'aktif_mi']);
    }

    public function render()
    {

        return view('livewire.lokasyon.semt.semt-ekleme-modal');
    }
}
