<?php

namespace App\Services;

use App\Models\Resim;
use App\Enums\ResimKategorisi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Intervention\Image\Image as InterventionImage;

class ResimUploadService
{
    /**
     * Resim yükle ve işle
     */
    public function upload(
        UploadedFile $file,
        string $imageableType,
        string $imageableId,
        ResimKategorisi $kategori,
        array $additionalData = []
    ): array {
        try {
            // Dosya validasyonu
            $validationErrors = $this->validateFile($file, $kategori);
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'errors' => $validationErrors
                ];
            }

            // Dosya hash'i oluştur
            $fileHash = hash_file('md5', $file->getRealPath());

            // Duplicate kontrolü
            if ($this->isDuplicate($fileHash, $imageableType, $imageableId)) {
                return [
                    'success' => false,
                    'errors' => ['Bu resim zaten yüklenmiş.']
                ];
            }

            // Dosya adı oluştur
            $fileName = $this->generateFileName($file, $kategori);
            
            // Dosya yolu oluştur
            $path = $this->generatePath($imageableType, $imageableId, $kategori);
            
            // Resmi işle ve kaydet
            $processedImage = $this->processImage($file, $kategori);
            $storedPath = $processedImage->storeAs($path, $fileName, 'public');

            // Resim boyutlarını al
            $dimensions = $this->getImageDimensions($processedImage);

            // EXIF verilerini çıkar
            $exifData = $this->extractExifData($file);

            // Veritabanına kaydet
            $resim = Resim::create([
                'url' => $storedPath,
                'imageable_id' => $imageableId,
                'imageable_type' => $imageableType,
                'kategori' => $kategori,
                'baslik' => $additionalData['baslik'] ?? null,
                'aciklama' => $additionalData['aciklama'] ?? null,
                'cekim_tarihi' => $additionalData['cekim_tarihi'] ?? $exifData['cekim_tarihi'] ?? null,
                'dosya_adi' => $fileName,
                'orijinal_dosya_adi' => $file->getClientOriginalName(),
                'dosya_boyutu' => $file->getSize(),
                'genislik' => $dimensions['width'],
                'yukseklik' => $dimensions['height'],
                'mime_type' => $file->getMimeType(),
                'hash' => $fileHash,
                'exif_data' => $exifData['data'],
                'upload_ip' => request()->ip(),
                'upload_user_agent' => request()->userAgent(),
                'yükleyen_id' => Auth::id(),
                'onay_durumu' => $kategori->requiresApproval() ? 'beklemede' : 'onaylandı',
                'alt_text' => $additionalData['alt_text'] ?? null,
                'copyright_bilgisi' => $additionalData['copyright_bilgisi'] ?? null,
                'etiketler' => $additionalData['etiketler'] ?? null,
                'aktif_mi' => true,
                'siralama' => $additionalData['siralama'] ?? $kategori->sortPriority(),
                'is_processed' => false,
            ]);

            // Asenkron işleme için job kuyruğuna ekle
            $this->queueImageProcessing($resim);

