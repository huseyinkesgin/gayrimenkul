<?php

namespace App\Livewire\Sirket\Personel;

use Flux\Flux;
use App\Models\Resim;
use Livewire\Component;
use App\Models\Kisi\Kisi;
use App\Models\Kisi\Sube;
use Livewire\Attributes\On;
use App\Models\Kisi\Personel;
use App\Models\Kisi\Pozisyon;
use Livewire\WithFileUploads;
use App\Models\Kisi\Departman;
use App\Models\Kisi\PersonelRol;
use Illuminate\Support\Facades\Storage;

class PersonelDuzenlemeModal extends Component
{
    use WithFileUploads;

    public ?string $personelId = null;
    public ?Personel $personel = null;
    public bool $loading = false;

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

    // Dropdown verileri
    public $subeler = [];
    public $departmanlar = [];
    public $pozisyonlar = [];
    public $roller = [];

    protected $rules = [
        'photo' => 'nullable|image|max:2048',
        'ad' => 'required|string|min:2|max:100',
        'soyad' => 'required|string|min:2|max:100',
        'tc_kimlik_no' => 'required|string|size:11',
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
        'personel_no' => 'required|string|max:20',
        'siralama' => 'integer|min:0',
        'personel_notlar' => 'nullable|string|max:500',
        'selected_roller' => 'array',
        'selected_roller.*' => 'uuid|exists:personel_rol,id',
    ];

    protected $messages = [
        'ad.required' => 'Ad gereklidir.',
        'ad.string' => 'Ad metin olmalıdır.',
        'ad.min' => 'Ad en az 2 karakter olmalıdır.',
        'ad.max' => 'Ad en fazla 100 karakter olmalıdır.',
        'soyad.required' => 'Soyad gereklidir.',
        'soyad.string' => 'Soyad metin olmalıdır.',
        'soyad.min' => 'Soyad en az 2 karakter olmalıdır.',
        'soyad.max' => 'Soyad en fazla 100 karakter olmalıdır.',
        'tc_kimlik_no.required' => 'TC Kimlik No gereklidir.',
        'tc_kimlik_no.string' => 'TC Kimlik No metin olmalıdır.',
        'tc_kimlik_no.size' => 'TC Kimlik No 11 karakter olmalıdır.',
        'dogum_tarihi.date' => 'Geçerli bir doğum tarihi giriniz.',
        'cinsiyet.in' => 'Cinsiyet Erkek, Kadın veya Diğer olmalıdır.',
        'dogum_yeri.string' => 'Doğum yeri metin olmalıdır.',
        'dogum_yeri.max' => 'Doğum yeri en fazla 100 karakter olmalıdır.',
        'medeni_hali.in' => 'Medeni hali Bekar, Evli, Dul veya Boşanmış olmalıdır.',
        'email.email' => 'Geçerli bir email adresi giriniz.',
        'email.max' => 'Email en fazla 100 karakter olmalıdır.',
        'telefon.string' => 'Telefon metin olmalıdır.',
        'telefon.max' => 'Telefon en fazla 20 karakter olmalıdır.',
        'kisi_notlar.string' => 'Kişi notları metin olmalıdır.',
        'kisi_notlar.max' => 'Kişi notları en fazla 500 karakter olmalıdır.',
        'kisi_aktif_mi.boolean' => 'Kişi aktif mi alanı boolean olmalıdır.',

        'sube_id.required' => 'Şube seçimi gereklidir.',
        'sube_id.uuid' => 'Geçerli bir şube seçiniz.',
        'sube_id.exists' => 'Seçilen şube geçerli değil.',
        'departman_id.required' => 'Departman seçimi gereklidir.',
        'departman_id.uuid' => 'Geçerli bir departman seçiniz.',
        'departman_id.exists' => 'Seçilen departman geçerli değil.',
        'pozisyon_id.required' => 'Pozisyon seçimi gereklidir.',
        'pozisyon_id.uuid' => 'Geçerli bir pozisyon seçiniz.',
        'pozisyon_id.exists' => 'Seçilen pozisyon geçerli değil.',
        'ise_baslama_tarihi.required' => 'İşe başlama tarihi gereklidir.',
        'ise_baslama_tarihi.date' => 'Geçerli bir işe başlama tarihi giriniz.',
        'isten_ayrilma_tarihi.date' => 'Geçerli bir işten ayrılma tarihi giriniz.',
        'isten_ayrilma_tarihi.after' => 'İşten ayrılma tarihi, işe başlama tarihinden sonra olmalıdır.',
        'calisma_durumu.required' => 'Çalışma durumu gereklidir.',
        'calisma_durumu.in' => 'Çalışma durumu Aktif, Pasif, İzinli veya Ayrılmış olmalıdır.',
        'calisma_sekli.string' => 'Çalışma şekli metin olmalıdır.',
        'calisma_sekli.max' => 'Çalışma şekli en fazla 50 karakter olmalıdır.',
        'personel_no.required' => 'Personel numarası gereklidir.',
        'personel_no.string' => 'Personel numarası metin olmalıdır.',
        'personel_no.max' => 'Personel numarası en fazla 20 karakter olmalıdır.',
        'siralama.integer' => 'Sıralama sayı olmalıdır.',
        'siralama.min' => 'Sıralama 0 veya daha büyük olmalıdır.',
        'personel_notlar.string' => 'Personel notları metin olmalıdır.',
        'personel_notlar.max' => 'Personel notları en fazla 500 karakter olmalıdır.',
        'selected_roller.array' => 'Roller dizi olmalıdır.',
        'selected_roller.*.uuid' => 'Geçerli roller seçiniz.',
        'selected_roller.*.exists' => 'Seçilen rollerden biri geçerli değil.',
    ];

