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
     * Gelişmiş dosya validasyonu ve güvenlik kontrolleri
     */
    private function validateFile(UploadedFile $file, DokumanTipi $dokumanTipi): array
    {
        $errors = [];

        // Dosya bütünlüğü kontrolü
        if (!$file->isValid()) {
            $errors[] = 'Dosya bozuk veya geçersiz.';
            return $errors;
        }

        // MIME type kontrolü
        $detectedMimeType = $file->getMimeType();
        $allowedMimeTypes = $dokumanTipi->allowedMimeTypes();
        
        if (!in_array($detectedMimeType, $allowedMimeTypes)) {
            $errors[] = "Bu döküman tipi için {$detectedMimeType} formatı desteklenmiyor.";
        }

        // Dosya uzantısı kontrolü (MIME type spoofing koruması)
        $fileExtension = strtolower($file->getClientOriginalExtension());
        $expectedExtensions = $this->getMimeTypeExtensions($detectedMimeType);
        
        if (!in_array($fileExtension, $expectedExtensions)) {
            $errors[] = "Dosya uzantısı ({$fileExtension}) MIME type ile uyumlu değil.";
        }

        // Dosya boyutu kontrolü
        $maxSize = $dokumanTipi->maxFileSize() * 1024 * 1024; // MB to bytes
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = $dokumanTipi->maxFileSize();
            $errors[] = "Dosya boyutu {$maxSizeMB}MB'ı aşamaz.";
        }

        // Minimum dosya boyutu kontrolü (0 byte dosya koruması)
        if ($file->getSize() < 100) { // 100 byte minimum
            $errors[] = 'Dosya çok küçük, geçerli bir döküman değil.';
        }

        // Dosya adı güvenlik kontrolü
        $originalName = $file->getClientOriginalName();
        if ($this->hasUnsafeFileName($originalName)) {
            $errors[] = 'Dosya adı güvenli olmayan karakterler içeriyor.';
        }

        // İçerik tabanlı güvenlik kontrolleri
        $contentErrors = $this->validateFileContent($file, $dokumanTipi);
        $errors = array_merge($errors, $contentErrors);

        // Virüs tarama (opsiyonel - ClamAV gibi)
        if (config('filesystems.virus_scan_enabled', false)) {
            $virusErrors = $this->scanForVirus($file);
            $errors = array_merge($errors, $virusErrors);
        }

        return $errors;
    }

    /**
     * MIME type'a göre beklenen dosya uzantıları
     */
    private function getMimeTypeExtensions(string $mimeType): array
    {
        return match ($mimeType) {
            'application/pdf' => ['pdf'],
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/tiff' => ['tif', 'tiff'],
            'application/dwg', 'application/acad' => ['dwg'],
            'application/dxf' => ['dxf'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'application/vnd.ms-excel' => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
            default => []
        };
    }

    /**
     * Güvenli olmayan dosya adı kontrolü
     */
    private function hasUnsafeFileName(string $fileName): bool
    {
        // Tehlikeli karakterler ve pattern'ler
        $unsafePatterns = [
            '/\.\.|\/|\\\\/',  // Directory traversal
            '/[<>:"|?*]/',     // Windows reserved characters
            '/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/i', // Windows reserved names
            '/\.(php|js|html|htm|exe|bat|cmd|sh)$/i',    // Executable extensions
            '/\x00/',          // Null bytes
        ];

        foreach ($unsafePatterns as $pattern) {
            if (preg_match($pattern, $fileName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dosya içeriği güvenlik kontrolü
     */
    private function validateFileContent(UploadedFile $file, DokumanTipi $dokumanTipi): array
    {
        $errors = [];
        $filePath = $file->getRealPath();

        try {
            // Dosya header kontrolü (magic bytes)
            $fileHeader = file_get_contents($filePath, false, null, 0, 20);
            
            if (!$this->isValidFileHeader($fileHeader, $file->getMimeType())) {
                $errors[] = 'Dosya içeriği MIME type ile uyumlu değil.';
            }

            // PDF özel kontrolleri
            if ($file->getMimeType() === 'application/pdf') {
                $pdfErrors = $this->validatePdfContent($filePath);
                $errors = array_merge($errors, $pdfErrors);
            }

            // Resim dosyaları için kontroller
            if (str_starts_with($file->getMimeType(), 'image/')) {
                $imageErrors = $this->validateImageContent($filePath);
                $errors = array_merge($errors, $imageErrors);
            }

            // Zararlı script kontrolü
            $scriptErrors = $this->scanForMaliciousContent($filePath);
            $errors = array_merge($errors, $scriptErrors);

        } catch (\Exception $e) {
            $errors[] = 'Dosya içeriği analiz edilemedi.';
        }

        return $errors;
    }

    /**
     * Dosya header (magic bytes) kontrolü
     */
    private function isValidFileHeader(string $header, string $mimeType): bool
    {
        $magicBytes = [
            'application/pdf' => ['%PDF'],
            'image/jpeg' => ["\xFF\xD8\xFF"],
            'image/png' => ["\x89PNG\r\n\x1A\n"],
            'image/tiff' => ["II*\x00", "MM\x00*"],
            'application/msword' => ["\xD0\xCF\x11\xE0"],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ["PK\x03\x04"],
        ];

        if (!isset($magicBytes[$mimeType])) {
            return true; // Bilinmeyen tip için geç
        }

        foreach ($magicBytes[$mimeType] as $magic) {
            if (str_starts_with($header, $magic)) {
                return true;
            }
        }

        return false;
    }

    /**
     * PDF içerik kontrolü
     */
    private function validatePdfContent(string $filePath): array
    {
        $errors = [];

        try {
            // PDF header kontrolü
            $handle = fopen($filePath, 'rb');
            $header = fread($handle, 8);
            fclose($handle);

            if (!str_starts_with($header, '%PDF-')) {
                $errors[] = 'Geçerli bir PDF dosyası değil.';
                return $errors;
            }

            // PDF versiyonu kontrolü
            $version = substr($header, 5, 3);
            if (!in_array($version, ['1.0', '1.1', '1.2', '1.3', '1.4', '1.5', '1.6', '1.7', '2.0'])) {
                $errors[] = 'Desteklenmeyen PDF versiyonu.';
            }

            // Dosya boyutu vs içerik tutarlılığı
            $fileSize = filesize($filePath);
            if ($fileSize > 100 * 1024 * 1024) { // 100MB üzeri PDF'ler şüpheli
                $errors[] = 'PDF dosyası çok büyük, güvenlik riski oluşturabilir.';
            }

        } catch (\Exception $e) {
            $errors[] = 'PDF dosyası analiz edilemedi.';
        }

        return $errors;
    }

    /**
     * Resim içerik kontrolü
     */
    private function validateImageContent(string $filePath): array
    {
        $errors = [];

        try {
            $imageInfo = getimagesize($filePath);
            
            if (!$imageInfo) {
                $errors[] = 'Geçerli bir resim dosyası değil.';
                return $errors;
            }

            // Aşırı büyük resim kontrolü
            if ($imageInfo[0] > 10000 || $imageInfo[1] > 10000) {
                $errors[] = 'Resim boyutları çok büyük (max 10000x10000).';
            }

            // Sıfır boyutlu resim kontrolü
            if ($imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
                $errors[] = 'Geçersiz resim boyutları.';
            }

        } catch (\Exception $e) {
            $errors[] = 'Resim dosyası analiz edilemedi.';
        }

        return $errors;
    }

    /**
     * Zararlı içerik tarama
     */
    private function scanForMaliciousContent(string $filePath): array
    {
        $errors = [];

        try {
            // Dosya içeriğinin bir kısmını oku (ilk 1MB)
            $content = file_get_contents($filePath, false, null, 0, 1024 * 1024);
            
            // Zararlı pattern'ler
            $maliciousPatterns = [
                '/<script[^>]*>.*?<\/script>/is',
                '/javascript:/i',
                '/vbscript:/i',
                '/onload\s*=/i',
                '/onerror\s*=/i',
                '/eval\s*\(/i',
                '/exec\s*\(/i',
                '/system\s*\(/i',
                '/shell_exec\s*\(/i',
                '/passthru\s*\(/i',
            ];

            foreach ($maliciousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $errors[] = 'Dosya zararlı içerik barındırıyor olabilir.';
                    break;
                }
            }

            // Şüpheli string'ler
            $suspiciousStrings = [
                'eval(',
                'base64_decode(',
                'shell_exec(',
                'system(',
                'exec(',
                'passthru(',
                'file_get_contents(',
                'file_put_contents(',
                'fopen(',
                'fwrite(',
            ];

            $suspiciousCount = 0;
            foreach ($suspiciousStrings as $string) {
                if (stripos($content, $string) !== false) {
                    $suspiciousCount++;
                }
            }

            if ($suspiciousCount > 2) {
                $errors[] = 'Dosya şüpheli kod yapıları içeriyor.';
            }

        } catch (\Exception $e) {
            // Hata durumunda sessizce geç
        }

        return $errors;
    }

    /**
     * Virüs tarama (ClamAV entegrasyonu)
     */
    private function scanForVirus(UploadedFile $file): array
    {
        $errors = [];

        try {
            // ClamAV socket bağlantısı
            $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
            
            if (!$socket || !socket_connect($socket, '/var/run/clamav/clamd.ctl')) {
                // ClamAV mevcut değilse sessizce geç
                return $errors;
            }

            // Dosyayı tara
            $command = "SCAN " . $file->getRealPath() . "\n";
            socket_write($socket, $command, strlen($command));
            
            $response = socket_read($socket, 1024);
            socket_close($socket);

            if (strpos($response, 'FOUND') !== false) {
                $errors[] = 'Dosyada virüs tespit edildi.';
            }

        } catch (\Exception $e) {
            // Virüs tarama hatası durumunda sessizce geç
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