            return [
                'success' => true,
                'resim' => $resim,
                'message' => 'Resim başarıyla yüklendi.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Resim yüklenirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Toplu resim yükleme
     */
    public function uploadMultiple(
        array $files,
        string $imageableType,
        string $imageableId,
        ResimKategorisi $kategori,
        array $additionalData = []
    ): array {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($files as $index => $file) {
            $fileData = $additionalData;
            
            // Her dosya için farklı sıralama
            if (!isset($fileData['siralama'])) {
                $fileData['siralama'] = $kategori->sortPriority() + $index;
            }

            $result = $this->upload($file, $imageableType, $imageableId, $kategori, $fileData);
            $results[] = $result;
            
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        return [
            'results' => $results,
            'summary' => [
                'total' => count($files),
                'success' => $successCount,
                'error' => $errorCount
            ]
        ];
    }

    /**
     * Resim güncelle (yeni versiyon)
     */
    public function updateImage(
        Resim $existingResim,
        UploadedFile $file,
        array $additionalData = []
    ): array {
        try {
            // Eski resmi pasif yap
            $existingResim->update(['aktif_mi' => false]);

            // Yeni resmi yükle
            $result = $this->upload(
                $file,
                $existingResim->imageable_type,
                $existingResim->imageable_id,
                $existingResim->kategori,
                array_merge([
                    'baslik' => $existingResim->baslik,
                    'aciklama' => $existingResim->aciklama,
                    'siralama' => $existingResim->siralama,
                ], $additionalData)
            );

            if ($result['success']) {
                // Eski resmi fiziksel olarak sil
                $this->deletePhysicalFile($existingResim);
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Resim güncellenirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Resim boyutlandır ve optimize et
     */
    private function processImage(UploadedFile $file, ResimKategorisi $kategori): InterventionImage
    {
        $image = Image::make($file->getRealPath());
        
        // Kategori bazlı işleme
        switch ($kategori) {
            case ResimKategorisi::AVATAR:
                return $this->processAvatar($image);
                
            case ResimKategorisi::LOGO:
                return $this->processLogo($image);
                
            case ResimKategorisi::KAPAK_RESMI:
                return $this->processKapakResmi($image);
                
            case ResimKategorisi::GALERI:
            case ResimKategorisi::IC_MEKAN:
            case ResimKategorisi::DIS_MEKAN:
            case ResimKategorisi::DETAY:
            case ResimKategorisi::CEPHE:
            case ResimKategorisi::MANZARA:
                return $this->processGalleryImage($image, $kategori);
                
            case ResimKategorisi::PLAN:
                return $this->processPlan($image);
                
            case ResimKategorisi::UYDU:
            case ResimKategorisi::OZNITELIK:
            case ResimKategorisi::BUYUKSEHIR:
            case ResimKategorisi::EGIM:
            case ResimKategorisi::EIMAR:
                return $this->processMapImage($image);
                
            default:
                return $this->processGeneral($image, $kategori);
        }
    }

    /**
     * Avatar resmi işle
     */
    private function processAvatar(InterventionImage $image): InterventionImage
    {
        // Kare format, 300x300
        $image->fit(300, 300);
        
        // Kalite optimizasyonu
        $image->encode('jpg', 85);
        
        return $image;
    }

    /**
     * Logo resmi işle
     */
    private function processLogo(InterventionImage $image): InterventionImage
    {
        // Maksimum 500x200, aspect ratio koru
        $image->resize(500, 200, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // PNG formatında koru (şeffaflık için)
        if ($image->mime() !== 'image/png') {
            $image->encode('png');
        }
        
        return $image;
    }

    /**
     * Kapak resmi işle
     */
    private function processKapakResmi(InterventionImage $image): InterventionImage
    {
        // 16:9 aspect ratio, maksimum 1920x1080
        $image->fit(1920, 1080);
        
        // Yüksek kalite
        $image->encode('jpg', 95);
        
        // Watermark ekle
        $this->addWatermark($image);
        
        return $image;
    }

    /**
     * Galeri resmi işle
     */
    private function processGalleryImage(InterventionImage $image, ResimKategorisi $kategori): InterventionImage
    {
        $dimensions = $kategori->recommendedDimensions();
        
        // Maksimum boyut, aspect ratio koru
        $image->resize($dimensions['width'], $dimensions['height'], function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // Kalite ayarı
        $image->encode('jpg', $kategori->qualitySetting());
        
        // Watermark ekle
        if ($kategori->requiresWatermark()) {
            $this->addWatermark($image);
        }
        
        return $image;
    }

    /**
     * Plan resmi işle
     */
    private function processPlan(InterventionImage $image): InterventionImage
    {
        // Yüksek çözünürlük koru
        $image->resize(2048, 1536, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // Maksimum kalite
        $image->encode('jpg', 100);
        
        return $image;
    }

    /**
     * Harita resmi işle
     */
    private function processMapImage(InterventionImage $image): InterventionImage
    {
        // Orijinal boyutları koru, sadece optimize et
        $image->encode('jpg', 100);
        
        return $image;
    }

    /**
     * Genel resim işleme
     */
    private function processGeneral(InterventionImage $image, ResimKategorisi $kategori): InterventionImage
    {
        $dimensions = $kategori->recommendedDimensions();
        
        $image->resize($dimensions['width'], $dimensions['height'], function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        $image->encode('jpg', $kategori->qualitySetting());
        
        return $image;
    }

    /**
     * Watermark ekle
     */
    private function addWatermark(InterventionImage $image): void
    {
        // Watermark dosyası varsa ekle
        $watermarkPath = public_path('images/watermark.png');
        
        if (file_exists($watermarkPath)) {
            $watermark = Image::make($watermarkPath);
            
            // Watermark boyutunu resim boyutuna göre ayarla
            $watermarkSize = min($image->width(), $image->height()) * 0.1;
            $watermark->resize($watermarkSize, $watermarkSize, function ($constraint) {
                $constraint->aspectRatio();
            });
            
            // Sağ alt köşeye yerleştir
            $image->insert($watermark, 'bottom-right', 10, 10);
        }
    }

    /**
     * Resim boyutlarını al
     */
    private function getImageDimensions(InterventionImage $image): array
    {
        return [
            'width' => $image->width(),
            'height' => $image->height()
        ];
    }

    /**
     * EXIF verilerini çıkar
     */
    private function extractExifData(UploadedFile $file): array
    {
        $result = [
            'data' => null,
            'cekim_tarihi' => null
        ];

        try {
            if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/tiff'])) {
                $exifData = @exif_read_data($file->getRealPath());
                
                if ($exifData) {
                    $result['data'] = $exifData;
                    
                    // Çekim tarihi çıkar
                    if (isset($exifData['DateTime'])) {
                        $result['cekim_tarihi'] = \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exifData['DateTime']);
                    } elseif (isset($exifData['DateTimeOriginal'])) {
                        $result['cekim_tarihi'] = \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exifData['DateTimeOriginal']);
                    }
                }
            }
        } catch (\Exception $e) {
            // EXIF okuma hatası, devam et
        }

        return $result;
    }

    /**
     * Asenkron resim işleme
     */
    private function queueImageProcessing(Resim $resim): void
    {
        // Job kuyruğuna ekle (gerçek implementasyonda)
        // ProcessImageJob::dispatch($resim);
        
        // Şimdilik senkron işle
        $this->processImageVariants($resim);
    }

    /**
     * Resim varyantlarını oluştur
     */
    private function processImageVariants(Resim $resim): void
    {
        try {
            if (!Storage::disk('public')->exists($resim->url)) {
                return;
            }

            $image = Image::make(Storage::disk('public')->path($resim->url));
            $pathInfo = pathinfo($resim->url);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $extension = $pathInfo['extension'];

            // Thumbnail (150x150)
            $thumbnailPath = $directory . '/' . $filename . '_thumb.' . $extension;
            $thumbnail = clone $image;
            $thumbnail->fit(150, 150);
            Storage::disk('public')->put($thumbnailPath, $thumbnail->encode('jpg', 80)->getEncoded());

            // Medium (800x600)
            $mediumPath = $directory . '/' . $filename . '_medium.' . $extension;
            $medium = clone $image;
            $medium->resize(800, 600, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::disk('public')->put($mediumPath, $medium->encode('jpg', 85)->getEncoded());

            // Large (1920x1080)
            $largePath = $directory . '/' . $filename . '_large.' . $extension;
            $large = clone $image;
            $large->resize(1920, 1080, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::disk('public')->put($largePath, $large->encode('jpg', 90)->getEncoded());

            // Veritabanını güncelle
            $resim->update([
                'thumbnail_url' => $thumbnailPath,
                'medium_url' => $mediumPath,
                'large_url' => $largePath,
                'is_processed' => true,
                'processing_error' => null,
            ]);

        } catch (\Exception $e) {
            $resim->update([
                'processing_error' => $e->getMessage(),
                'is_processed' => false,
            ]);
        }
    }

    /**
     * Dosya validasyonu
     */
    private function validateFile(UploadedFile $file, ResimKategorisi $kategori): array
    {
        $errors = [];

        // MIME type kontrolü
        if (!in_array($file->getMimeType(), $kategori->allowedMimeTypes())) {
            $errors[] = "Bu kategori için {$file->getMimeType()} formatı desteklenmiyor.";
        }

        // Dosya boyutu kontrolü
        $maxSize = $kategori->maxFileSize() * 1024 * 1024; // MB to bytes
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = $kategori->maxFileSize();
            $errors[] = "Dosya boyutu {$maxSizeMB}MB'ı aşamaz.";
        }

        // Dosya bütünlüğü kontrolü
        if (!$file->isValid()) {
            $errors[] = 'Dosya bozuk veya geçersiz.';
        }

        // Resim boyut kontrolü
        try {
            $imageInfo = getimagesize($file->getRealPath());
            if (!$imageInfo) {
                $errors[] = 'Geçersiz resim dosyası.';
            } else {
                $minDimensions = $this->getMinimumDimensions($kategori);
                if ($imageInfo[0] < $minDimensions['width'] || $imageInfo[1] < $minDimensions['height']) {
                    $errors[] = "Resim minimum {$minDimensions['width']}x{$minDimensions['height']} boyutunda olmalıdır.";
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Resim dosyası okunamadı.';
        }

        return $errors;
    }

    /**
     * Minimum boyutları al
     */
    private function getMinimumDimensions(ResimKategorisi $kategori): array
    {
        return match ($kategori) {
            ResimKategorisi::AVATAR => ['width' => 100, 'height' => 100],
            ResimKategorisi::LOGO => ['width' => 100, 'height' => 50],
            ResimKategorisi::KAPAK_RESMI => ['width' => 800, 'height' => 450],
            ResimKategorisi::GALERI, ResimKategorisi::IC_MEKAN, ResimKategorisi::DIS_MEKAN => ['width' => 640, 'height' => 480],
            default => ['width' => 200, 'height' => 200],
        };
    }

    /**
     * Duplicate kontrolü
     */
    private function isDuplicate(string $hash, string $imageableType, string $imageableId): bool
    {
        return Resim::where('hash', $hash)
                   ->where('imageable_type', $imageableType)
                   ->where('imageable_id', $imageableId)
                   ->where('aktif_mi', true)
                   ->exists();
    }

    /**
     * Dosya adı oluştur
     */
    private function generateFileName(UploadedFile $file, ResimKategorisi $kategori): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomString = Str::random(8);
        $extension = strtolower($file->getClientOriginalExtension());
        
        return "{$kategori->value}_{$timestamp}_{$randomString}.{$extension}";
    }

    /**
     * Dosya yolu oluştur
     */
    private function generatePath(string $imageableType, string $imageableId, ResimKategorisi $kategori): string
    {
        $modelName = class_basename($imageableType);
        $year = now()->year;
        $month = now()->format('m');
        
        return "resimler/{$modelName}/{$imageableId}/{$kategori->value}/{$year}/{$month}";
    }

    /**
     * Resim sil
     */
    public function delete(Resim $resim): array
    {
        try {
            // Fiziksel dosyaları sil
            $this->deletePhysicalFile($resim);

            // Veritabanından soft delete
            $resim->update(['aktif_mi' => false]);
            $resim->delete();

            return [
                'success' => true,
                'message' => 'Resim başarıyla silindi.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Resim silinirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Fiziksel dosyaları sil
     */
    private function deletePhysicalFile(Resim $resim): void
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
     * Resim geri yükle
     */
    public function restore(Resim $resim): array
    {
        try {
            $resim->restore();
            $resim->update(['aktif_mi' => true]);

            return [
                'success' => true,
                'message' => 'Resim başarıyla geri yüklendi.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Resim geri yüklenirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Mülk tipine göre uygun kategorileri getir
     */
    public function getAvailableCategoriesForProperty(string $propertyType): array
    {
        return ResimKategorisi::forPropertyType($propertyType);
    }

    /**
     * Resim istatistikleri
     */
    public function getStatistics(string $imageableType, string $imageableId): array
    {
        $query = Resim::where('imageable_type', $imageableType)
                     ->where('imageable_id', $imageableId)
                     ->where('aktif_mi', true);

        return [
            'total_count' => $query->count(),
            'total_size' => $query->sum('dosya_boyutu'),
            'by_category' => $query->selectRaw('kategori, COUNT(*) as count, SUM(dosya_boyutu) as size')
                                  ->groupBy('kategori')
                                  ->get()
                                  ->mapWithKeys(function ($item) {
                                      return [$item->kategori => [
                                          'count' => $item->count,
                                          'size' => $item->size
                                      ]];
                                  }),
            'approval_status' => $query->selectRaw('onay_durumu, COUNT(*) as count')
                                     ->groupBy('onay_durumu')
                                     ->get()
                                     ->pluck('count', 'onay_durumu'),
            'recent_uploads' => $query->latest('olusturma_tarihi')->limit(5)->get(),
            'most_viewed' => $query->orderBy('görüntülenme_sayisi', 'desc')->limit(5)->get(),
        ];
    }

    /**
     * Resim optimizasyonu
     */
    public function optimizeImage(Resim $resim): array
    {
        try {
            if (!Storage::disk('public')->exists($resim->url)) {
                return [
                    'success' => false,
                    'errors' => ['Resim dosyası bulunamadı.']
                ];
            }

            $image = Image::make(Storage::disk('public')->path($resim->url));
            
            // Kalite optimizasyonu
            $quality = $resim->kategori->qualitySetting();
            $image->encode('jpg', $quality);
            
            // Dosyayı güncelle
            Storage::disk('public')->put($resim->url, $image->getEncoded());
            
            // Boyut bilgisini güncelle
            $newSize = Storage::disk('public')->size($resim->url);
            $resim->update(['dosya_boyutu' => $newSize]);

            return [
                'success' => true,
                'message' => 'Resim başarıyla optimize edildi.',
                'old_size' => $resim->getOriginal('dosya_boyutu'),
                'new_size' => $newSize,
                'savings' => $resim->getOriginal('dosya_boyutu') - $newSize,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Resim optimize edilirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Toplu resim optimizasyonu
     */
    public function bulkOptimize(array $resimIds): array
    {
        $results = [];
        $totalSavings = 0;

        foreach ($resimIds as $resimId) {
            $resim = Resim::find($resimId);
            if ($resim) {
                $result = $this->optimizeImage($resim);
                $results[] = $result;
                
                if ($result['success'] && isset($result['savings'])) {
                    $totalSavings += $result['savings'];
                }
            }
        }

        return [
            'results' => $results,
            'total_savings' => $totalSavings,
            'formatted_savings' => $this->formatBytes($totalSavings),
        ];
    }

    /**
     * Byte'ları human readable format'a çevir
     */
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