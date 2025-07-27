<?php

namespace App\Livewire\Galeri;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\GaleriService;
use App\Services\ResimUploadService;
use App\Enums\ResimKategorisi;
use Illuminate\Support\Facades\Log;

/**
 * Galeri Görüntüleme Livewire Bileşeni
 * 
 * Bu bileşen mülk galerilerini görüntülemek ve yönetmek için kullanılır.
 * 
 * Özellikler:
 * - Galeri resimlerini görüntüleme
 * - Resim sıralama (drag & drop)
 * - Ana resim belirleme
 * - Kategori bazlı filtreleme
 * - Resim detay görüntüleme
 * - Toplu işlemler
 */
class GaleriGoruntuleme extends Component
{
    use WithFileUploads;

    // Mülk bilgileri
    public string $mulkType;
    public string $mulkId;
    public string $mulkBaslik = '';

    // Galeri durumu
    public array $resimler = [];
    public array $galeriIstatistikleri = [];
    public array $galeriKurallari = [];
    public bool $galeriAktif = false;

    // Filtreleme ve sıralama
    public ?string $secilenKategori = null;
    public string $siralama = 'sira_asc';
    public array $kategoriSecenekleri = [];

    // Modal durumları
    public bool $detayModalAcik = false;
    public bool $siralamaModuAktif = false;
    public bool $topluIslemModuAktif = false;

    // Seçili resim
    public ?array $secilenResim = null;
    public array $secilenResimler = [];

    // Mesajlar
    public string $mesaj = '';
    public string $mesajTipi = '';

    protected $listeners = [
        'resimSiralandi' => 'resimSiralamasiniGuncelle',
        'anaResimBelirlendi' => 'anaResimBelirle',
        'resimlerSecildi' => 'resimleriSec'
    ];

    public function mount(string $mulkType, string $mulkId, string $mulkBaslik = '')
    {
        $this->mulkType = $mulkType;
        $this->mulkId = $mulkId;
        $this->mulkBaslik = $mulkBaslik;

        $this->galeriVerileriniYukle();
    }

    public function render()
    {
        return view('livewire.galeri.galeri-goruntuleme');
    }

    /**
     * Galeri verilerini yükle
     */
    public function galeriVerileriniYukle()
    {
        try {
            $galeriService = app(GaleriService::class);

            // Galeri kurallarını al
            $kurallarSonuc = $galeriService->galeriKurallariniGetir($this->mulkType);
            if ($kurallarSonuc['basarili']) {
                $this->galeriKurallari = $kurallarSonuc['kurallar'] ?? [];
                $this->galeriAktif = $this->galeriKurallari['galeri_aktif'] ?? false;
            }

            if (!$this->galeriAktif) {
                $this->mesajGoster('Bu mülk tipi için galeri mevcut değil.', 'warning');
                return;
            }

            // Kategori seçeneklerini al
            $kategorilerSonuc = $galeriService->mulkTipiIcinKategorileriGetir($this->mulkType);
            if ($kategorilerSonuc['basarili']) {
                $this->kategoriSecenekleri = $kategorilerSonuc['tum_kategoriler'];
            }

            // Resimleri yükle
            $this->resimleriYukle();

            // İstatistikleri al
            $this->istatistikleriYukle();

        } catch (\Exception $e) {
            Log::error('Galeri verileri yükleme hatası: ' . $e->getMessage());
            $this->mesajGoster('Galeri verileri yüklenirken hata oluştu.', 'error');
        }
    }

    /**
     * Resimleri yükle
     */
    public function resimleriYukle()
    {
        $galeriService = app(GaleriService::class);
        
        $kategori = $this->secilenKategori ? 
            ResimKategorisi::from($this->secilenKategori) : null;

        $sonuc = $galeriService->galeriResimleriGetir(
            $this->mulkType,
            $this->mulkId,
            $kategori,
            $this->siralama
        );

        if ($sonuc['basarili']) {
            $this->resimler = $sonuc['resimler'];
        } else {
            $this->mesajGoster($sonuc['hata'], 'error');
        }
    }

    /**
     * İstatistikleri yükle
     */
    public function istatistikleriYukle()
    {
        $galeriService = app(GaleriService::class);
        
        $sonuc = $galeriService->galeriIstatistikleri($this->mulkType, $this->mulkId);

        if ($sonuc['basarili']) {
            $this->galeriIstatistikleri = $sonuc;
        }
    }

    /**
     * Kategori filtresi değiştir
     */
    public function kategoriDegistir()
    {
        $this->resimleriYukle();
    }

    /**
     * Sıralama değiştir
     */
    public function siralamaDegistir()
    {
        $this->resimleriYukle();
    }

    /**
     * Resim detayını göster
     */
    public function resimDetayiGoster(int $resimId)
    {
        $resim = collect($this->resimler)->firstWhere('id', $resimId);
        if ($resim) {
            $this->secilenResim = $resim;
            $this->detayModalAcik = true;
        }
    }

    /**
     * Detay modalını kapat
     */
    public function detayModalKapat()
    {
        $this->detayModalAcik = false;
        $this->secilenResim = null;
    }

