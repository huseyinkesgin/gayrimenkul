<?php

namespace App\Services;

use App\Models\Resim;
use App\Models\Mulk\BaseMulk;
use App\Enums\ResimKategorisi;
use App\Enums\MulkKategorisi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GaleriService
{
    /**
     * Mülk tipine göre izin verilen resim kategorilerini getir
     */
    public function getAllowedCategoriesForProperty(string $propertyType): array
    {
        return match ($propertyType) {
            'arsa' => [
                ResimKategorisi::MANZARA,
                ResimKategorisi::UYDU,
                ResimKategorisi::OZNITELIK,
                ResimKategorisi::BUYUKSEHIR,
                ResimKategorisi::EGIM,
                ResimKategorisi::EIMAR,
            ],
            'isyeri' => [
                ResimKategorisi::KAPAK_RESMI,
                ResimKategorisi::GALERI,
                ResimKategorisi::IC_MEKAN,
                ResimKategorisi::DIS_MEKAN,
                ResimKategorisi::CEPHE,
                ResimKategorisi::DETAY,
                ResimKategorisi::PLAN,
                ResimKategorisi::UYDU,
                ResimKategorisi::OZNITELIK,
                ResimKategorisi::BUYUKSEHIR,
            ],
            'konut' => [
                ResimKategorisi::KAPAK_RESMI,
                ResimKategorisi::GALERI,
                ResimKategorisi::IC_MEKAN,
                ResimKategorisi::DIS_MEKAN,
                ResimKategorisi::CEPHE,
                ResimKategorisi::MANZARA,
                ResimKategorisi::DETAY,
                ResimKategorisi::PLAN,
            ],
            'turistik_tesis' => [
                ResimKategorisi::KAPAK_RESMI,
                ResimKategorisi::GALERI,
                ResimKategorisi::IC_MEKAN,
                ResimKategorisi::DIS_MEKAN,
                ResimKategorisi::CEPHE,
                ResimKategorisi::MANZARA,
                ResimKategorisi::DETAY,
                ResimKategorisi::PLAN,
            ],
            default => []
        };
    }

    /**
     * Mülk için galeri kurallarını kontrol et
     */
    public function validateGalleryRules(BaseMulk $mulk, ResimKategorisi $kategori): array
    {
        $errors = [];
        $propertyType = $mulk->getMulkType();
        $allowedCategories = $this->getAllowedCategoriesForProperty($propertyType);

        // Kategori kontrolü
        if (!in_array($kategori, $allowedCategories)) {
            $errors[] = "Bu mülk tipi için {$kategori->label()} kategorisi desteklenmiyor.";
        }

        // Kategori bazlı özel kurallar
        switch ($kategori) {
            case ResimKategorisi::KAPAK_RESMI:
                if ($this->hasKapakResmi($mulk)) {
                    $errors[] = 'Bu mülk için zaten bir kapak resmi mevcut.';
                }
                break;

            case ResimKategorisi::GALERI:
                $galeriCount = $this->getGaleriCount($mulk);
                if ($galeriCount >= $this->getMaxGaleriCount($propertyType)) {
                    $maxCount = $this->getMaxGaleriCount($propertyType);
                    $errors[] = "Bu mülk tipi için maksimum {$maxCount} galeri resmi yüklenebilir.";
                }
                break;

            case ResimKategorisi::PLAN:
                $planCount = $this->getPlanCount($mulk);
                if ($planCount >= $this->getMaxPlanCount($propertyType)) {
                    $maxCount = $this->getMaxPlanCount($propertyType);
                    $errors[] = "Bu mülk tipi için maksimum {$maxCount} plan resmi yüklenebilir.";
                }
                break;
        }

        return $errors;
    }

    /**
     * Mülk için galeri organizasyonu oluştur
     */
    public function organizeGallery(BaseMulk $mulk): array
    {
        $propertyType = $mulk->getMulkType();
        $allowedCategories = $this->getAllowedCategoriesForProperty($propertyType);
        
        $gallery = [];

        foreach ($allowedCategories as $kategori) {
            $resimler = $mulk->resimler()
                ->where('kategori', $kategori->value)
                ->orderBy('siralama')
                ->orderBy('olusturma_tarihi', 'desc')
                ->get();

            if ($resimler->isNotEmpty()) {
                $gallery[$kategori->value] = [
                    'kategori' => $kategori,
                    'label' => $kategori->label(),
                    'description' => $kategori->description(),
                    'color' => $kategori->color(),
                    'count' => $resimler->count(),
                    'resimler' => $resimler,
                    'sort_priority' => $kategori->sortPriority(),
                ];
            }
        }

        // Sıralama önceliğine göre sırala
        uasort($gallery, function ($a, $b) {
            return $a['sort_priority'] <=> $b['sort_priority'];
        });

        return $gallery;
    }

    /**
     * Galeri sıralama güncelle
     */
    public function updateGalleryOrder(BaseMulk $mulk, array $imageOrders): bool
    {
        try {
            DB::beginTransaction();

            foreach ($imageOrders as $imageId => $order) {
                $resim = $mulk->resimler()->where('id', $imageId)->first();
                if ($resim) {
                    $resim->update(['siralama' => $order]);
                }
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Kategori içinde sıralama güncelle
     */
    public function updateCategoryOrder(BaseMulk $mulk, ResimKategorisi $kategori, array $imageIds): bool
    {
        try {
            DB::beginTransaction();

            foreach ($imageIds as $index => $imageId) {
                $resim = $mulk->resimler()
                    ->where('id', $imageId)
                    ->where('kategori', $kategori->value)
                    ->first();
                
                if ($resim) {
                    $newOrder = ($kategori->sortPriority() * 1000) + $index + 1;
                    $resim->update(['siralama' => $newOrder]);
                }
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Galeri istatistikleri
     */
    public function getGalleryStatistics(BaseMulk $mulk): array
    {
        $propertyType = $mulk->getMulkType();
        $allowedCategories = $this->getAllowedCategoriesForProperty($propertyType);
        
        $stats = [
            'total_images' => 0,
            'total_size' => 0,
            'categories' => [],
            'completion_percentage' => 0,
            'missing_categories' => [],
            'recommendations' => [],
        ];

        $totalImages = 0;
        $totalSize = 0;
        $requiredCategories = $this->getRequiredCategories($propertyType);
        $missingRequired = [];

        foreach ($allowedCategories as $kategori) {
            $count = $mulk->resimler()
                ->where('kategori', $kategori->value)
                ->count();

            $size = $mulk->resimler()
                ->where('kategori', $kategori->value)
                ->sum('dosya_boyutu');

            $stats['categories'][$kategori->value] = [
                'label' => $kategori->label(),
                'count' => $count,
                'size' => $size,
                'formatted_size' => $this->formatBytes($size),
                'max_allowed' => $this->getMaxCountForCategory($kategori, $propertyType),
                'is_required' => in_array($kategori, $requiredCategories),
                'is_complete' => $count > 0,
            ];

            $totalImages += $count;
            $totalSize += $size;

            // Zorunlu kategori eksik mi?
            if (in_array($kategori, $requiredCategories) && $count === 0) {
                $missingRequired[] = $kategori->label();
            }
        }

        $stats['total_images'] = $totalImages;
        $stats['total_size'] = $totalSize;
        $stats['formatted_total_size'] = $this->formatBytes($totalSize);
        $stats['missing_categories'] = $missingRequired;

        // Tamamlanma yüzdesi
        $completedRequired = count($requiredCategories) - count($missingRequired);
        $stats['completion_percentage'] = count($requiredCategories) > 0 
            ? round(($completedRequired / count($requiredCategories)) * 100, 1)
            : 100;

        // Öneriler
        $stats['recommendations'] = $this->generateRecommendations($mulk, $stats);

        return $stats;
    }

    /**
     * Galeri önizleme oluştur
     */
    public function generateGalleryPreview(BaseMulk $mulk, int $limit = 6): array
    {
        $preview = [];
        
        // Önce kapak resmi
        $kapakResmi = $mulk->kapakResmi;
        if ($kapakResmi) {
            $preview[] = [
                'resim' => $kapakResmi,
                'is_cover' => true,
                'category_label' => 'Kapak Resmi',
            ];
        }

        // Sonra diğer kategorilerden
        $categories = [
            ResimKategorisi::GALERI,
            ResimKategorisi::IC_MEKAN,
            ResimKategorisi::DIS_MEKAN,
            ResimKategorisi::CEPHE,
            ResimKategorisi::MANZARA,
            ResimKategorisi::DETAY,
        ];

        $remaining = $limit - count($preview);
        
        foreach ($categories as $kategori) {
            if ($remaining <= 0) break;

            $resimler = $mulk->resimler()
                ->where('kategori', $kategori->value)
                ->limit($remaining)
                ->get();

            foreach ($resimler as $resim) {
                if ($remaining <= 0) break;
                
                $preview[] = [
                    'resim' => $resim,
                    'is_cover' => false,
                    'category_label' => $kategori->label(),
                ];
                
                $remaining--;
            }
        }

        return $preview;
    }

    /**
     * Galeri arama ve filtreleme
     */
    public function searchGallery(BaseMulk $mulk, array $filters = []): Collection
    {
        $query = $mulk->resimler();

        // Kategori filtresi
        if (!empty($filters['categories'])) {
            $query->whereIn('kategori', $filters['categories']);
        }

        // Tarih aralığı filtresi
        if (!empty($filters['date_from'])) {
            $query->where('cekim_tarihi', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('cekim_tarihi', '<=', $filters['date_to']);
        }

        // Boyut filtresi
        if (!empty($filters['min_width'])) {
            $query->where('genislik', '>=', $filters['min_width']);
        }

        if (!empty($filters['min_height'])) {
            $query->where('yukseklik', '>=', $filters['min_height']);
        }

        // Dosya boyutu filtresi
        if (!empty($filters['min_file_size'])) {
            $query->where('dosya_boyutu', '>=', $filters['min_file_size']);
        }

        if (!empty($filters['max_file_size'])) {
            $query->where('dosya_boyutu', '<=', $filters['max_file_size']);
        }

        // Onay durumu filtresi
        if (!empty($filters['approval_status'])) {
            $query->where('onay_durumu', $filters['approval_status']);
        }

        // Etiket filtresi
        if (!empty($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('etiketler', $tag);
            }
        }

        // Sıralama
        $sortBy = $filters['sort_by'] ?? 'siralama';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        
        $query->orderBy($sortBy, $sortDirection);

        return $query->get();
    }

    /**
     * Toplu galeri işlemleri
     */
    public function bulkGalleryOperation(BaseMulk $mulk, string $operation, array $imageIds, array $data = []): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        try {
            DB::beginTransaction();

            foreach ($imageIds as $imageId) {
                $resim = $mulk->resimler()->where('id', $imageId)->first();
                
                if (!$resim) {
                    $results[] = [
                        'image_id' => $imageId,
                        'success' => false,
                        'message' => 'Resim bulunamadı'
                    ];
                    $errorCount++;
                    continue;
                }

                $result = $this->performSingleOperation($resim, $operation, $data);
                $results[] = array_merge($result, ['image_id' => $imageId]);
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Toplu işlem hatası: ' . $e->getMessage()
            ];
        }

        return [
            'success' => $errorCount === 0,
            'results' => $results,
            'summary' => [
                'total' => count($imageIds),
                'success' => $successCount,
                'error' => $errorCount,
            ]
        ];
    }

    /**
     * Galeri yedekleme
     */
    public function backupGallery(BaseMulk $mulk): array
    {
        try {
            $resimler = $mulk->resimler;
            $backupData = [];

            foreach ($resimler as $resim) {
                $backupData[] = [
                    'id' => $resim->id,
                    'kategori' => $resim->kategori,
                    'baslik' => $resim->baslik,
                    'aciklama' => $resim->aciklama,
                    'dosya_adi' => $resim->dosya_adi,
                    'url' => $resim->url,
                    'siralama' => $resim->siralama,
                    'etiketler' => $resim->etiketler,
                    'metadata' => [
                        'boyutlar' => $resim->dimensions,
                        'dosya_boyutu' => $resim->dosya_boyutu,
                        'mime_type' => $resim->mime_type,
                        'cekim_tarihi' => $resim->cekim_tarihi,
                        'exif_data' => $resim->exif_data,
                    ]
                ];
            }

            $backupPath = "gallery_backups/mulk_{$mulk->id}_" . now()->format('Y-m-d_H-i-s') . '.json';
            \Storage::disk('local')->put($backupPath, json_encode($backupData, JSON_PRETTY_PRINT));

            return [
                'success' => true,
                'backup_path' => $backupPath,
                'image_count' => count($backupData),
                'message' => 'Galeri başarıyla yedeklendi'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Yedekleme hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Helper metodlar
     */
    private function hasKapakResmi(BaseMulk $mulk): bool
    {
        return $mulk->kapakResmi !== null;
    }

    private function getGaleriCount(BaseMulk $mulk): int
    {
        return $mulk->galeriResimleri()->count();
    }

    private function getPlanCount(BaseMulk $mulk): int
    {
        return $mulk->planResimleri()->count();
    }

    private function getMaxGaleriCount(string $propertyType): int
    {
        return match ($propertyType) {
            'arsa' => 5,
            'isyeri' => 20,
            'konut' => 15,
            'turistik_tesis' => 30,
            default => 10
        };
    }

    private function getMaxPlanCount(string $propertyType): int
    {
        return match ($propertyType) {
            'arsa' => 0,
            'isyeri' => 5,
            'konut' => 3,
            'turistik_tesis' => 10,
            default => 1
        };
    }

    private function getMaxCountForCategory(ResimKategorisi $kategori, string $propertyType): int
    {
        return match ($kategori) {
            ResimKategorisi::KAPAK_RESMI => 1,
            ResimKategorisi::GALERI => $this->getMaxGaleriCount($propertyType),
            ResimKategorisi::PLAN => $this->getMaxPlanCount($propertyType),
            ResimKategorisi::IC_MEKAN => 10,
            ResimKategorisi::DIS_MEKAN => 8,
            ResimKategorisi::CEPHE => 5,
            ResimKategorisi::MANZARA => 5,
            ResimKategorisi::DETAY => 15,
            default => 3
        };
    }

    private function getRequiredCategories(string $propertyType): array
    {
        return match ($propertyType) {
            'arsa' => [ResimKategorisi::MANZARA],
            'isyeri' => [ResimKategorisi::KAPAK_RESMI, ResimKategorisi::DIS_MEKAN],
            'konut' => [ResimKategorisi::KAPAK_RESMI, ResimKategorisi::IC_MEKAN],
            'turistik_tesis' => [ResimKategorisi::KAPAK_RESMI, ResimKategorisi::IC_MEKAN, ResimKategorisi::DIS_MEKAN],
            default => []
        };
    }

    private function generateRecommendations(BaseMulk $mulk, array $stats): array
    {
        $recommendations = [];

        // Eksik zorunlu kategoriler
        if (!empty($stats['missing_categories'])) {
            $recommendations[] = [
                'type' => 'missing_required',
                'priority' => 'high',
                'message' => 'Eksik zorunlu kategoriler: ' . implode(', ', $stats['missing_categories'])
            ];
        }

        // Kapak resmi eksik
        if (!$this->hasKapakResmi($mulk)) {
            $recommendations[] = [
                'type' => 'missing_cover',
                'priority' => 'high',
                'message' => 'Kapak resmi eklemeniz önerilir'
            ];
        }

        // Az resim var
        if ($stats['total_images'] < 3) {
            $recommendations[] = [
                'type' => 'low_image_count',
                'priority' => 'medium',
                'message' => 'Daha fazla resim eklemeniz önerilir (minimum 3 resim)'
            ];
        }

        // Büyük dosya boyutları
        if ($stats['total_size'] > 50 * 1024 * 1024) { // 50MB
            $recommendations[] = [
                'type' => 'large_file_size',
                'priority' => 'low',
                'message' => 'Dosya boyutlarını optimize etmeniz önerilir'
            ];
        }

        return $recommendations;
    }

    private function performSingleOperation(Resim $resim, string $operation, array $data): array
    {
        try {
            switch ($operation) {
                case 'update_category':
                    $resim->update(['kategori' => $data['category']]);
                    return ['success' => true, 'message' => 'Kategori güncellendi'];

                case 'update_title':
                    $resim->update(['baslik' => $data['title']]);
                    return ['success' => true, 'message' => 'Başlık güncellendi'];

                case 'update_description':
                    $resim->update(['aciklama' => $data['description']]);
                    return ['success' => true, 'message' => 'Açıklama güncellendi'];

                case 'add_tags':
                    $currentTags = $resim->etiketler ?? [];
                    $newTags = array_unique(array_merge($currentTags, $data['tags']));
                    $resim->update(['etiketler' => $newTags]);
                    return ['success' => true, 'message' => 'Etiketler eklendi'];

                case 'remove_tags':
                    $currentTags = $resim->etiketler ?? [];
                    $newTags = array_diff($currentTags, $data['tags']);
                    $resim->update(['etiketler' => array_values($newTags)]);
                    return ['success' => true, 'message' => 'Etiketler kaldırıldı'];

                case 'approve':
                    $resim->approve(auth()->id());
                    return ['success' => true, 'message' => 'Resim onaylandı'];

                case 'reject':
                    $resim->reject(auth()->id(), $data['reason'] ?? null);
                    return ['success' => true, 'message' => 'Resim reddedildi'];

                case 'deactivate':
                    $resim->update(['aktif_mi' => false]);
                    return ['success' => true, 'message' => 'Resim pasif hale getirildi'];

                case 'activate':
                    $resim->update(['aktif_mi' => true]);
                    return ['success' => true, 'message' => 'Resim aktif hale getirildi'];

                default:
                    return ['success' => false, 'message' => 'Geçersiz işlem'];
            }

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'İşlem hatası: ' . $e->getMessage()];
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}