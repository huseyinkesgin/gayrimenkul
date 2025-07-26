<?php

namespace App\Livewire\Lokasyon\Semt;

use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Sehir;
use App\Models\Lokasyon\Semt;
use Flux\Flux;
use Livewire\Component;

class SemtDuzenlemeModal extends Component
{
    public Semt $semt;
    public $sehir_id;
    public $ilce_id;
    public $ad;
    public $not;
    public bool $aktif_mi;

    public $sehirler = [];
    public $ilceler;


    protected function rules()
    {
        return [
            'sehir_id' => 'required|exists:sehir,id',
            'ilce_id' => 'required|exists:ilce,id',
            'ad' => 'required|string|min:3|max:35',
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
            'ad.required' => 'Semt adı gereklidir.',
            'ad.string' => 'Semt adı metin olmalıdır.',
            'ad.min' => 'Semt adı en az 3 karakter olmalıdır.',
            'ad.max' => 'Semt adı en fazla 35 karakter olmalıdır.',
            'not.string' => 'Not metin olmalıdır.',
            'not.max' => 'Not en fazla 500 karakter olmalıdır.',
            'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
        ];
    }


    public function mount(Semt $semt)
    {

        $this->sehirler = Sehir::aktif()->get();
        $this->sehir_id = $semt->ilce->sehir_id;

        $this->ilceler = Ilce::where('sehir_id', $this->sehir_id)->get();
        $this->ilce_id = $semt->ilce_id;
        $this->ad = $semt->ad;
        $this->not = $semt->not;
        $this->aktif_mi = $semt->aktif_mi;
    }

    public function updatedSehirId($value)
    {
        $this->ilceler = $value ? Ilce::where('sehir_id', $value)->get() : [];
        $this->ilce_id = '';
    }


    public function updateSemt()
    {
        $this->validate();

        $this->semt->update([
            'ad' => $this->ad,
            'ilce_id' => $this->ilce_id,
            'not' => $this->not,
            'aktif_mi' => $this->aktif_mi ? 1 : 0,
        ]);
        $this->dispatch('semtGuncellendi');

        Flux::modals()->close();
    }

    public function render()
    {
        return view('livewire.lokasyon.semt.semt-duzenleme-modal');
    }
}
