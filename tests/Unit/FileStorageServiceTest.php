<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class FileStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private FileStorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageService = new FileStorageService();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_gets_optimal_disk_for_image()
    {
        // Küçük resim için public disk
        $disk = $this->storageService->getOptimalDisk('image', 5 * 1024 * 1024); // 5MB
        $this->assertEquals('public', $disk);

        // Büyük resim için cloud disk (yoksa public)
        $disk = $this->storageService->getOptimalDisk('image', 15 * 1024 * 1024); // 15MB
        $this->assertEquals('public', $disk); // Cloud disk olmadığı için public
    }

    public function test_gets_optimal_disk_for_document()
    {
        // Küçük döküman için local disk
        $disk = $this->storageService->getOptimalDisk('document', 10 * 1024 * 1024); // 10MB
        $this->assertEquals('local', $disk);

        // Büyük döküman için cloud disk (yoksa local)
        $disk = $this->storageService->getOptimalDisk('document', 60 * 1024 * 1024); // 60MB
        $this->assertEquals('local', $disk); // Cloud disk olmadığı için local
    }

    public function test_generates_path_for_different_file_types()
    {
        $date = now()->setDate(2024, 3, 15);

        // Resim yolu
        $imagePath = $this->storageService->generatePath('image', 'galeri', 'Mulk', 'uuid-123', $date);
        $this->assertEquals('images/galeri/Mulk/uuid-123/2024/03', $imagePath);

        // Döküman yolu
        $docPath = $this->storageService->generatePath('document', 'tapu', 'Mulk', 'uuid-456', $date);
        $this->assertEquals('documents/tapu/Mulk/uuid-456/2024/03', $docPath);

        // Backup yolu
        $backupPath = $this->storageService->generatePath('backup', '', '', '', $date);
        $this->assertEquals('backups/2024/03/15', $backupPath);

        // Temp yolu
        $tempPath = $this->storageService->generatePath('temp', '', '', '', $date);
        $this->assertEquals('temp/2024/03/15', $tempPath);
    }

    public function test_generates_secure_filename()
    {
        $file = UploadedFile::fake()->create('test file.pdf', 1024, 'application/pdf');

        // Prefix ile
        $filename = $this->storageService->generateSecureFileName($file, 'tapu');
        $this->assertStringStartsWith('tapu_', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
        $this->assertStringContainsString(now()->format('Y-m-d'), $filename);

        // Orijinal adı koru
        $filename = $this->storageService->generateSecureFileName($file, 'doc', true);
        $this->assertStringContains('test_file', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    public function test_checks_chunked_upload_requirement()
    {
        Config::set('app.chunk_upload_threshold', 50 * 1024 * 1024); // 50MB

        // Küçük dosya
        $this->assertFalse($this->storageService->requiresChunkedUpload(10 * 1024 * 1024)); // 10MB

        // Büyük dosya
        $this->assertTrue($this->storageService->requiresChunkedUpload(100 * 1024 * 1024)); // 100MB
    }

    public function test_validates_file_security()
    {
        // Güvenli dosya
        $safeFile = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $result = $this->storageService->validateFileSecurity($safeFile);
        
        $this->assertTrue($result['safe']);
        $this->assertEmpty($result['errors']);

        // Tehlikeli uzantı
        $dangerousFile = UploadedFile::fake()->create('script.php', 1024, 'application/x-php');
        $result = $this->storageService->validateFileSecurity($dangerousFile);
        
        $this->assertFalse($result['safe']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Güvenlik riski', $result['errors'][0]);
    }

    public function test_cleans_up_old_files()
    {
        // Test dosyaları oluştur
        Storage::disk('local')->put('test/old_file.txt', 'content');
        Storage::disk('local')->put('test/new_file.txt', 'content');

        // Eski dosyanın tarihini değiştir (simüle)
        $oldTimestamp = now()->subDays(35)->timestamp;
        
        $result = $this->storageService->cleanupOldFiles('local', 'test', 30);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('deleted_count', $result);
        $this->assertArrayHasKey('total_size_freed', $result);
        $this->assertArrayHasKey('formatted_size', $result);
    }

    public function test_compresses_file()
    {
        $content = str_repeat('This is test content for compression. ', 100);
        Storage::disk('local')->put('test/compress_me.txt', $content);

        $result = $this->storageService->compressFile('local', 'test/compress_me.txt');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('compressed_path', $result);
        $this->assertArrayHasKey('compression_ratio', $result);
        $this->assertArrayHasKey('savings', $result);
        $this->assertGreaterThan(0, $result['compression_ratio']);
        
        Storage::disk('local')->assertExists($result['compressed_path']);
    }

    public function test_decompresses_file()
    {
        $originalContent = str_repeat('This is test content for compression. ', 100);
        $compressedContent = gzencode($originalContent, 9);
        
        Storage::disk('local')->put('test/compressed.txt.gz', $compressedContent);

        $result = $this->storageService->decompressFile('local', 'test/compressed.txt.gz');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('decompressed_path', $result);
        $this->assertArrayHasKey('decompressed_size', $result);
        
        Storage::disk('local')->assertExists($result['decompressed_path']);
        
        $decompressedContent = Storage::disk('local')->get($result['decompressed_path']);
        $this->assertEquals($originalContent, $decompressedContent);
    }

    public function test_backs_up_file()
    {
        $content = 'This is important content to backup';
        Storage::disk('local')->put('important/file.txt', $content);

        $result = $this->storageService->backupFile('local', 'important/file.txt');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('backup_path', $result);
        $this->assertArrayHasKey('backup_disk', $result);
        $this->assertArrayHasKey('file_size', $result);
        
        Storage::disk($result['backup_disk'])->assertExists($result['backup_path']);
        
        $backupContent = Storage::disk($result['backup_disk'])->get($result['backup_path']);
        $this->assertEquals($content, $backupContent);
    }

    public function test_gets_file_metadata()
    {
        $content = 'Test file content';
        Storage::disk('local')->put('test/metadata_test.txt', $content);

        $metadata = $this->storageService->getFileMetadata('local', 'test/metadata_test.txt');

        $this->assertTrue($metadata['exists']);
        $this->assertEquals(strlen($content), $metadata['size']);
        $this->assertArrayHasKey('last_modified', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
        $this->assertArrayHasKey('formatted_size', $metadata);
        $this->assertArrayHasKey('formatted_date', $metadata);
    }

    public function test_gets_file_metadata_for_nonexistent_file()
    {
        $metadata = $this->storageService->getFileMetadata('local', 'nonexistent/file.txt');

        $this->assertFalse($metadata['exists']);
    }

    public function test_gets_file_url_for_public_disk()
    {
        $url = $this->storageService->getFileUrl('public', 'images/test.jpg');
        
        $this->assertStringContainsString('storage/images/test.jpg', $url);
    }

    public function test_gets_disk_usage_report()
    {
        $report = $this->storageService->getDiskUsageReport();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('public', $report);
        $this->assertArrayHasKey('local', $report);
        
        foreach ($report as $diskName => $usage) {
            if (!isset($usage['error'])) {
                $this->assertArrayHasKey('type', $usage);
            }
        }
    }

    public function test_handles_missing_file_for_compression()
    {
        $result = $this->storageService->compressFile('local', 'nonexistent/file.txt');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('bulunamadı', $result['message']);
    }

    public function test_handles_missing_file_for_backup()
    {
        $result = $this->storageService->backupFile('local', 'nonexistent/file.txt');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('bulunamadı', $result['message']);
    }

    public function test_generates_different_filenames_for_same_file()
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $filename1 = $this->storageService->generateSecureFileName($file);
        $filename2 = $this->storageService->generateSecureFileName($file);

        $this->assertNotEquals($filename1, $filename2);
        $this->assertStringEndsWith('.pdf', $filename1);
        $this->assertStringEndsWith('.pdf', $filename2);
    }

    public function test_file_security_validation_with_large_file()
    {
        Config::set('app.max_file_size', 50 * 1024 * 1024); // 50MB

        $largeFile = UploadedFile::fake()->create('large.pdf', 100 * 1024, 'application/pdf'); // 100MB
        $result = $this->storageService->validateFileSecurity($largeFile);

        $this->assertFalse($result['safe']);
        $this->assertStringContainsString('çok büyük', $result['errors'][0]);
    }

    public function test_file_security_validation_with_invalid_filename()
    {
        $file = UploadedFile::fake()->create('file<>name.pdf', 1024, 'application/pdf');
        $result = $this->storageService->validateFileSecurity($file);

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('geçersiz karakterler', $result['warnings'][0]);
    }
}