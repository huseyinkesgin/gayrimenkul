<?php

namespace App\Livewire\Sirket\Personel;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Kisi\Personel;
use App\Models\Kisi\Kisi;
use App\Models\Adres;
use App\Models\Lokasyon\Sehir;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Semt;
use App\Models\Lokasyon\Mahalle;

class PersonelAdresEklemeModal extends Component
{
    public ?string $personelId = null;
    public ?Personel $personel = null;
    public ?Kisi $kisi = null;

    // Adres bilgileri
    public string $adres_adi = '';
    public string $adres_detay = '';
    public string $posta_kodu = '';
    public ?string $sehir_id = null;
    public ?string $ilce_id = null;
    public ?string $semt_id = null;
    public ?string $mahalle_id = null;
    public bool $varsayilan_mi = false;
    public string $notlar = '';

    // Dropdown verileri
    public $sehirler = [];
    public $ilceler = [];
    public $semtler = [];
    public $mahalleler = [];

    protected $rules = [
        'adres_adi' => 'required|string|max:100',
        'adres_detay' => 'required|string|max:500',
        'posta_kodu' => 'nullable|string|max:10',
        'sehir_id' => 'required|uuid|exists:sehir,id',
        'ilce_id' => 'nullable|uuid|exists:ilce,id',
        'semt_id' => 'nullable|uuid|exists:semt,id',
        'mahalle_id' => 'nullable|uuid|exists:mahalle,id',
        'varsayilan_mi' => 'boolean',
        'notlar' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'adres_adi.required' => 'Adres adı gereklidir.',
        'adres_adi.string' => 'Adres adı metin olmalıdır.',
        'adres_adi.max' => 'Adres adı en fazla 100 karakter olmalıdır.',
        'adres_detay.required' => 'Adres detayı gereklidir.',
        'adres_detay.string' => 'Adres detayı metin olmalıdır.',
        'adres_detay.max' => 'Adres detayı en fazla 500 karakter olmalıdır.',
        'posta_kodu.string' => 'Posta kodu metin olmalıdır.',
        'posta_kodu.max' => 'Posta kodu en fazla 10 karakter olmalıdır.',
        'sehir_id.required' => 'Şehir seçimi gereklidir.',
        'sehir_id.exists' => 'Seçilen şehir geçerli değil.',
        'ilce_id.exists' => 'Seçilen ilçe geçerli değil.',
        'semt_id.exists' => 'Seçilen semt geçerli değil.',
        'mahalle_id.exists' => 'Seçilen mahalle geçerli değil.',
        'varsayilan_mi.boolean' => 'Varsayılan mi alanı boolean olmalıdır.',
        'notlar.string' => 'Notlar metin olmalıdır.',
        'notlar.max' => 'Notlar en fazla 500 karakter olmalıdır.',
    ];

    public function mount()
    {
        $this->sehirler = Sehir::where('aktif_mi', true)->orderBy('ad')->get();
    }

    #[On('loadPersonelForAdres')]
    public function handleLoadPersonelForAdres($personelId)
    {
        $this->personelId = $personelId;
        $this->loadPersonel();
        $this->dispatch('open-modal', name: 'personel-adres-ekleme-modal');
    }

    public function loadPersonel()
    {
        $this->personel = Personel::with('kisi')->find($this->personelId);
        
        if ($this->personel && $this->personel->kisi) {
            $this->kisi = $this->personel->kisi;
        }
    }

    public function updatedSehirId()
    {
        $this->ilce_id = null;
        $this->semt_id = null;
        $this->mahalle_id = null;
        $this->ilceler = [];
        $this->semtler = [];
        $this->mahalleler = [];

        if ($this->sehir_id) {
            $this->ilceler = Ilce::where('sehir_id', $this->sehir_id)
                ->where('aktif_mi', true)
                ->orderBy('ad')
                ->get();
        }
    }

    public function updatedIlceId()
    {
        $this->semt_id = null;
        $this->mahalle_id = null;
        $this->semtler = [];
        $this->mahalleler = [];

        if ($this->ilce_id) {
            $this->semtler = Semt::where('ilce_id', $this->ilce_id)
                ->where('aktif_mi', true)
                ->orderBy('ad')
                ->get();
        }
    }

    public function updatedSemtId()
    {
        $this->mahalle_id = null;
        $this->mahalleler = [];

        if ($this->semt_id) {
            $this->mahalleler = Mahalle::where('semt_id', $this->semt_id)
                ->where('aktif_mi', true)
                ->orderBy('ad')
                ->get();
        }
    }

    public function addAdres()
    {
        $this->validate();

        if (!$this->kisi) {
            return;
        }

        // Eğer varsayılan adres olarak işaretlendiyse, diğer adresleri varsayılan olmaktan çıkar
        if ($this->varsayilan_mi) {
            $this->kisi->adresler()->update(['varsayilan_mi' => false]);
        }

        // Yeni adres ekle
        $this->kisi->adresler()->create([
            'adres_adi' => $this->adres_adi,
            'adres_detay' => $this->adres_detay,
            'posta_kodu' => $this->posta_kodu ?: null,
            'sehir_id' => $this->sehir_id,
            'ilce_id' => $this->ilce_id ?: null,
            'semt_id' => $this->semt_id ?: null,
            'mahalle_id' => $this->mahalle_id ?: null,
            'varsayilan_mi' => $this->varsayilan_mi,
            'aktif_mi' => true,
            'notlar' => $this->notlar,
        ]);

        $this->dispatch('personelAdresEklendi');
        $this->dispatch('close-modal', name: 'personel-adres-ekleme-modal');
        $this->resetModal();
    }

    public function clearForm()
    {
        $this->adres_adi = '';
        $this->adres_detay = '';
        $this->posta_kodu = '';
        $this->sehir_id = null;
        $this->ilce_id = null;
        $this->semt_id = null;
        $this->mahalle_id = null;
        $this->varsayilan_mi = false;
        $this->notlar = '';
        
        $this->ilceler = [];
        $this->semtler = [];
        $this->mahalleler = [];
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'personel-adres-ekleme-modal');
    }

    public function resetModal()
    {
        $this->personelId = null;
        $this->personel = null;
        $this->kisi = null;
        
        $this->clearForm();
    }

    public function render()
    {
        return view('livewire.sirket.personel.personel-adres-ekleme-modal');
    }
}
