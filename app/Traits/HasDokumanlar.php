<?php

namespace App\Traits;

use App\Models\Dokuman;
use App\Enums\DokumanTipi;
use App\Services\DokumanYonetimService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * HasDokumanlar Trait
 * 
 * Bu trait, döküman sahibi olabilen modeller için
 * ortak döküman yönetimi metodları sağlar.
 */
trait HasDokumanlar
{
    /**
     * Polymorphic döküman ilişkisi
     */
    public function dokumanlar(): MorphMany
    {
        return $this->morphMany(Dokuman::class, 'documentable');
    }

    /**
     * Aktif dökümanlar
     */
    public function aktifDokumanlar(): MorphMany
    {
        return $this->dokumanlar()->where('aktif_mi', true);
    }

    /**
     * Arşivlenmiş dökümanlar
     */
    public function arsivlenmisD okumanlar(): MorphMany
    {
        return $this->dokumanlar()->where('aktif_mi', false);
    }

    /**
     * Belirli tipteki dökümanlar
     */
    public function getDokumanlarByType(DokumanTipi $tip): Collection
    {
        return $this->aktifDokumanlar()
                   ->where('dokuman_tipi', $tip)
                   ->orderBy('olusturma_tarihi', 'desc')
                   ->get();
    }

    /**
     * Tapu dökümanları
     */
    public function getTapuDokumanlari(): Collection
    {
        return $this->getDokumanlarByType(DokumanTipi::TAPU);
    }

    /**
     * AutoCAD dosyaları
     */
    public function getAutoCADDosyalari(): Collection
    {
        return $this->getDokumanlarByType(DokumanTipi::AUTOCAD);
    }

    /**
     * Proje resimleri
     */
    public function getProjeResimleri(): Collection
    {
        return $this->getDokumanlarByType(DokumanTipi::PROJE_RESMI);
    }

    /**
     * Ruhsat dökümanları
     */
    public function getRuhsatDokumanlari(): Collection
    {
        return $this->getDokumanlarByType(DokumanTipi::RUHSAT);
    }

    /**
     * Bu model için uygun döküman tiplerini getir
     * Alt sınıflar bu metodu override edebilir
     */
    public function getUygunDokumanTipleri(): array
    {
        // Varsayılan olarak tüm tipler uygun
        return DokumanTipi::cases();
    }

    /**
     * Mülk tipi döndür (mülk modelleri için)
     * Bu metod mülk modellerinde override edilmelidir
     */
    public function getMulkType(): ?string
    {
        return null;
    }

    /**
     * Döküman istatistikleri
     */
    public function getDokumanIstatistikleri(): array
    {
        $service = app(DokumanYonetimService::class);
        return $service->getDokumanIstatistikleri(
            static::class,
            $this->id
        );
    }

    /**
     * Eksik zorunlu dökümanları getir
     */
    public function getEksikZorunluDokumanlar(): array
    {
        $mulkType = $this->getMulkType();
        if (!$mulkType) {
            return [];
        }

        $service = app(DokumanYonetimService::class);
        return $service->getEksikZorunluDokumanlar(
            static::class,
            $this->id,
            $mulkType
        );
    }

    /**
     * Döküman var mı kontrol et
     */
    public function hasDokuman(DokumanTipi $tip): bool
    {
        return $this->aktifDokumanlar()
                   ->where('dokuman_tipi', $tip)
                   ->exists();
    }

    /**
     * Zorunlu dökümanlar eksik mi?
     */
    public function hasEksikZorunluDokumanlar(): bool
    {
        return count($this->getEksikZorunluDokumanlar()) > 0;
    }

    /**
     * En son yüklenen döküman
     */
    public function getEnSonYuklenenDokuman(): ?Dokuman
    {
        return $this->aktifDokumanlar()
                   ->latest('olusturma_tarihi')
                   ->first();
    }

    /**
     * Toplam döküman boyutu
     */
    public function getToplamDokumanBoyutu(): int
    {
        return $this->aktifDokumanlar()->sum('dosya_boyutu');
    }

    /**
     * Döküman sayısı
     */
    public function getDokumanSayisi(): int
    {
        return $this->aktifDokumanlar()->count();
    }

    /**
     * Döküman tiplerinin dağılımı
     */
    public function getDokumanTipDagilimi(): Collection
    {
        return $this->aktifDokumanlar()
                   ->selectRaw('dokuman_tipi, COUNT(*) as adet')
                   ->groupBy('dokuman_tipi')
                   ->get()
                   ->mapWithKeys(function ($item) {
                       $tip = DokumanTipi::from($item->dokuman_tipi);
                       return [$tip->value => [
                           'tip' => $tip,
                           'label' => $tip->label(),
                           'adet' => $item->adet
                       ]];
                   });
    }

    /**
     * Döküman arama
     */
    public function searchDokumanlar(string $searchTerm): Collection
    {
        $service = app(DokumanYonetimService::class);
        return $service->dokumanAra(
            $searchTerm,
            static::class,
            $this->id
        );
    }

    /**
     * Model silindiğinde dökümanları da sil
     */
    protected static function bootHasDokumanlar()
    {
        static::deleting(function ($model) {
            // Soft delete kullanıyorsa dökümanları da soft delete yap
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                $model->dokumanlar()->update(['aktif_mi' => false]);
                $model->dokumanlar()->delete();
            } else {
                // Hard delete durumunda dökümanları tamamen sil
                $model->dokumanlar()->forceDelete();
            }
        });

        static::restoring(function ($model) {
            // Model geri yüklendiğinde dökümanları da geri yükle
            $model->dokumanlar()->restore();
            $model->dokumanlar()->update(['aktif_mi' => true]);
        });
    }
}