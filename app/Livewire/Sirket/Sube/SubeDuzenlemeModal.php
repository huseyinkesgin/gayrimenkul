<?php

namespace App\Livewire\Sirket\Sube;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Kisi\Sube;
use App\Models\Adres;
use App\Models\Lokasyon\Sehir;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Semt;
use App\Models\Lokasyon\Mahalle;

class SubeDuzenlemeModal extends Component
{
    public ?string $subeId = null;
    public ?Sube $sube = null;
    public ?Adres $adres = null;
    public bool $loading = false;

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

    public function mount($subeId = null)
    {
        $this->subeId = $subeId;
        $this->sehirler = Sehir::where('aktif_mi', true)->orderBy('ad')->get();

        if ($this->subeId) {
            $this->loadSube();
        }
    }

    public function loadSube()
    {
        $this->sube = Sube::with('adresler')->find($this->subeId);

        if (!$this->sube) {
            return;
        }

        // Şube bilgilerini yükle
        $this->ad = $this->sube->ad;
        $this->kod = $this->sube->kod ?? '';
        $this->telefon = $this->sube->telefon ?? '';
        $this->email = $this->sube->email ?? '';
        $this->not = $this->sube->notlar ?? '';
        $this->aktif_mi = $this->sube->aktif_mi;

        // İlk adresi yükle (varsayılan veya ilk adres)
        $this->adres = $this->sube->adresler->where('varsayilan_mi', true)->first()
                      ?? $this->sube->adresler->first();

        if ($this->adres) {
            $this->adres_adi = $this->adres->adres_adi;
            $this->adres_detay = $this->adres->adres_detay;
            $this->posta_kodu = $this->adres->posta_kodu ?? '';
            $this->sehir_id = $this->adres->sehir_id;
            $this->ilce_id = $this->adres->ilce_id;
            $this->semt_id = $this->adres->semt_id;
            $this->mahalle_id = $this->adres->mahalle_id;
            $this->varsayilan_mi = $this->adres->varsayilan_mi;

            // Cascade dropdown'ları yükle
            $this->loadCascadeData();
        }
    }

    public function loadCascadeData()
    {
        if ($this->sehir_id) {
            $this->ilceler = Ilce::where('sehir_id', $this->sehir_id)
                ->where('aktif_mi', true)
                ->orderBy('ad')
                ->get();
        }

        if ($this->ilce_id) {
            $this->semtler = Semt::where('ilce_id', $this->ilce_id)
                ->where('aktif_mi', true)
                ->orderBy('ad')
                ->get();
        }

        if ($this->semt_id) {
            $this->mahalleler = Mahalle::where('semt_id', $this->semt_id)
                ->where('aktif_mi', true)
                ->orderBy('ad')
                ->get();
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

    public function updateSube()
    {
        $this->validate();

        if (!$this->sube) {
            return;
        }

        // Şube bilgilerini güncelle
        $this->sube->update([
            'ad' => $this->ad,
            'kod' => $this->kod,
            'telefon' => $this->telefon,
            'email' => $this->email,
            'notlar' => $this->not,
            'aktif_mi' => $this->aktif_mi,
        ]);

        // Adres bilgilerini güncelle veya oluştur
        if ($this->adres) {
            // Mevcut adresi güncelle
            $this->adres->update([
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
        } else {
            // Yeni adres oluştur
            $this->sube->adresler()->create([
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
        }

        $this->dispatch('subeGuncellendi');
        $this->resetModal();
        Flux::modals()->close();
    }

    #[On('loadSube')]
    public function handleLoadSube($subeId)
    {
        $this->loading = true;
        $this->subeId = $subeId;
        $this->loadSube();
        $this->loading = false;
    }

    #[On('modal-closed:sube-duzenleme-modal')]
    public function handleModalClosed()
    {
        $this->resetModal();
    }

    public function resetModal()
    {
        $this->subeId = null;
        $this->sube = null;
        $this->adres = null;
        $this->loading = false;

        // Form alanlarını sıfırla
        $this->ad = '';
        $this->kod = '';
        $this->telefon = '';
        $this->email = '';
        $this->not = '';
        $this->aktif_mi = true;

        $this->adres_adi = '';
        $this->adres_detay = '';
        $this->posta_kodu = '';
        $this->sehir_id = null;
        $this->ilce_id = null;
        $this->semt_id = null;
        $this->mahalle_id = null;
        $this->varsayilan_mi = true;

        $this->ilceler = [];
        $this->semtler = [];
        $this->mahalleler = [];
    }

    public function render()
    {
        return view('livewire.sirket.sube.sube-duzenleme-modal');
    }
}
