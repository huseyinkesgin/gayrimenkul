<?php

namespace App\Services;

use App\Models\Resim;
use App\Enums\ResimKategorisi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;

class ResimService
{
    /**
     * Resim yükle ve işle
     */
    public function uploadAndProcess(
        UploadedFile $file,
        string $imageableType,
        string $imageableId,
        ResimKategorisi $kategori,
        ?string $baslik = null,
        ?string $aciklama = null,
        ?array $etiketler = null,
        ?int $userId = null
    ): Resim {
        // Dosya validasyonu
        $this->validateFile($file, $kategori);

        // Dosya adı oluştur
        $fileName = $this->generateFileName($file, $kategori);
        
        // Dosya yolu oluştur
        $path = $this->generatePath($kategori, $imageableType);
        $fullPath = $path . '/' . $fileName;

        // Dosyayı kaydet
        $file->storeAs($path, $fileName, 'public');

        // Resim kaydını oluştur
        $resim = Resim::create([
            'url' => $fullPath,
            'imageable_id' => $imageableId,
            'imageable_type' => $imageableType,
            'kategori' => $kategori,
            'baslik' => $baslik,
            'aciklama' => $aciklama,
            'dosya_adi' => $fileName,
            'orijinal_dosya_adi' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'dosya_boyutu' => $file->getSize(),
            'etiketler' => $etiketler,
            'yükleyen_id' => $userId,
            'onay_durumu' => $kategori->requiresApproval() ? 'beklemede' : 'onaylandı',
            'upload_ip' => request()->ip(),
            'upload_user_agent' => request()->userAgent(),
            'aktif_mi' => true,
        ]);

        // Resmi işle
        $this->processImageAsync($resim);

        return $resim;
    }

    /**
     * Dosya validasyonu
     */
    protected function validateFile(UploadedFile $file, ResimKategorisi $kategori): void
    {
        // Dosya boyutu kontrolü
        $maxSizeInBytes = $kategori->maxFileSize() * 1048576;
        if ($file->getSize() > $maxSizeInBytes) {
            throw new \InvalidArgumentException(
                "Dosya boyutu {$kategori->maxFileSize()}MB'dan büyük olamaz."
            );
        }

        // MIME type kontrolü
        if (!in_array($file->getMimeType(), $kategori->allowedMimeTypes())) {
            throw new \InvalidArgumentException(
                "Bu dosya türü {$kategori->label()} kategorisi için desteklenmiyor."
            );
        }

        // Dosya bütünlüğü kontrolü
        if (!$this->isValidImageFile($file)) {
            throw new \InvalidArgumentException("Geçersiz resim dosyası.");
        }
    }

