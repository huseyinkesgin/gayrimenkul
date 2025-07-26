<?php

namespace App\Livewire\Lokasyon\Mahalle;

use Flux\Flux;
use Livewire\Component;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Semt;
use App\Models\Lokasyon\Sehir;
use App\Models\Lokasyon\Mahalle;

class MahalleDuzenlemeModal extends Component
{
    public Mahalle $mahalle;
    public $sehir_id;
    public $ilce_id;
    public $semt_id;
    public $ad;
    public $posta_kodu;
    public $not;
    public bool $aktif_mi;

    public $sehirler = [];
    public $ilceler = [];
    public $semtler = [];

    protected function rules()
    {
        return [
            'sehir_id' => 'required|exists:sehir,id',
            'ilce_id' => 'required|exists:ilce,id',
            'semt_id' => 'required|exists:semt,id',
            'ad' => 'required|string|min:3|max:35',
            'posta_kodu' => 'nullable|string|max:10',
            'not' => 'nullable|string|max:500',
            'aktif_mi' => 'boolean',
        ];
    }

    protected function messages()
    {
        return [
            'sehir_id.required' => 'Şehir seçilmelidir.',
            'sehir_id.exists' => 'Seçilen şehir geçerli değil.',
            'ilce_id.required' => 'İlçe seçilmelidir.',
            'ilce_id.exists' => 'Seçilen ilçe geçerli değil.',
            'semt_id.required' => 'Semt seçilmelidir.',
            'semt_id.exists' => 'Seçilen semt geçerli değil.',
            'ad.required' => 'Mahalle adı gereklidir.',
            'ad.string' => 'Mahalle adı metin olmalıdır.',
            'ad.min' => 'Mahalle adı en az 3 karakter olmalıdır.',
            'ad.max' => 'Mahalle adı en fazla 35 karakter olmalıdır.',
            'posta_kodu.string' => 'Posta kodu metin olmalıdır.',
            'posta_kodu.max' => 'Posta kodu en fazla 10 karakter olmalıdır.',
            'not.string' => 'Not metin olmalıdır.',
            'not.max' => 'Not en fazla 500 karakter olmalıdır.',
            'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
        ];
    }

    public function mount(Mahalle $mahalle)
    {
        $this->mahalle = $mahalle;
        $this->sehirler = Sehir::aktif()->get();
        
        // İlişkili verileri yükle
        $this->sehir_id = $mahalle->semt->ilce->sehir_id;
        $this->ilceler = Ilce::where('sehir_id', $this->sehir_id)->get();
        
        $this->ilce_id = $mahalle->semt->ilce_id;
        $this->semtler = Semt::where('ilce_id', $this->ilce_id)->get();
        
        $this->semt_id = $mahalle->semt_id;
        $this->ad = $mahalle->ad;
        $this->posta_kodu = $mahalle->posta_kodu;
        $this->not = $mahalle->not;
        $this->aktif_mi = $mahalle->aktif_mi;
    }

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

    public function updateMahalle()
    {
        $this->validate();

        $this->mahalle->update([
            'ad' => $this->ad,
            'semt_id' => $this->semt_id,
            'posta_kodu' => $this->posta_kodu,
            'not' => $this->not,
            'aktif_mi' => $this->aktif_mi ? 1 : 0,
        ]);
        
        $this->dispatch('mahalleGuncellendi');
        Flux::modals()->close();
    }

    public function render()
    {
        return view('livewire.lokasyon.mahalle.mahalle-duzenleme-modal');
    }
}
