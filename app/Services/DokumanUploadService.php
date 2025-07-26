<?php

namespace App\Services;

use App\Models\Dokuman;
use App\Enums\DokumanTipi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DokumanUploadService
{
    /**
     * Döküman yükle
     */
    public function upload(
        UploadedFile $file,
        string $documentableType,
        string $documentableId,
        DokumanTipi $dokumanTipi,
        array $additionalData = []
    ): array {
        try {
            // Dosya validasyonu
            $validationErrors = $this->validateFile($file, $dokumanTipi);
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'errors' => $validationErrors
                ];
            }

            // Dosya hash'i oluştur
            $fileHash = hash_file('sha256', $file->getRealPath());

            // Duplicate kontrolü
            if (Dokuman::isDuplicate($fileHash, $documentableType, $documentableId)) {
                return [
                    'success' => false,
                    'errors' => ['Bu dosya zaten yüklenmiş.']
                ];
            }

            // Dosya adı oluştur
            $fileName = $this->generateFileName($file, $dokumanTipi);
            
            // Dosya yolu oluştur
            $path = $this->generatePath($documentableType, $documentableId, $dokumanTipi);
            
            // Dosyayı kaydet
            $storedPath = $file->storeAs($path, $fileName, 'public');

            // Veritabanına kaydet
            $dokuman = Dokuman::create([
                'url' => $storedPath,
                'documentable_id' => $documentableId,
                'documentable_type' => $documentableType,
                'dokuman_tipi' => $dokumanTipi,
                'baslik' => $additionalData['baslik'] ?? $file->getClientOriginalName(),
                'aciklama' => $additionalData['aciklama'] ?? null,
                'dosya_adi' => $fileName,
                'orijinal_dosya_adi' => $file->getClientOriginalName(),
                'dosya_boyutu' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'dosya_uzantisi' => $file->getClientOriginalExtension(),
                'dosya_hash' => $fileHash,
                'gizli_mi' => $additionalData['gizli_mi'] ?? false,
                'erisim_izinleri' => $additionalData['erisim_izinleri'] ?? null,
                'metadata' => $this->extractMetadata($file),
                'olusturan_id' => Auth::id(),
                'aktif_mi' => true,
            ]);

            return [
                'success' => true,
                'dokuman' => $dokuman,
                'message' => 'Döküman başarıyla yüklendi.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Dosya yüklenirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Toplu döküman yükleme
     */
    public function uploadMultiple(
        array $files,
        string $documentableType,
        string $documentableId,
        DokumanTipi $dokumanTipi,
        array $additionalData = []
    ): array {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($files as $file) {
            $result = $this->upload($file, $documentableType, $documentableId, $dokumanTipi, $additionalData);
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
     * Döküman versiyonu güncelle
     */
    public function updateVersion(
        Dokuman $existingDokuman,
        UploadedFile $file,
        array $additionalData = []
    ): array {
        try {
            // Dosya validasyonu
            $validationErrors = $this->validateFile($file, $existingDokuman->dokuman_tipi);
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'errors' => $validationErrors
                ];
            }

            // Dosya hash'i oluştur
            $fileHash = hash_file('sha256', $file->getRealPath());

            // Dosya adı oluştur
            $fileName = $this->generateFileName($file, $existingDokuman->dokuman_tipi);
            
            // Dosya yolu oluştur
            $path = $this->generatePath(
                $existingDokuman->documentable_type,
                $existingDokuman->documentable_id,
                $existingDokuman->dokuman_tipi
            );
            
            // Dosyayı kaydet
            $storedPath = $file->storeAs($path, $fileName, 'public');

            // Yeni versiyon oluştur
            $newVersion = $existingDokuman->createNewVersion([
                'url' => $storedPath,
                'baslik' => $additionalData['baslik'] ?? $file->getClientOriginalName(),
                'aciklama' => $additionalData['aciklama'] ?? null,
                'dosya_adi' => $fileName,
                'orijinal_dosya_adi' => $file->getClientOriginalName(),
                'dosya_boyutu' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'dosya_uzantisi' => $file->getClientOriginalExtension(),
                'dosya_hash' => $fileHash,
                'metadata' => $this->extractMetadata($file),
                'olusturan_id' => Auth::id(),
                'guncelleyen_id' => Auth::id(),
            ]);

            return [
                'success' => true,
                'dokuman' => $newVersion,
                'message' => 'Döküman yeni versiyonu başarıyla oluşturuldu.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Versiyon güncellenirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Dosya validasyonu
     */
    private function validateFile(UploadedFile $file, DokumanTipi $dokumanTipi): array
    {
        $errors = [];

        // MIME type kontrolü
        if (!in_array($file->getMimeType(), $dokumanTipi->allowedMimeTypes())) {
            $errors[] = "Bu döküman tipi için {$file->getMimeType()} formatı desteklenmiyor.";
        }

        // Dosya boyutu kontrolü
        $maxSize = $dokumanTipi->maxFileSize() * 1024 * 1024; // MB to bytes
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = $dokumanTipi->maxFileSize();
            $errors[] = "Dosya boyutu {$maxSizeMB}MB'ı aşamaz.";
        }

        // Dosya bütünlüğü kontrolü
        if (!$file->isValid()) {
            $errors[] = 'Dosya bozuk veya geçersiz.';
        }

        return $errors;
    }

    /**
     * Dosya adı oluştur
     */
    private function generateFileName(UploadedFile $file, DokumanTipi $dokumanTipi): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomString = Str::random(8);
        $extension = $file->getClientOriginalExtension();
        
        return "{$dokumanTipi->value}_{$timestamp}_{$randomString}.{$extension}";
    }

    /**
     * Dosya yolu oluştur
     */
    private function generatePath(string $documentableType, string $documentableId, DokumanTipi $dokumanTipi): string
    {
        $modelName = class_basename($documentableType);
        $year = now()->year;
        $month = now()->format('m');
        
        return "dokumanlar/{$modelName}/{$documentableId}/{$dokumanTipi->value}/{$year}/{$month}";
    }

    /**
     * Dosya metadata'sını çıkar
     */
    private function extractMetadata(UploadedFile $file): array
    {
        $metadata = [
            'upload_time' => now()->toISOString(),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];

        // Resim dosyaları için ek bilgiler
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                $imageInfo = getimagesize($file->getRealPath());
                if ($imageInfo) {
                    $metadata['width'] = $imageInfo[0];
                    $metadata['height'] = $imageInfo[1];
                    $metadata['type'] = $imageInfo[2];
                }
            } catch (\Exception $e) {
                // Hata durumunda metadata'ya ekleme
            }
        }

        return $metadata;
    }

    /**
     * Döküman sil
     */
    public function delete(Dokuman $dokuman): array
    {
        try {
            // Dosyayı fiziksel olarak sil
            if (Storage::disk('public')->exists($dokuman->url)) {
                Storage::disk('public')->delete($dokuman->url);
            }

            // Veritabanından soft delete
            $dokuman->update([
                'aktif_mi' => false,
                'guncelleyen_id' => Auth::id()
            ]);
            $dokuman->delete();

            return [
                'success' => true,
                'message' => 'Döküman başarıyla silindi.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Döküman silinirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Döküman geri yükle
     */
    public function restore(Dokuman $dokuman): array
    {
        try {
            $dokuman->restore();
            $dokuman->update([
                'aktif_mi' => true,
                'guncelleyen_id' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => 'Döküman başarıyla geri yüklendi.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Döküman geri yüklenirken hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Mülk tipine göre uygun döküman tiplerini getir
     */
    public function getAvailableTypesForMulk(string $mulkType): array
    {
        return DokumanTipi::forMulkType($mulkType);
    }

    /**
     * Döküman istatistikleri
     */
    public function getStatistics(string $documentableType, string $documentableId): array
    {
        $query = Dokuman::where('documentable_type', $documentableType)
                        ->where('documentable_id', $documentableId)
                        ->where('aktif_mi', true);

        return [
            'total_count' => $query->count(),
            'total_size' => $query->sum('dosya_boyutu'),
            'by_type' => $query->selectRaw('dokuman_tipi, COUNT(*) as count, SUM(dosya_boyutu) as size')
                              ->groupBy('dokuman_tipi')
                              ->get()
                              ->mapWithKeys(function ($item) {
                                  return [$item->dokuman_tipi => [
                                      'count' => $item->count,
                                      'size' => $item->size
                                  ]];
                              }),
            'recent_uploads' => $query->latest('olusturma_tarihi')->limit(5)->get()
        ];
    }
}