<?php

namespace App\Traits;

use App\Models\Resim;
use App\Enums\ResimKategorisi;
use App\Services\GaleriService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Galeri Yönetimi Trait'i
 * 
 * Bu trait, modellere galeri yönetimi özelliklerini ekler.
 * Mülk modelleri bu trait'i kullanarak galeri işlemlerini gerçekleştirebilir.
 */
trait HasGaleri
{
    /**
     * Resimler ilişkisi
     */
    public function resimler(): MorphMany
    {
        return $this->morphMany(Resim::class, 'imagable')
                    ->where('aktif_mi', true)
                    ->orderBy('sira', 'asc')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Galeri resimleri ilişkisi
     */
    public function galeriResimleri(): MorphMany
    {
        $galeriKategorileri = ResimKategorisi::galeriKategorileri();
        $galeriKategorileriValues = array_map(fn($kategori) => $kategori->value, $galeriKategorileri);
        
        return $this->morphMany(Resim::class, 'imagable')
                    ->whereIn('kategori', $galeriKategorileriValues)
                    ->where('aktif_mi', true)
                    ->orderBy('sira', 'asc')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Ana resim ilişkisi
     */
    public function anaResim()
    {
        return $this->morphOne(Resim::class, 'imagable')
                    ->where('ana_resim_mi', true)
                    ->where('aktif_mi', true);
    }

    /**
     * Kategori bazında resimler
     */
    public function kategoriBazindaResimler(ResimKategorisi $kategori): MorphMany
    {
        return $this->morphMany(Resim::class, 'imagable')
                    ->where('kategori', $kategori)
                    ->where('aktif_mi', true)
                    ->orderBy('sira', 'asc')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Galeri aktif mi?
     */
    public function galeriAktifMi(): bool
    {
        $galeriService = app(GaleriService::class);
        $kurallar = $galeriService->galeriKurallariniGetir(static::class);
        
        return $kurallar['basarili'] && 
               isset($kurallar['kurallar']['galeri_aktif']) && 
               $kurallar['kurallar']['galeri_aktif'];
    }

    /**
     * Galeri istatistiklerini al
     */
    public function galeriIstatistikleri(): array
    {
        if (!$this->galeriAktifMi()) {
            return [
                'basarili' => false,
                'hata' => 'Bu mülk tipi için galeri mevcut değil.'
            ];
        }

        $galeriService = app(GaleriService::class);
        return $galeriService->galeriIstatistikleri(static::class, $this->id);
    }

    /**
     * Ana resim URL'ini al
     */
    public function anaResimUrl(string $boyut = 'medium'): ?string
    {
        $anaResim = $this->anaResim;
        if (!$anaResim) {
            return null;
        }

        $resimUploadService = app(\App\Services\ResimUploadService::class);
        return $resimUploadService->resimUrlOlustur($anaResim, $boyut);
    }

    /**
     * İlk galeri resmini al (ana resim yoksa)
     */
    public function ilkGaleriResmi()
    {
        return $this->galeriResimleri()->first();
    }

    /**
     * İlk galeri resmi URL'ini al
     */
    public function ilkGaleriResmiUrl(string $boyut = 'medium'): ?string
    {
        // Önce ana resmi kontrol et
        $anaResimUrl = $this->anaResimUrl($boyut);
        if ($anaResimUrl) {
            return $anaResimUrl;
        }

        // Ana resim yoksa ilk galeri resmini al
        $ilkResim = $this->ilkGaleriResmi();
        if (!$ilkResim) {
            return null;
        }

        $resimUploadService = app(\App\Services\ResimUploadService::class);
        return $resimUploadService->resimUrlOlustur($ilkResim, $boyut);
    }

    /**
     * Galeri resim sayısını al
     */
    public function galeriResimSayisi(): int
    {
        return $this->galeriResimleri()->count();
    }

    /**
     * Belirli kategoride resim var mı?
     */
    public function kategoriResmiVarMi(ResimKategorisi $kategori): bool
    {
        return $this->kategoriBazindaResimler($kategori)->exists();
    }

    /**
     * Galeri tamamlandı mı?
     */
    public function galeriTamamlandiMi(): bool
    {
        $istatistikler = $this->galeriIstatistikleri();
        return $istatistikler['basarili'] && 
               isset($istatistikler['galeri_tamamlandi']) && 
               $istatistikler['galeri_tamamlandi'];
    }

    /**
     * Galeri doluluk oranını al
     */
    public function galeriDolulukOrani(): float
    {
        $istatistikler = $this->galeriIstatistikleri();
        return $istatistikler['basarili'] && isset($istatistikler['doluluk_orani']) 
            ? $istatistikler['doluluk_orani'] 
            : 0.0;
    }

    /**
     * Eksik galeri kategorilerini al
     */
    public function eksikGaleriKategorileri(): array
    {
        $istatistikler = $this->galeriIstatistikleri();
        return $istatistikler['basarili'] && isset($istatistikler['eksik_kategoriler']) 
            ? $istatistikler['eksik_kategoriler'] 
            : [];
    }

    /**
     * Galeri için uygun kategorileri al
     */
    public function galeriKategorileri(): array
    {
        $galeriService = app(GaleriService::class);
        $sonuc = $galeriService->mulkTipiIcinKategorileriGetir(static::class);
        
        return $sonuc['basarili'] ? $sonuc['tum_kategoriler'] : [];
    }

    /**
     * Zorunlu galeri kategorilerini al
     */
    public function zorunluGaleriKategorileri(): array
    {
        $galeriService = app(GaleriService::class);
        $sonuc = $galeriService->mulkTipiIcinKategorileriGetir(static::class);
        
        return $sonuc['basarili'] ? $sonuc['zorunlu_kategoriler'] : [];
    }

    /**
     * Opsiyonel galeri kategorilerini al
     */
    public function opsiyonelGaleriKategorileri(): array
    {
        $galeriService = app(GaleriService::class);
        $sonuc = $galeriService->mulkTipiIcinKategorileriGetir(static::class);
        
        return $sonuc['basarili'] ? $sonuc['opsiyonel_kategoriler'] : [];
    }

    /**
     * Galeri organizasyon önerilerini al
     */
    public function galeriOrganizasyonOnerileri(): array
    {
        $galeriService = app(GaleriService::class);
        $sonuc = $galeriService->galeriOrganizasyonuOner(static::class, $this->id);
        
        return $sonuc['basarili'] ? $sonuc['oneriler'] : [];
    }

    /**
     * Galeri durumu badge'ini al
     */
    public function galeriDurumBadge(): array
    {
        if (!$this->galeriAktifMi()) {
            return [
                'text' => 'Galeri Yok',
                'color' => 'gray',
                'icon' => 'fas fa-ban'
            ];
        }

        $resimSayisi = $this->galeriResimSayisi();
        $istatistikler = $this->galeriIstatistikleri();
        
        if (!$istatistikler['basarili']) {
            return [
                'text' => 'Hata',
                'color' => 'red',
                'icon' => 'fas fa-exclamation-triangle'
            ];
        }

        if ($resimSayisi === 0) {
            return [
                'text' => 'Resim Yok',
                'color' => 'red',
                'icon' => 'fas fa-image'
            ];
        }

        if ($istatistikler['galeri_tamamlandi']) {
            return [
                'text' => 'Tamamlandı',
                'color' => 'green',
                'icon' => 'fas fa-check-circle'
            ];
        }

        if ($resimSayisi < $istatistikler['min_resim']) {
            return [
                'text' => 'Eksik',
                'color' => 'orange',
                'icon' => 'fas fa-exclamation-circle'
            ];
        }

        return [
            'text' => 'Devam Ediyor',
            'color' => 'blue',
            'icon' => 'fas fa-clock'
        ];
    }

    /**
     * Scope: Galeri tamamlanmış olanlar
     */
    public function scopeGaleriTamamlanmis($query)
    {
        return $query->whereHas('galeriResimleri', function ($q) {
            $q->where('aktif_mi', true);
        }, '>=', function ($query) {
            // Bu kısım daha karmaşık olacak, şimdilik basit bir kontrol
            return 3; // Minimum resim sayısı
        });
    }

    /**
     * Scope: Ana resmi olan
     */
    public function scopeAnaResmiOlan($query)
    {
        return $query->whereHas('anaResim');
    }

    /**
     * Scope: Galeri aktif olan
     */
    public function scopeGaleriAktif($query)
    {
        // Bu scope'u kullanmak için model seviyesinde kontrol gerekli
        return $query->whereHas('galeriResimleri');
    }
}