    public function mount($personelId = null)
    {
        $this->personelId = $personelId;
        $this->loadDropdownData();

        if ($this->personelId) {
            $this->loadPersonel();
        }
      
    }



    public function loadDropdownData()
    {
        $this->subeler = Sube::where('aktif_mi', true)->orderBy('ad')->get();
        $this->departmanlar = Departman::where('aktif_mi', true)->orderBy('ad')->get();
        $this->pozisyonlar = Pozisyon::where('aktif_mi', true)->orderBy('siralama')->get();
        $this->roller = PersonelRol::where('aktif_mi', true)->orderBy('siralama')->get();
    }

    public function loadPersonel()
    {
        $this->personel = Personel::with(['kisi', 'roller', 'avatar'])->find($this->personelId);

        if (!$this->personel || !$this->personel->kisi) {
            return;
        }

        // Kişi bilgilerini yükle
        $kisi = $this->personel->kisi;
        $this->ad = $kisi->ad;
        $this->soyad = $kisi->soyad;
        $this->tc_kimlik_no = $kisi->tc_kimlik_no;
        $this->dogum_tarihi = $kisi->dogum_tarihi?->format('Y-m-d');
        $this->cinsiyet = $kisi->cinsiyet ?? '';
        $this->dogum_yeri = $kisi->dogum_yeri ?? '';
        $this->medeni_hali = $kisi->medeni_hali ?? '';
        $this->email = $kisi->email ?? '';
        $this->telefon = $kisi->telefon ?? '';
        $this->kisi_notlar = $kisi->notlar ?? '';
        $this->kisi_aktif_mi = $kisi->aktif_mi;

        // Personel bilgilerini yükle
        $this->sube_id = $this->personel->sube_id;
        $this->departman_id = $this->personel->departman_id;
        $this->pozisyon_id = $this->personel->pozisyon_id;
        $this->ise_baslama_tarihi = $this->personel->ise_baslama_tarihi?->format('Y-m-d');
        $this->isten_ayrilma_tarihi = $this->personel->isten_ayrilma_tarihi?->format('Y-m-d');
        $this->calisma_durumu = $this->personel->calisma_durumu;
        $this->calisma_sekli = $this->personel->calisma_sekli ?? '';
        $this->personel_no = $this->personel->personel_no;
        $this->siralama = $this->personel->siralama;
        $this->personel_notlar = $this->personel->notlar ?? '';
        $this->selected_roller = $this->personel->roller->pluck('id')->toArray();

        // Avatar yükle
        $this->currentAvatar = $this->personel->avatar ? Storage::url($this->personel->avatar->url) : null;
    }

    #[On('loadPersonelForEdit')]
    public function handleLoadPersonelForEdit($personelId)
    {
        $this->loading = true;
        $this->personelId = $personelId;
        $this->loadPersonel();
        $this->loading = false;
        $this->dispatch('open-modal', name: 'personel-duzenleme-modal');
    }

    public function updatePersonel()
    {
        // TC Kimlik No unique kontrolü için mevcut personelin kişi ID'sini hariç tut
        $this->rules['tc_kimlik_no'] = 'required|string|size:11|unique:kisi,tc_kimlik_no,' . $this->personel->kisi->id;
        $this->rules['personel_no'] = 'required|string|max:20|unique:personel,personel_no,' . $this->personel->id;

        $this->validate();

        if (!$this->personel || !$this->personel->kisi) {
            return;
        }

        // Kişi bilgilerini güncelle
        $this->personel->kisi->update([
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

        // Personel bilgilerini güncelle
        $this->personel->update([
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

        // Rolleri güncelle
        $this->personel->roller()->sync($this->selected_roller);

        // Avatar güncelle
        if ($this->photo) {
            $this->updateAvatar($this->personel);
        }

        $this->dispatch('personelGuncellendi');
        $this->dispatch('close-modal', name: 'personel-duzenleme-modal');
        $this->resetModal();
    }

    public function resetModal()
    {
        $this->personelId = null;
        $this->personel = null;
        $this->loading = false;

        // Tüm alanları sıfırla
        $this->ad = '';
        $this->soyad = '';
        $this->tc_kimlik_no = '';
        $this->dogum_tarihi = null;
        $this->cinsiyet = '';
        $this->dogum_yeri = '';
        $this->medeni_hali = '';
        $this->email = '';
        $this->telefon = '';
        $this->kisi_notlar = '';
        $this->kisi_aktif_mi = true;

        $this->sube_id = null;
        $this->departman_id = null;
        $this->pozisyon_id = null;
        $this->ise_baslama_tarihi = null;
        $this->isten_ayrilma_tarihi = null;
        $this->calisma_durumu = 'Aktif';
        $this->calisma_sekli = '';
        $this->personel_no = '';
        $this->siralama = 0;
        $this->personel_notlar = '';
        $this->selected_roller = [];

        // Avatar temizle
        $this->photo = null;
        $this->currentAvatar = null;
    }

    private function updateAvatar($personel)
    {
        if (!$this->photo) return;

        // Eski avatarı sil
        if ($personel->avatar) {
            Storage::disk('public')->delete($personel->avatar->url);
            $personel->avatar->delete();
        }

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

    public function removeAvatar()
    {
        if ($this->personel && $this->personel->avatar) {
            Storage::disk('public')->delete($this->personel->avatar->url);
            $this->personel->avatar->delete();
            $this->currentAvatar = null;
        }
        $this->photo = null;
    }

    public function removePhoto()
    {
        $this->photo = null;
    }

    public function clearForm()
    {
        if ($this->personel) {
            $this->loadPersonel(); // Orijinal değerleri yükle
        }
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'personel-duzenleme-modal');
    }

    public function render()
    {
        return view('livewire.sirket.personel.personel-duzenleme-modal');
    }
}
