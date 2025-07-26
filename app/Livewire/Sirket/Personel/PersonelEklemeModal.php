<?php

namespace App\Livewire\Sirket\Personel;

use Flux\Flux;
use App\Models\Adres;
use App\Models\Resim;
use Livewire\Component;
use App\Models\Kisi\Kisi;
use App\Models\Kisi\Sube;
use App\Models\Kisi\Personel;
use App\Models\Kisi\Pozisyon;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Semt;
use Livewire\WithFileUploads;
use App\Models\Kisi\Departman;
use App\Models\Lokasyon\Sehir;
use App\Models\Kisi\PersonelRol;
use App\Models\Lokasyon\Mahalle;
use Illuminate\Support\Facades\Storage;

class PersonelEklemeModal extends Component
{
    use WithFileUploads;

    public $photo;
    public $currentAvatar = null;
    // Kişi bilgileri
    public string $ad = '';
    public string $soyad = '';
    public string $tc_kimlik_no = '';
    public ?string $dogum_tarihi = null;
    public string $cinsiyet = '';
    public string $dogum_yeri = '';
    public string $medeni_hali = '';
    public string $email = '';
    public string $telefon = '';
    public string $kisi_notlar = '';
    public bool $kisi_aktif_mi = true;

    // Personel bilgileri
    public ?string $sube_id = null;
    public ?string $departman_id = null;
    public ?string $pozisyon_id = null;
    public ?string $ise_baslama_tarihi = null;
    public ?string $isten_ayrilma_tarihi = null;
    public string $calisma_durumu = 'Aktif';
    public string $calisma_sekli = '';
    public string $personel_no = '';
    public int $siralama = 0;
    public string $personel_notlar = '';
    public array $selected_roller = [];

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
    public $subeler = [];
    public $departmanlar = [];
    public $pozisyonlar = [];
    public $roller = [];
    public $sehirler = [];
    public $ilceler = [];
    public $semtler = [];
    public $mahalleler = [];

    protected $rules = [
        'photo' => 'nullable|image|max:2048',
        'ad' => 'required|string|min:2|max:100',
        'soyad' => 'required|string|min:2|max:100',
        'tc_kimlik_no' => 'required|string|size:11|unique:kisi,tc_kimlik_no',
        'dogum_tarihi' => 'nullable|date',
        'cinsiyet' => 'nullable|in:Erkek,Kadın,Diğer',
        'dogum_yeri' => 'nullable|string|max:100',
        'medeni_hali' => 'nullable|in:Bekar,Evli,Dul,Boşanmış',
        'email' => 'nullable|email|max:100',
        'telefon' => 'nullable|string|max:20',
        'kisi_notlar' => 'nullable|string|max:500',
        'kisi_aktif_mi' => 'boolean',

        'sube_id' => 'required|uuid|exists:sube,id',
        'departman_id' => 'required|uuid|exists:departman,id',
        'pozisyon_id' => 'required|uuid|exists:pozisyon,id',
        'ise_baslama_tarihi' => 'required|date',
        'isten_ayrilma_tarihi' => 'nullable|date|after:ise_baslama_tarihi',
        'calisma_durumu' => 'required|in:Aktif,Pasif,İzinli,Ayrılmış',
        'calisma_sekli' => 'nullable|string|max:50',
        'personel_no' => 'required|string|max:20|unique:personel,personel_no',
        'siralama' => 'integer|min:0',
        'personel_notlar' => 'nullable|string|max:500',
        'selected_roller' => 'array',
        'selected_roller.*' => 'uuid|exists:personel_rol,id',

        'adres_adi' => 'required|string|max:100',
        'adres_detay' => 'required|string|max:500',
        'posta_kodu' => 'nullable|string|max:10',
        'sehir_id' => 'required|uuid|exists:sehir,id',
        'ilce_id' => 'nullable|uuid|exists:ilce,id',
        'semt_id' => 'nullable|uuid|exists:semt,id',
        'mahalle_id' => 'nullable|uuid|exists:mahalle,id',
        'varsayilan_mi' => 'boolean',
    ];

