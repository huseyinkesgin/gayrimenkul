<?php

namespace App\Livewire\Sirket\Sube;

use Flux\Flux;
use Livewire\Component;
use App\Models\Kisi\Sube;
use App\Models\Adres;
use App\Models\Lokasyon\Sehir;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Semt;
use App\Models\Lokasyon\Mahalle;
use App\Livewire\BaseTablo;

class SubeEklemeModal extends Component
{
    // Şube bilgileri
    public string $ad = '';
    public string $kod = '';
    public string $telefon = '';
    public string $email = '';
    public string $not = '';
    public bool $aktif_mi = true;

    // Adres bilgileri
    public string $adres_adi = '';
    public string $adres_detay = '';
    public string $posta_kodu = '';
    public ?string $sehir_id = null;
    public ?string $ilce_id = null;
    public ?string $semt_id = null;
    public ?string $mahalle_id = null;
    public bool $varsayilan_mi = true;

    // Dropdown verileri
    public $sehirler = [];
    public $ilceler = [];
    public $semtler = [];
    public $mahalleler = [];

    protected $rules = [
        'ad' => 'required|string|min:2|max:100',
        'kod' => 'nullable|string|max:20',
        'telefon' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:100',
        'not' => 'nullable|string|max:500',
        'aktif_mi' => 'boolean',
        'adres_adi' => 'nullable|string|max:100',
        'adres_detay' => 'required|string|max:500',
        'posta_kodu' => 'nullable|string|max:10',
        'sehir_id' => 'required|uuid|exists:sehir,id',
        'ilce_id' => 'nullable|uuid|exists:ilce,id',
        'semt_id' => 'nullable|uuid|exists:semt,id',
        'mahalle_id' => 'nullable|uuid|exists:mahalle,id',
        'varsayilan_mi' => 'boolean',
    ];

    protected $messages = [
        'ad.required' => 'Şube adı gereklidir.',
        'ad.string' => 'Şube adı metin olmalıdır.',
        'ad.min' => 'Şube adı en az 2 karakter olmalıdır.',
        'ad.max' => 'Şube adı en fazla 100 karakter olmalıdır.',
        'kod.string' => 'Kod metin olmalıdır.',
        'kod.max' => 'Kod en fazla 20 karakter olmalıdır.',
        'telefon.string' => 'Telefon metin olmalıdır.',
        'telefon.max' => 'Telefon en fazla 20 karakter olmalıdır.',
        'email.email' => 'Geçerli bir email adresi giriniz.',
        'email.max' => 'Email en fazla 100 karakter olmalıdır.',
        'not.string' => 'Not metin olmalıdır.',
        'not.max' => 'Not en fazla 500 karakter olmalıdır.',
        'aktif_mi.boolean' => 'Aktif mi alanı boolean olmalıdır.',
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
    ];

    public function mount()
    {
        $this->sehirler = Sehir::where('aktif_mi', true)->orderBy('ad')->get();
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

    public function addSube()
    {
        $this->validate();

        // Şube oluştur
        $sube = Sube::create([
            'ad' => $this->ad,
            'kod' => $this->kod,
            'telefon' => $this->telefon,
            'email' => $this->email,
            'notlar' => $this->not,
            'aktif_mi' => $this->aktif_mi,
        ]);

        // Adres oluştur ve şube ile ilişkilendir
        $sube->adresler()->create([
            'adres_adi' => $this->adres_adi,
            'adres_detay' => $this->adres_detay,
            'posta_kodu' => $this->posta_kodu ?: null,
            'sehir_id' => $this->sehir_id,
            'ilce_id' => $this->ilce_id ?: null,
            'semt_id' => $this->semt_id ?: null,
            'mahalle_id' => $this->mahalle_id ?: null,
            'varsayilan_mi' => $this->varsayilan_mi,
            'aktif_mi' => true,
        ]);

        $this->dispatch('subeEklendi');
        Flux::modals()->close();
    }

    public function render()
    {
        return view('livewire.sirket.sube.sube-ekleme-modal');
    }

}