    /**
     * Geçerli resim dosyası kontrolü
     */
    protected function isValidImageFile(UploadedFile $file): bool
    {
        try {
            $image = Image::make($file->path());
            return $image->width() > 0 && $image->height() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Dosya adı oluştur
     */
    protected function generateFileName(UploadedFile $file, ResimKategorisi $kategori): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);
        $categoryPrefix = strtoupper(substr($kategori->value, 0, 3));
        
        return "{$categoryPrefix}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Dosya yolu oluştur
     */
    protected function generatePath(ResimKategorisi $kategori, string $imageableType): string
    {
        $year = now()->year;
        $month = now()->format('m');
        
        return "images/{$kategori->value}/{$imageableType}/{$year}/{$month}";
    }

    /**
     * Resmi asenkron olarak işle
     */
    protected function processImageAsync(Resim $resim): void
    {
        // Queue job olarak çalıştırılabilir
        dispatch(function () use ($resim) {
            $resim->processImage();
        })->afterResponse();
    }

    /**
     * Toplu resim yükleme
     */
    public function uploadMultiple(
        array $files,
        string $imageableType,
        string $imageableId,
        ResimKategorisi $kategori,
        ?array $metadata = null,
        ?int $userId = null
    ): array {
        $uploadedImages = [];
        
        foreach ($files as $index => $file) {
            $baslik = $metadata['baslik'][$index] ?? null;
            $aciklama = $metadata['aciklama'][$index] ?? null;
            $etiketler = $metadata['etiketler'][$index] ?? null;
            
            try {
                $resim = $this->uploadAndProcess(
                    $file,
                    $imageableType,
                    $imageableId,
                    $kategori,
                    $baslik,
                    $aciklama,
                    $etiketler,
                    $userId
                );
                
                $uploadedImages[] = $resim;
            } catch (\Exception $e) {
                // Hata logla ama diğer dosyaları yüklemeye devam et
                \Log::error("Resim yükleme hatası: " . $e->getMessage(), [
                    'file' => $file->getClientOriginalName(),
                    'kategori' => $kategori->value,
                ]);
            }
        }
        
        return $uploadedImages;
    }

    /**
     * Resim güncelle
     */
    public function updateImage(
        Resim $resim,
        ?string $baslik = null,
        ?string $aciklama = null,
        ?array $etiketler = null,
        ?string $altText = null,
        ?string $copyrightBilgisi = null
    ): Resim {
        $updateData = array_filter([
            'baslik' => $baslik,
            'aciklama' => $aciklama,
            'etiketler' => $etiketler,
            'alt_text' => $altText,
            'copyright_bilgisi' => $copyrightBilgisi,
        ], fn($value) => $value !== null);

        $resim->update($updateData);
        
        return $resim;
    }

    /**
     * Resim sil (soft delete)
     */
    public function deleteImage(Resim $resim, bool $permanent = false): bool
    {
        if ($permanent) {
            // Fiziksel dosyaları sil
            $this->deletePhysicalFiles($resim);
            
            // Veritabanından sil
            return $resim->forceDelete();
        } else {
            // Soft delete
            return $resim->delete();
        }
    }

    /**
     * Fiziksel dosyaları sil
     */
    protected function deletePhysicalFiles(Resim $resim): void
    {
        $filesToDelete = [
            $resim->url,
            $resim->thumbnail_url,
            $resim->medium_url,
            $resim->large_url,
        ];

        foreach ($filesToDelete as $file) {
            if ($file && Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
            }
        }
    }

    /**
     * Duplicate resim kontrolü
     */
    public function checkForDuplicates(string $hash): array
    {
        return Resim::findDuplicates($hash)->toArray();
    }

    /**
     * Resim sıralama güncelle
     */
    public function updateSorting(array $imageIds): void
    {
        foreach ($imageIds as $index => $imageId) {
            Resim::where('id', $imageId)->update(['siralama' => $index + 1]);
        }
    }

    /**
     * Toplu onay işlemi
     */
    public function bulkApprove(array $imageIds, int $userId): int
    {
        return Resim::whereIn('id', $imageIds)
            ->onayBekleyen()
            ->update([
                'onay_durumu' => 'onaylandı',
                'onaylayan_id' => $userId,
                'onay_tarihi' => now(),
            ]);
    }

    /**
     * Toplu reddetme işlemi
     */
    public function bulkReject(array $imageIds, int $userId, ?string $reason = null): int
    {
        return Resim::whereIn('id', $imageIds)
            ->onayBekleyen()
            ->update([
                'onay_durumu' => 'reddedildi',
                'onaylayan_id' => $userId,
                'onay_tarihi' => now(),
                'processing_error' => $reason,
            ]);
    }

    /**
     * Resim istatistikleri
     */
    public function getImageStats(string $imageableType, string $imageableId): array
    {
        $query = Resim::where('imageable_type', $imageableType)
            ->where('imageable_id', $imageableId);

        return [
            'toplam' => $query->count(),
            'aktif' => $query->aktif()->count(),
            'onaylanmış' => $query->onaylanmis()->count(),
            'beklemede' => $query->onayBekleyen()->count(),
            'reddedilmiş' => $query->reddedilmis()->count(),
            'işlenmiş' => $query->islenmis()->count(),
            'toplam_boyut' => $query->sum('dosya_boyutu'),
            'toplam_görüntülenme' => $query->sum('görüntülenme_sayisi'),
            'kategoriler' => $query->selectRaw('kategori, COUNT(*) as count')
                ->groupBy('kategori')
                ->pluck('count', 'kategori')
                ->toArray(),
        ];
    }

    /**
     * Resim optimizasyonu
     */
    public function optimizeImage(Resim $resim): bool
    {
        if (!Storage::disk('public')->exists($resim->url)) {
            return false;
        }

        try {
            $image = Image::make(Storage::disk('public')->path($resim->url));
            
            // Kategori bazlı optimizasyon
            $quality = $resim->kategori->qualitySetting();
            $dimensions = $resim->kategori->recommendedDimensions();
            
            // Boyut optimizasyonu
            if ($image->width() > $dimensions['width'] || $image->height() > $dimensions['height']) {
                $image->resize($dimensions['width'], $dimensions['height'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Kalite optimizasyonu
            $optimizedImage = $image->encode('jpg', $quality);
            
            // Dosyayı güncelle
            Storage::disk('public')->put($resim->url, $optimizedImage);
            
            // Metadata güncelle
            $resim->update([
                'dosya_boyutu' => strlen($optimizedImage),
                'genislik' => $image->width(),
                'yukseklik' => $image->height(),
            ]);
            
            return true;
        } catch (\Exception $e) {
            $resim->update(['processing_error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Watermark ekle
     */
    public function addWatermark(Resim $resim, string $watermarkPath): bool
    {
        if (!$resim->kategori->requiresWatermark()) {
            return true;
        }

        if (!Storage::disk('public')->exists($resim->url)) {
            return false;
        }

        try {
            $image = Image::make(Storage::disk('public')->path($resim->url));
            $watermark = Image::make($watermarkPath);
            
            // Watermark boyutunu resmin %10'u kadar yap
            $watermarkWidth = $image->width() * 0.1;
            $watermark->resize($watermarkWidth, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            
            // Sağ alt köşeye yerleştir
            $image->insert($watermark, 'bottom-right', 20, 20);
            
            // Dosyayı güncelle
            Storage::disk('public')->put($resim->url, $image->encode());
            
            return true;
        } catch (\Exception $e) {
            $resim->update(['processing_error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Resim arama
     */
    public function searchImages(array $criteria): \Illuminate\Database\Eloquent\Collection
    {
        $query = Resim::query();

        if (isset($criteria['kategori'])) {
            $query->kategoriye($criteria['kategori']);
        }

        if (isset($criteria['imageable_type'])) {
            $query->where('imageable_type', $criteria['imageable_type']);
        }

        if (isset($criteria['imageable_id'])) {
            $query->where('imageable_id', $criteria['imageable_id']);
        }

        if (isset($criteria['onay_durumu'])) {
            $query->where('onay_durumu', $criteria['onay_durumu']);
        }

        if (isset($criteria['etiket'])) {
            $query->etiketli($criteria['etiket']);
        }

        if (isset($criteria['min_boyut']) || isset($criteria['max_boyut'])) {
            $query->dosyaBoyutuna($criteria['min_boyut'] ?? null, $criteria['max_boyut'] ?? null);
        }

        if (isset($criteria['baslangic_tarihi']) || isset($criteria['bitis_tarihi'])) {
            $query->tarihAraliginda(
                isset($criteria['baslangic_tarihi']) ? Carbon::parse($criteria['baslangic_tarihi']) : null,
                isset($criteria['bitis_tarihi']) ? Carbon::parse($criteria['bitis_tarihi']) : null
            );
        }

        if (isset($criteria['arama_metni'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('baslik', 'like', '%' . $criteria['arama_metni'] . '%')
                  ->orWhere('aciklama', 'like', '%' . $criteria['arama_metni'] . '%')
                  ->orWhere('alt_text', 'like', '%' . $criteria['arama_metni'] . '%');
            });
        }

        return $query->orderBy('siralama')->orderBy('olusturma_tarihi', 'desc')->get();
    }
}