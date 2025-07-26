<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class FileStorageService
{
    private string $defaultDisk;
    private array $diskConfig;

    public function __construct()
    {
        $this->defaultDisk = Config::get('filesystems.default', 'local');
        $this->diskConfig = Config::get('filesystems.disks', []);
    }

    /**
     * Dosya depolama stratejisi - dosya tipine göre disk seçimi
     */
    public function getOptimalDisk(string $fileType, int $fileSize = 0): string
    {
        // Dosya tipine göre disk stratejisi
        return match ($fileType) {
            'image' => $this->getImageStorageDisk($fileSize),
            'document' => $this->getDocumentStorageDisk($fileSize),
            'backup' => $this->getBackupStorageDisk(),
            'temp' => $this->getTempStorageDisk(),
            default => $this->defaultDisk
        };
    }

    /**
     * Resim dosyaları için disk seçimi
     */
    private function getImageStorageDisk(int $fileSize): string
    {
        // Büyük resimler için cloud storage
        if ($fileSize > 10 * 1024 * 1024) { // 10MB'dan büyük
            return $this->getCloudDisk() ?? 'public';
        }

        // Küçük resimler için local storage
        return 'public';
    }

    /**
     * Döküman dosyaları için disk seçimi
     */
    private function getDocumentStorageDisk(int $fileSize): string
    {
        // Büyük dökümanlar için cloud storage
        if ($fileSize > 50 * 1024 * 1024) { // 50MB'dan büyük
            return $this->getCloudDisk() ?? 'local';
        }

        return 'local';
    }

    /**
     * Backup dosyaları için disk seçimi
     */
    private function getBackupStorageDisk(): string
    {
        return $this->getCloudDisk() ?? 'local';
    }

    /**
     * Geçici dosyalar için disk seçimi
     */
    private function getTempStorageDisk(): string
    {
        return 'local'; // Geçici dosyalar her zaman local
    }

    /**
     * Mevcut cloud disk'i bul
     */
    private function getCloudDisk(): ?string
    {
        $cloudDisks = ['s3', 'gcs', 'azure', 'digitalocean'];
        
        foreach ($cloudDisks as $disk) {
            if (isset($this->diskConfig[$disk])) {
                return $disk;
            }
        }

        return null;
    }

    /**
     * Dosya yolu oluşturma stratejisi
     */
    public function generatePath(
        string $fileType,
        string $category,
        string $entityType,
        string $entityId,
        ?Carbon $date = null
    ): string {
        $date = $date ?? now();
        $year = $date->year;
        $month = $date->format('m');
        $day = $date->format('d');

        return match ($fileType) {
            'image' => "images/{$category}/{$entityType}/{$entityId}/{$year}/{$month}",
            'document' => "documents/{$category}/{$entityType}/{$entityId}/{$year}/{$month}",
            'backup' => "backups/{$year}/{$month}/{$day}",
            'temp' => "temp/{$year}/{$month}/{$day}",
            'export' => "exports/{$entityType}/{$year}/{$month}",
            'import' => "imports/{$entityType}/{$year}/{$month}",
            default => "files/{$fileType}/{$year}/{$month}"
        };
    }

    /**
     * Güvenli dosya adı oluştur
     */
    public function generateSecureFileName(
        UploadedFile $file,
        string $prefix = '',
        bool $preserveOriginalName = false
    ): string {
        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = substr(md5(uniqid()), 0, 8);

        if ($preserveOriginalName) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalName);
            $safeName = substr($safeName, 0, 50); // Maksimum 50 karakter
            
            return $prefix ? "{$prefix}_{$safeName}_{$timestamp}_{$random}.{$extension}" 
                          : "{$safeName}_{$timestamp}_{$random}.{$extension}";
        }

        return $prefix ? "{$prefix}_{$timestamp}_{$random}.{$extension}" 
                      : "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Dosya boyutuna göre chunk upload gerekli mi?
     */
    public function requiresChunkedUpload(int $fileSize): bool
    {
        $chunkThreshold = Config::get('app.chunk_upload_threshold', 100 * 1024 * 1024); // 100MB
        return $fileSize > $chunkThreshold;
    }

    /**
     * Disk kapasitesi kontrolü
     */
    public function checkDiskSpace(string $disk, int $requiredSpace): array
    {
        try {
            $diskPath = $this->getDiskPath($disk);
            
            if (!$diskPath) {
                return [
                    'available' => true,
                    'message' => 'Cloud storage - kapasite kontrolü yapılamıyor'
                ];
            }

            $freeSpace = disk_free_space($diskPath);
            $totalSpace = disk_total_space($diskPath);

            $available = $freeSpace > $requiredSpace;
            $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            return [
                'available' => $available,
                'free_space' => $freeSpace,
                'total_space' => $totalSpace,
                'usage_percent' => round($usagePercent, 2),
                'required_space' => $requiredSpace,
                'message' => $available ? 'Yeterli alan var' : 'Yetersiz disk alanı'
            ];

        } catch (\Exception $e) {
            return [
                'available' => false,
                'message' => 'Disk alanı kontrolü yapılamadı: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Disk fiziksel yolunu al
     */
    private function getDiskPath(string $disk): ?string
    {
        $diskConfig = $this->diskConfig[$disk] ?? null;
        
        if (!$diskConfig || $diskConfig['driver'] !== 'local') {
            return null;
        }

        return $diskConfig['root'] ?? null;
    }

    /**
     * Dosya temizleme - eski dosyaları sil
     */
    public function cleanupOldFiles(string $disk, string $path, int $daysOld = 30): array
    {
        try {
            $cutoffDate = now()->subDays($daysOld);
            $deletedFiles = [];
            $totalSize = 0;

            $files = Storage::disk($disk)->allFiles($path);

            foreach ($files as $file) {
                $lastModified = Storage::disk($disk)->lastModified($file);
                
                if ($lastModified < $cutoffDate->timestamp) {
                    $size = Storage::disk($disk)->size($file);
                    
                    if (Storage::disk($disk)->delete($file)) {
                        $deletedFiles[] = $file;
                        $totalSize += $size;
                    }
                }
            }

            return [
                'success' => true,
                'deleted_count' => count($deletedFiles),
                'total_size_freed' => $totalSize,
                'formatted_size' => $this->formatBytes($totalSize),
                'files' => $deletedFiles
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Dosya temizleme hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Dosya sıkıştırma
     */
    public function compressFile(string $disk, string $filePath): array
    {
        try {
            if (!Storage::disk($disk)->exists($filePath)) {
                return [
                    'success' => false,
                    'message' => 'Dosya bulunamadı'
                ];
            }

            $originalSize = Storage::disk($disk)->size($filePath);
            $fileContent = Storage::disk($disk)->get($filePath);
            
            // Gzip sıkıştırma
            $compressedContent = gzencode($fileContent, 9);
            $compressedPath = $filePath . '.gz';
            
            Storage::disk($disk)->put($compressedPath, $compressedContent);
            $compressedSize = Storage::disk($disk)->size($compressedPath);

            return [
                'success' => true,
                'original_path' => $filePath,
                'compressed_path' => $compressedPath,
                'original_size' => $originalSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => round((1 - ($compressedSize / $originalSize)) * 100, 2),
                'savings' => $originalSize - $compressedSize
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Sıkıştırma hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Dosya sıkıştırmasını aç
     */
    public function decompressFile(string $disk, string $compressedPath): array
    {
        try {
            if (!Storage::disk($disk)->exists($compressedPath)) {
                return [
                    'success' => false,
                    'message' => 'Sıkıştırılmış dosya bulunamadı'
                ];
            }

            $compressedContent = Storage::disk($disk)->get($compressedPath);
            $decompressedContent = gzdecode($compressedContent);
            
            if ($decompressedContent === false) {
                return [
                    'success' => false,
                    'message' => 'Dosya açılamadı'
                ];
            }

            $originalPath = str_replace('.gz', '', $compressedPath);
            Storage::disk($disk)->put($originalPath, $decompressedContent);

            return [
                'success' => true,
                'compressed_path' => $compressedPath,
                'decompressed_path' => $originalPath,
                'decompressed_size' => strlen($decompressedContent)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Açma hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Dosya yedekleme
     */
    public function backupFile(string $sourceDisk, string $sourcePath, string $backupDisk = null): array
    {
        try {
            $backupDisk = $backupDisk ?? $this->getBackupStorageDisk();
            
            if (!Storage::disk($sourceDisk)->exists($sourcePath)) {
                return [
                    'success' => false,
                    'message' => 'Kaynak dosya bulunamadı'
                ];
            }

            $backupPath = 'backups/' . now()->format('Y/m/d') . '/' . basename($sourcePath);
            $fileContent = Storage::disk($sourceDisk)->get($sourcePath);
            
            Storage::disk($backupDisk)->put($backupPath, $fileContent);

            return [
                'success' => true,
                'source_path' => $sourcePath,
                'backup_path' => $backupPath,
                'backup_disk' => $backupDisk,
                'file_size' => strlen($fileContent)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Yedekleme hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Disk kullanım raporu
     */
    public function getDiskUsageReport(): array
    {
        $report = [];

        foreach ($this->diskConfig as $diskName => $config) {
            try {
                $usage = $this->getDiskUsage($diskName);
                $report[$diskName] = $usage;
            } catch (\Exception $e) {
                $report[$diskName] = [
                    'error' => $e->getMessage()
                ];
            }
        }

        return $report;
    }

    /**
     * Belirli bir disk için kullanım bilgisi
     */
    private function getDiskUsage(string $disk): array
    {
        $diskPath = $this->getDiskPath($disk);
        
        if (!$diskPath) {
            return [
                'type' => 'cloud',
                'message' => 'Cloud storage - detaylı bilgi alınamıyor'
            ];
        }

        $freeSpace = disk_free_space($diskPath);
        $totalSpace = disk_total_space($diskPath);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = ($usedSpace / $totalSpace) * 100;

        return [
            'type' => 'local',
            'total_space' => $totalSpace,
            'used_space' => $usedSpace,
            'free_space' => $freeSpace,
            'usage_percent' => round($usagePercent, 2),
            'formatted' => [
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace)
            ]
        ];
    }

    /**
     * Dosya güvenlik kontrolü
     */
    public function validateFileSecurity(UploadedFile $file): array
    {
        $errors = [];
        $warnings = [];

        // Dosya uzantısı kontrolü
        $extension = strtolower($file->getClientOriginalExtension());
        $dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'scr', 'pif', 'vbs', 'js'];
        
        if (in_array($extension, $dangerousExtensions)) {
            $errors[] = 'Güvenlik riski: Tehlikeli dosya uzantısı';
        }

        // MIME type kontrolü
        $mimeType = $file->getMimeType();
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            $warnings[] = 'Uyarı: Beklenmeyen MIME type - ' . $mimeType;
        }

        // Dosya boyutu kontrolü
        $maxSize = Config::get('app.max_file_size', 100 * 1024 * 1024); // 100MB
        if ($file->getSize() > $maxSize) {
            $errors[] = 'Dosya boyutu çok büyük';
        }

        // Dosya adı kontrolü
        $fileName = $file->getClientOriginalName();
        if (preg_match('/[<>:"|?*]/', $fileName)) {
            $warnings[] = 'Dosya adında geçersiz karakterler var';
        }

        return [
            'safe' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Byte formatı
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

    /**
     * Dosya URL'si oluştur
     */
    public function getFileUrl(string $disk, string $path): string
    {
        if ($disk === 'public') {
            return Storage::disk('public')->url($path);
        }

        // Cloud storage için signed URL
        if (in_array($disk, ['s3', 'gcs', 'azure'])) {
            return Storage::disk($disk)->temporaryUrl($path, now()->addHours(1));
        }

        // Local disk için route üzerinden
        return route('file.serve', ['disk' => $disk, 'path' => base64_encode($path)]);
    }

    /**
     * Dosya metadata'sı
     */
    public function getFileMetadata(string $disk, string $path): array
    {
        try {
            if (!Storage::disk($disk)->exists($path)) {
                return ['exists' => false];
            }

            return [
                'exists' => true,
                'size' => Storage::disk($disk)->size($path),
                'last_modified' => Storage::disk($disk)->lastModified($path),
                'mime_type' => Storage::disk($disk)->mimeType($path),
                'formatted_size' => $this->formatBytes(Storage::disk($disk)->size($path)),
                'formatted_date' => Carbon::createFromTimestamp(Storage::disk($disk)->lastModified($path))->format('d.m.Y H:i:s')
            ];

        } catch (\Exception $e) {
            return [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}