    /**
     * Ana resim belirle
     */
    public function anaResimBelirle(int $resimId)
    {
        try {
            $galeriService = app(GaleriService::class);
            
            $sonuc = $galeriService->anaResimBelirle($resimId, $this->mulkType, $this->mulkId);

            if ($sonuc['basarili']) {
                $this->mesajGoster($sonuc['mesaj'], 'success');
                $this->galeriVerileriniYukle();
            } else {
                $this->mesajGoster($sonuc['hata'], 'error');
            }

        } catch (\Exception $e) {
            Log::error('Ana resim belirleme hatası: ' . $e->getMessage());
            $this->mesajGoster('Ana resim belirlenirken hata oluştu.', 'error');
        }
    }

    /**
     * Resim sıralamasını güncelle
     */
    public function resimSiralamasiniGuncelle(array $resimSiralari)
    {
        try {
            $galeriService = app(GaleriService::class);
            
            $sonuc = $galeriService->resimSiralamasiGuncelle($resimSiralari);

            if ($sonuc['basarili']) {
                $this->mesajGoster($sonuc['mesaj'], 'success');
                $this->resimleriYukle();
            } else {
                $this->mesajGoster($sonuc['hata'], 'error');
            }

        } catch (\Exception $e) {
            Log::error('Resim sıralama güncelleme hatası: ' . $e->getMessage());
            $this->mesajGoster('Resim sıralaması güncellenirken hata oluştu.', 'error');
        }
    }

    /**
     * Sıralama modunu aç/kapat
     */
    public function siralamaModunuToggle()
    {
        $this->siralamaModuAktif = !$this->siralamaModuAktif;
        
        if (!$this->siralamaModuAktif) {
            $this->resimleriYukle();
        }
    }

    /**
     * Toplu işlem modunu aç/kapat
     */
    public function topluIslemModunuToggle()
    {
        $this->topluIslemModuAktif = !$this->topluIslemModuAktif;
        $this->secilenResimler = [];
    }

    /**
     * Resim seç/seçimi kaldır
     */
    public function resimSec(int $resimId)
    {
        if (in_array($resimId, $this->secilenResimler)) {
            $this->secilenResimler = array_diff($this->secilenResimler, [$resimId]);
        } else {
            $this->secilenResimler[] = $resimId;
        }
    }

    /**
     * Tüm resimleri seç
     */
    public function tumResimleriSec()
    {
        $this->secilenResimler = collect($this->resimler)->pluck('id')->toArray();
    }

    /**
     * Seçimi temizle
     */
    public function secimiTemizle()
    {
        $this->secilenResimler = [];
    }

    /**
     * Seçili resimleri sil
     */
    public function seciliResimleriSil()
    {
        if (empty($this->secilenResimler)) {
            $this->mesajGoster('Silinecek resim seçilmedi.', 'warning');
            return;
        }

        try {
            $galeriService = app(GaleriService::class);
            
            $sonuc = $galeriService->topluResimSil($this->secilenResimler);

            if ($sonuc['basarili']) {
                $this->mesajGoster($sonuc['mesaj'], 'success');
                $this->secilenResimler = [];
                $this->topluIslemModuAktif = false;
                $this->galeriVerileriniYukle();
            } else {
                $this->mesajGoster($sonuc['hata'], 'error');
            }

        } catch (\Exception $e) {
            Log::error('Toplu resim silme hatası: ' . $e->getMessage());
            $this->mesajGoster('Resimler silinirken hata oluştu.', 'error');
        }
    }

    /**
     * Galeri organizasyon önerilerini al
     */
    public function organizasyonOnerileriniAl()
    {
        try {
            $galeriService = app(GaleriService::class);
            
            $sonuc = $galeriService->galeriOrganizasyonuOner($this->mulkType, $this->mulkId);

            if ($sonuc['basarili']) {
                $this->dispatch('organizasyonOnerileriGoster', $sonuc['oneriler']);
            } else {
                $this->mesajGoster($sonuc['hata'], 'error');
            }

        } catch (\Exception $e) {
            Log::error('Organizasyon önerileri alma hatası: ' . $e->getMessage());
            $this->mesajGoster('Organizasyon önerileri alınırken hata oluştu.', 'error');
        }
    }

    /**
     * Mesaj göster
     */
    private function mesajGoster(string $mesaj, string $tip = 'info')
    {
        $this->mesaj = $mesaj;
        $this->mesajTipi = $tip;

        // 5 saniye sonra mesajı temizle
        $this->dispatch('mesajGoster', [
            'mesaj' => $mesaj,
            'tip' => $tip,
            'sure' => 5000
        ]);
    }

    /**
     * Computed properties
     */
    public function getAnaResimProperty()
    {
        return collect($this->resimler)->firstWhere('ana_resim_mi', true);
    }

    public function getToplamResimSayisiProperty()
    {
        return count($this->resimler);
    }

    public function getSeciliResimSayisiProperty()
    {
        return count($this->secilenResimler);
    }

    public function getGaleriDolulukOraniProperty()
    {
        if (!isset($this->galeriIstatistikleri['doluluk_orani'])) {
            return 0;
        }
        return $this->galeriIstatistikleri['doluluk_orani'];
    }
}