    public function mount()
    {
        $this->subeler = Sube::where('aktif_mi', true)->orderBy('ad')->get();
        $this->departmanlar = Departman::where('aktif_mi', true)->orderBy('ad')->get();
        $this->pozisyonlar = Pozisyon::where('aktif_mi', true)->orderBy('siralama')->get();
        $this->roller = PersonelRol::where('aktif_mi', true)->orderBy('siralama')->get();
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

    public function addPersonel()
    {
        $this->validate();

        // Önce kişi oluştur
        $kisi = Kisi::create([
            'ad' => $this->ad,
            'soyad' => $this->soyad,
            'tc_kimlik_no' => $this->tc_kimlik_no,
            'dogum_tarihi' => $this->dogum_tarihi ?: null,
            'cinsiyet' => $this->cinsiyet ?: null,
            'dogum_yeri' => $this->dogum_yeri ?: null,
            'medeni_hali' => $this->medeni_hali ?: null,
            'email' => $this->email ?: null,
            'telefon' => $this->telefon ?: null,
            'notlar' => $this->kisi_notlar ?: null,
            'aktif_mi' => $this->kisi_aktif_mi,
        ]);

        // Kişiye adres ekle
        $kisi->adresler()->create([
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

        // Personel oluştur
        $personel = Personel::create([
            'kisi_id' => $kisi->id,
            'sube_id' => $this->sube_id,
            'departman_id' => $this->departman_id,
            'pozisyon_id' => $this->pozisyon_id,
            'ise_baslama_tarihi' => $this->ise_baslama_tarihi ?: null,
            'isten_ayrilma_tarihi' => $this->isten_ayrilma_tarihi ?: null,
            'calisma_durumu' => $this->calisma_durumu,
            'calisma_sekli' => $this->calisma_sekli ?: null,
            'personel_no' => $this->personel_no,
            'siralama' => $this->siralama,
            'notlar' => $this->personel_notlar ?: null,
        ]);

        // Personel rollerini ekle
        if (!empty($this->selected_roller)) {
            $personel->roller()->attach($this->selected_roller);
        }

        // Avatar yükle
        if ($this->photo) {
            $this->uploadAvatar($personel);
        }

        $this->dispatch('personelEklendi');
        $this->dispatch('close-modal', name: 'personel-ekleme-modal');
        $this->clearForm();
    }

    public function clearForm()
    {
        // Kişi bilgileri
        $this->ad = '';
        $this->soyad = '';
        $this->tc_kimlik_no = '';
        $this->dogum_tarihi = '';
        $this->cinsiyet = '';
        $this->dogum_yeri = '';
        $this->medeni_hali = '';
        $this->email = '';
        $this->telefon = '';
        $this->kisi_notlar = '';

        // Personel bilgileri
        $this->sube_id = null;
        $this->departman_id = null;
        $this->pozisyon_id = null;
        $this->personel_no = '';
        $this->ise_baslama_tarihi = '';
        $this->isten_ayrilma_tarihi = '';
        $this->calisma_durumu = 'Aktif';
        $this->calisma_sekli = '';
        $this->siralama = 0;
        $this->personel_notlar = '';
        $this->selected_roller = [];

        // Adres bilgileri
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

        // Avatar temizle
        $this->photo = null;
        $this->currentAvatar = null;
    }

    private function uploadAvatar($personel)
    {
        if (!$this->photo) return;

        // Dosya adını oluştur: ad_soyad_avatar.uzanti
        $adSoyad = strtolower($this->ad . '_' . $this->soyad);
        $adSoyad = str_replace(['ç', 'ğ', 'ı', 'ö', 'ş', 'ü'], ['c', 'g', 'i', 'o', 's', 'u'], $adSoyad);
        $adSoyad = preg_replace('/[^a-z0-9_]/', '', $adSoyad);
        
        $extension = $this->photo->getClientOriginalExtension();
        $fileName = $adSoyad . '_avatar.' . $extension;

        // Dosyayı public/avatar/personel klasörüne kaydet
        $path = $this->photo->storeAs('avatar/personel', $fileName, 'public');

        // Resim kaydını oluştur
        $personel->resimler()->create([
            'url' => $path,
            'aktif_mi' => true,
        ]);
    }

    public function removePhoto()
    {
        $this->photo = null;
        $this->currentAvatar = null;
    }

    public function removeAvatar()
    {
        $this->photo = null;
        $this->currentAvatar = null;
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'personel-ekleme-modal');
    }

    public function render()
    {
        return view('livewire.sirket.personel.personel-ekleme-modal');
    }
}
