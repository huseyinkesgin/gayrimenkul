<?php

namespace App\Services;

use App\Models\Dokuman;
use App\Enums\DokumanTipi;
use App\Enums\MulkKategorisi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Kapsamlı Döküman Yönetim Servisi
 * 
 * Bu servis, gayrimenkul portföy sistemi için döküman yönetiminin
 * tüm gereksinimlerini karşılar:
 * - Döküman tiplerine göre upload kuralları (5.1, 5.2)
 * - Döküman versiyonlama sistemi (5.5, 6.5)
 * - Mülk tipine göre filtreleme (5.5)
 * - Soft delete ile arşivleme (5.4)
 */
class DokumanYonetimService
{
    private DokumanUploadService $uploadService;

    public function __construct(DokumanUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Mülk tipine göre uygun döküman tiplerini getir
     * Gereksinim 5.1, 5.2: İşyeri için AutoCAD, herhangi bir mülk için tapu
     */
    public function getMulkTipineGoreDokumanTipleri(string $mulkType): array
    {
        $dokumanTipleri = DokumanTipi::forMulkType($mulkType);
        
        return array_map(function ($tip) {
            return [
                'value' => $tip->value,
                'label' => $tip->label(),
                'description' => $tip->description(),
                'allowed_mime_types' => $tip->allowedMimeTypes(),
                'max_file_size' => $tip->maxFileSize(),
                'is_required' => $tip->isRequired(),
            ];
        }, $dokumanTipleri);
    }

    /**
     * Döküman yükle ve kaydet
     * Gereksinim 5.3: Döküman tipi, adı ve yükleme tarihi kaydedilecek
     */
    public function dokumanYukle(
        UploadedFile $file,
        string $documentableType,
        string $documentableId,
        DokumanTipi $dokumanTipi,
        array $additionalData = []
    ): array {
        DB::beginTransaction();
        
        try {
            // Upload işlemi
            $result = $this->uploadService->upload(
                $file,
                $documentableType,
                $documentableId,
                $dokumanTipi,
                $additionalData
            );

            if (!$result['success']) {
                DB::rollBack();
                return $result;
            }

            // Gereksinim 5.3: Döküman tipi, adı ve yükleme tarihi otomatik kaydedildi
            $dokuman = $result['dokuman'];
            
            // Mülk tipi kontrolü ve uyumluluk doğrulaması
            if (method_exists($documentableType, 'getMulkType')) {
                $mulkType = $documentableType::find($documentableId)?->getMulkType();
                if ($mulkType && !$this->isDokumanTipiUyumlu($dokumanTipi, $mulkType)) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'errors' => ["Bu döküman tipi ({$dokumanTipi->label()}) bu mülk tipi için uygun değil."]
                    ];
                }
            }

            DB::commit();
            
            return [
                'success' => true,
                'dokuman' => $dokuman,
                'message' => 'Döküman başarıyla yüklendi ve kaydedildi.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'errors' => ['Döküman yüklenirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Döküman versiyonu güncelle
     * Gereksinim 6.5: Harita dökümanı güncellendiğinde eski versiyon arşivlenecek
     */
    public function dokumanVersiyonuGuncelle(
        Dokuman $mevcutDokuman,
        UploadedFile $yeniDosya,
        array $additionalData = []
    ): array {
        DB::beginTransaction();
        
        try {
            // Eski versiyonu arşivle (soft delete değil, sadece aktif değil yap)
            $mevcutDokuman->update([
                'aktif_mi' => false,
                'guncelleyen_id' => Auth::id()
            ]);

            // Yeni versiyon oluştur
            $result = $this->uploadService->updateVersion(
                $mevcutDokuman,
                $yeniDosya,
                $additionalData
            );

            if (!$result['success']) {
                DB::rollBack();
                return $result;
            }

            DB::commit();
            
            return [
                'success' => true,
                'dokuman' => $result['dokuman'],
                'eski_versiyon' => $mevcutDokuman,
                'message' => 'Döküman versiyonu başarıyla güncellendi, eski versiyon arşivlendi.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'errors' => ['Versiyon güncellenirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Dökümanları mülk tipine göre filtrele
     * Gereksinim 5.5: Döküman listesi mülk tipine göre filtrelenebilecek
     */
    public function dokumanlariFiltrelemeMulkTipineGore(
        string $documentableType,
        string $documentableId,
        ?string $mulkType = null
    ): Collection {
        $query = Dokuman::where('documentable_type', $documentableType)
                        ->where('documentable_id', $documentableId)
                        ->where('aktif_mi', true)
                        ->with(['olusturan', 'guncelleyen']);

        // Mülk tipi belirtilmişse, o tipe uygun döküman tiplerini filtrele
        if ($mulkType) {
            $uygunTipler = DokumanTipi::forMulkType($mulkType);
            $tipValues = array_map(fn($tip) => $tip->value, $uygunTipler);
            $query->whereIn('dokuman_tipi', $tipValues);
        }

        return $query->orderBy('dokuman_tipi')
                    ->orderBy('olusturma_tarihi', 'desc')
                    ->get()
                    ->groupBy('dokuman_tipi');
    }

    /**
     * Döküman sil (soft delete)
     * Gereksinim 5.4: Döküman silindiğinde soft delete ile arşivlenecek
     */
    public function dokumanSil(Dokuman $dokuman, string $silmeNedeni = null): array
    {
        DB::beginTransaction();
        
        try {
            // Soft delete uygula
            $dokuman->update([
                'aktif_mi' => false,
                'guncelleyen_id' => Auth::id(),
                'metadata' => array_merge($dokuman->metadata ?? [], [
                    'silme_tarihi' => now()->toISOString(),
                    'silen_kullanici' => Auth::id(),
                    'silme_nedeni' => $silmeNedeni
                ])
            ]);
            
            $dokuman->delete(); // Laravel soft delete

            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Döküman başarıyla arşivlendi (soft delete).'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'errors' => ['Döküman silinirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Arşivlenmiş dökümanı geri yükle
     */
    public function dokumanGeriYukle(Dokuman $dokuman): array
    {
        DB::beginTransaction();
        
        try {
            $dokuman->restore();
            $dokuman->update([
                'aktif_mi' => true,
                'guncelleyen_id' => Auth::id(),
                'metadata' => array_merge($dokuman->metadata ?? [], [
                    'geri_yukleme_tarihi' => now()->toISOString(),
                    'geri_yukleyen_kullanici' => Auth::id()
                ])
            ]);

            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Döküman başarıyla geri yüklendi.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'errors' => ['Döküman geri yüklenirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Döküman tipinin mülk tipi ile uyumlu olup olmadığını kontrol et
     */
    private function isDokumanTipiUyumlu(DokumanTipi $dokumanTipi, string $mulkType): bool
    {
        $uygunTipler = DokumanTipi::forMulkType($mulkType);
        return in_array($dokumanTipi, $uygunTipler);
    }

    /**
     * Döküman istatistikleri - mülk tipine göre
     */
    public function getDokumanIstatistikleri(
        string $documentableType,
        string $documentableId
    ): array {
        $baseQuery = Dokuman::where('documentable_type', $documentableType)
                           ->where('documentable_id', $documentableId);

        return [
            'toplam_dokuman' => $baseQuery->where('aktif_mi', true)->count(),
            'arsivlenen_dokuman' => $baseQuery->where('aktif_mi', false)->count(),
            'silinen_dokuman' => $baseQuery->onlyTrashed()->count(),
            'toplam_boyut' => $baseQuery->where('aktif_mi', true)->sum('dosya_boyutu'),
            'tip_bazinda_dagilim' => $baseQuery->where('aktif_mi', true)
                                             ->selectRaw('dokuman_tipi, COUNT(*) as adet, SUM(dosya_boyutu) as toplam_boyut')
                                             ->groupBy('dokuman_tipi')
                                             ->get()
                                             ->mapWithKeys(function ($item) {
                                                 return [$item->dokuman_tipi => [
                                                     'adet' => $item->adet,
                                                     'toplam_boyut' => $item->toplam_boyut,
                                                     'label' => DokumanTipi::from($item->dokuman_tipi)->label()
                                                 ]];
                                             }),
            'son_yuklenenler' => $baseQuery->where('aktif_mi', true)
                                          ->latest('olusturma_tarihi')
                                          ->limit(5)
                                          ->get(['id', 'baslik', 'dokuman_tipi', 'olusturma_tarihi'])
        ];
    }

    /**
     * Zorunlu dökümanları kontrol et
     */
    public function getEksikZorunluDokumanlar(
        string $documentableType,
        string $documentableId,
        string $mulkType
    ): array {
        $uygunTipler = DokumanTipi::forMulkType($mulkType);
        $zorunluTipler = array_filter($uygunTipler, fn($tip) => $tip->isRequired());
        
        $mevcutTipler = Dokuman::where('documentable_type', $documentableType)
                              ->where('documentable_id', $documentableId)
                              ->where('aktif_mi', true)
                              ->pluck('dokuman_tipi')
                              ->map(fn($tip) => DokumanTipi::from($tip))
                              ->toArray();

        $eksikTipler = array_diff($zorunluTipler, $mevcutTipler);
        
        return array_map(function ($tip) {
            return [
                'tip' => $tip->value,
                'label' => $tip->label(),
                'description' => $tip->description()
            ];
        }, $eksikTipler);
    }

    /**
     * Toplu döküman yükleme
     */
    public function topluDokumanYukle(
        array $files,
        string $documentableType,
        string $documentableId,
        DokumanTipi $dokumanTipi,
        array $additionalData = []
    ): array {
        return $this->uploadService->uploadMultiple(
            $files,
            $documentableType,
            $documentableId,
            $dokumanTipi,
            $additionalData
        );
    }

    /**
     * Döküman arama - başlık, açıklama ve dosya adında arama
     */
    public function dokumanAra(
        string $searchTerm,
        ?string $documentableType = null,
        ?string $documentableId = null,
        ?DokumanTipi $dokumanTipi = null
    ): Collection {
        $query = Dokuman::where('aktif_mi', true);

        // Full-text search (MySQL için)
        if (config('database.default') !== 'sqlite') {
            $query->whereRaw("MATCH(baslik, aciklama, dosya_adi) AGAINST(? IN BOOLEAN MODE)", [$searchTerm]);
        } else {
            // SQLite için basit LIKE arama
            $query->where(function ($q) use ($searchTerm) {
                $q->where('baslik', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('aciklama', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('dosya_adi', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($documentableType) {
            $query->where('documentable_type', $documentableType);
        }

        if ($documentableId) {
            $query->where('documentable_id', $documentableId);
        }

        if ($dokumanTipi) {
            $query->where('dokuman_tipi', $dokumanTipi);
        }

        return $query->with(['olusturan', 'documentable'])
                    ->orderBy('olusturma_tarihi', 'desc')
                    ->get();
    }
}