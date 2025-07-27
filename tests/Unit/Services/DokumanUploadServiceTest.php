<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DokumanUploadService;
use App\Models\Dokuman;
use App\Enums\DokumanTipi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * DokumanUploadService Unit Testleri
 * 
 * Bu test sınıfı DokumanUploadService'in tüm özelliklerini test eder:
 * - Döküman yükleme işlemleri
 * - Güvenlik kontrolleri
 * - Dosya validasyonu
 * - Versiyonlama
 * - Toplu işlemler
 */
class DokumanUploadServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private DokumanUploadService $dokumanUploadService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dokumanUploadService = new DokumanUploadService();
        
        // Test için fake storage kullan
        Storage::fake('public');
        
        // Test kullanıcısı oluştur
        $user = \App\Models\User::factory()->create();
        Auth::login($user);
    }

    /** @test */
    public function basarili_dokuman_yukleme_testi()
    {
        $file = UploadedFile::fake()->create('test-dokuman.pdf', 1024, 'application/pdf');
        
        $sonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU,
            [
                'baslik' => 'Test Tapu Dökümanı',
                'aciklama' => 'Test açıklaması'
            ]
        );

        $this->assertTrue($sonuc['success']);
        $this->assertArrayHasKey('dokuman', $sonuc);
        $this->assertArrayHasKey('message', $sonuc);
        
        // Veritabanında kayıt oluşturuldu mu?
        $this->assertDatabaseHas('dokumanlar', [
            'documentable_type' => 'App\\Models\\Mulk\\Konut\\Daire',
            'documentable_id' => '1',
            'dokuman_tipi' => DokumanTipi::TAPU->value,
            'baslik' => 'Test Tapu Dökümanı',
            'aktif_mi' => true
        ]);
    }

    /** @test */
    public function gecersiz_dosya_tipi_reddi_testi()
    {
        // Executable dosya (güvenlik riski)
        $file = UploadedFile::fake()->create('zararlı.exe', 1024, 'application/x-executable');
        
        $sonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );

        $this->assertFalse($sonuc['success']);
        $this->assertArrayHasKey('errors', $sonuc);
        $this->assertStringContainsString('güvenlik', strtolower($sonuc['errors'][0]));
    }

    /** @test */
    public function buyuk_dosya_boyutu_reddi_testi()
    {
        // 150MB dosya (limit 100MB)
        $file = UploadedFile::fake()->create('buyuk-dokuman.pdf', 150 * 1024, 'application/pdf');
        
        $sonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );

        $this->assertFalse($sonuc['success']);
        $this->assertArrayHasKey('errors', $sonuc);
        $this->assertStringContainsString('boyut', strtolower($sonuc['errors'][0]));
    }

    /** @test */
    public function zararlı_dosya_adi_reddi_testi()
    {
        // Zararlı dosya adı
        $file = UploadedFile::fake()->create('../../../etc/passwd', 1024, 'application/pdf');
        
        $sonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );

        $this->assertFalse($sonuc['success']);
        $this->assertArrayHasKey('errors', $sonuc);
    }

    /** @test */
    public function pdf_icerik_kontrolu_testi()
    {
        // Geçerli PDF header'ı olmayan dosya
        $tempFile = tmpfile();
        fwrite($tempFile, 'Bu bir PDF değil');
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        
        $file = new UploadedFile($tempPath, 'sahte.pdf', 'application/pdf', null, true);
        
        $sonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );

        $this->assertFalse($sonuc['success']);
        $this->assertArrayHasKey('errors', $sonuc);
    }

    /** @test */
    public function zararlı_script_tespiti_testi()
    {
        // JavaScript içeren dosya
        $tempFile = tmpfile();
        fwrite($tempFile, '<script>alert("XSS")</script>');
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        
        $file = new UploadedFile($tempPath, 'zararlı.html', 'text/html', null, true);
        
        $sonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::DIGER
        );

        $this->assertFalse($sonuc['success']);
        $this->assertArrayHasKey('errors', $sonuc);
    }

    /** @test */
    public function duplicate_dosya_tespiti_testi()
    {
        $file1 = UploadedFile::fake()->create('dokuman.pdf', 1024, 'application/pdf');
        
        // İlk yükleme
        $sonuc1 = $this->dokumanUploadService->upload(
            $file1,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );
        
        $this->assertTrue($sonuc1['success']);
        
        // Aynı dosyayı tekrar yükle
        $file2 = UploadedFile::fake()->create('dokuman.pdf', 1024, 'application/pdf');
        
        $sonuc2 = $this->dokumanUploadService->upload(
            $file2,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );

        $this->assertFalse($sonuc2['success']);
        $this->assertArrayHasKey('errors', $sonuc2);
        $this->assertStringContainsString('daha önce yüklenmiş', $sonuc2['errors'][0]);
    }

    /** @test */
    public function toplu_dokuman_yukleme_testi()
    {
        $files = [
            UploadedFile::fake()->create('dokuman1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('dokuman2.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('dokuman3.pdf', 1024, 'application/pdf'),
        ];
        
        $sonuc = $this->dokumanUploadService->uploadMultiple(
            $files,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );

        $this->assertArrayHasKey('results', $sonuc);
        $this->assertArrayHasKey('summary', $sonuc);
        
        $summary = $sonuc['summary'];
        $this->assertEquals(3, $summary['total']);
        $this->assertEquals(3, $summary['success']);
        $this->assertEquals(0, $summary['error']);
        
        // Her döküman için sonuç var mı?
        $this->assertCount(3, $sonuc['results']);
        foreach ($sonuc['results'] as $result) {
            $this->assertTrue($result['success']);
        }
    }

    /** @test */
    public function versiyon_guncelleme_testi()
    {
        // İlk dökümanı yükle
        $file1 = UploadedFile::fake()->create('dokuman-v1.pdf', 1024, 'application/pdf');
        
        $uploadSonuc = $this->dokumanUploadService->upload(
            $file1,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );
        
        $this->assertTrue($uploadSonuc['success']);
        $eskiDokuman = $uploadSonuc['dokuman'];
        
        // Yeni versiyon yükle
        $file2 = UploadedFile::fake()->create('dokuman-v2.pdf', 2048, 'application/pdf');
        
        $versionSonuc = $this->dokumanUploadService->updateVersion(
            $eskiDokuman,
            $file2,
            ['baslik' => 'Güncellenmiş Döküman']
        );
        
        $this->assertTrue($versionSonuc['success']);
        $this->assertArrayHasKey('dokuman', $versionSonuc);
        
        $yeniDokuman = $versionSonuc['dokuman'];
        $this->assertEquals($eskiDokuman->versiyon + 1, $yeniDokuman->versiyon);
        $this->assertEquals('Güncellenmiş Döküman', $yeniDokuman->baslik);
    }

    /** @test */
    public function dokuman_silme_testi()
    {
        // Döküman yükle
        $file = UploadedFile::fake()->create('silinecek-dokuman.pdf', 1024, 'application/pdf');
        
        $uploadSonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );
        
        $this->assertTrue($uploadSonuc['success']);
        $dokuman = $uploadSonuc['dokuman'];
        
        // Sil
        $silmeSonuc = $this->dokumanUploadService->delete($dokuman);
        
        $this->assertTrue($silmeSonuc['success']);
        
        // Soft delete kontrolü
        $this->assertDatabaseHas('dokumanlar', [
            'id' => $dokuman->id,
            'aktif_mi' => false
        ]);
    }

    /** @test */
    public function dokuman_geri_yukleme_testi()
    {
        // Döküman yükle ve sil
        $file = UploadedFile::fake()->create('geri-yuklenecek.pdf', 1024, 'application/pdf');
        
        $uploadSonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );
        
        $dokuman = $uploadSonuc['dokuman'];
        $this->dokumanUploadService->delete($dokuman);
        
        // Geri yükle
        $restoreSonuc = $this->dokumanUploadService->restore($dokuman);
        
        $this->assertTrue($restoreSonuc['success']);
        
        // Aktif duruma geldi mi?
        $this->assertDatabaseHas('dokumanlar', [
            'id' => $dokuman->id,
            'aktif_mi' => true
        ]);
    }

    /** @test */
    public function guvenli_dosya_adi_olusturma_testi()
    {
        // Türkçe karakterler ve özel karakterler içeren dosya adı
        $file = UploadedFile::fake()->create('çok önemli döküman!@#.pdf', 1024, 'application/pdf');
        
        $sonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );

        $this->assertTrue($sonuc['success']);
        
        // Dosya adı güvenli karakterlere dönüştürülmüş mü?
        $dokuman = $sonuc['dokuman'];
        $this->assertStringContainsString('cok_onemli_dokuman', $dokuman->dosya_adi);
        $this->assertStringNotContainsString('!@#', $dokuman->dosya_adi);
    }

    /** @test */
    public function metadata_cikarma_testi()
    {
        $file = UploadedFile::fake()->create('test-dokuman.pdf', 1024, 'application/pdf');
        
        $sonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );

        $this->assertTrue($sonuc['success']);
        
        $dokuman = $sonuc['dokuman'];
        $metadata = $dokuman->metadata;
        
        $this->assertArrayHasKey('orijinal_ad', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
        $this->assertArrayHasKey('boyut', $metadata);
        $this->assertArrayHasKey('upload_tarihi', $metadata);
        $this->assertArrayHasKey('upload_ip', $metadata);
        $this->assertArrayHasKey('user_agent', $metadata);
        $this->assertArrayHasKey('hash', $metadata);
        
        $this->assertEquals('test-dokuman.pdf', $metadata['orijinal_ad']);
        $this->assertEquals('application/pdf', $metadata['mime_type']);
    }

    /** @test */
    public function mulk_tipine_gore_uygun_dokuman_tipleri_testi()
    {
        $arsaTipleri = $this->dokumanUploadService->getAvailableTypesForMulk('App\\Models\\Mulk\\Arsa\\TicariArsa');
        $konutTipleri = $this->dokumanUploadService->getAvailableTypesForMulk('App\\Models\\Mulk\\Konut\\Daire');
        
        $this->assertIsArray($arsaTipleri);
        $this->assertIsArray($konutTipleri);
        
        // Arsa için harita dökümanları olmalı
        $this->assertContains(DokumanTipi::HARITA_UYDU, $arsaTipleri);
        
        // Konut için farklı dökümanlar olmalı
        $this->assertContains(DokumanTipi::TAPU, $konutTipleri);
    }

    /** @test */
    public function dokuman_istatistikleri_testi()
    {
        // Test dökümanları yükle
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->create("dokuman{$i}.pdf", 1024, 'application/pdf');
            $this->dokumanUploadService->upload(
                $file,
                'App\\Models\\Mulk\\Konut\\Daire',
                '1',
                DokumanTipi::TAPU
            );
        }
        
        $istatistikler = $this->dokumanUploadService->getStatistics(
            'App\\Models\\Mulk\\Konut\\Daire',
            '1'
        );
        
        $this->assertArrayHasKey('total_count', $istatistikler);
        $this->assertArrayHasKey('total_size', $istatistikler);
        $this->assertArrayHasKey('by_type', $istatistikler);
        $this->assertArrayHasKey('recent_uploads', $istatistikler);
        
        $this->assertEquals(3, $istatistikler['total_count']);
        $this->assertGreaterThan(0, $istatistikler['total_size']);
    }

    /** @test */
    public function dosya_boyutu_formatlama_testi()
    {
        $service = new DokumanUploadService();
        
        // Reflection kullanarak private metoda erişim
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('dosyaBoyutuFormatla');
        $method->setAccessible(true);
        
        $this->assertEquals('1.00 KB', $method->invoke($service, 1024));
        $this->assertEquals('1.00 MB', $method->invoke($service, 1024 * 1024));
        $this->assertEquals('1.00 GB', $method->invoke($service, 1024 * 1024 * 1024));
    }

    /** @test */
    public function virus_tarama_mock_testi()
    {
        // Bu test gerçek virus tarama yapmaz, sadece metodun çalıştığını kontrol eder
        $file = UploadedFile::fake()->create('temiz-dokuman.pdf', 1024, 'application/pdf');
        
        $sonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );

        // Virus tarama aktif olmadığı için başarılı olmalı
        $this->assertTrue($sonuc['success']);
    }

    /** @test */
    public function eski_dosyalari_temizleme_testi()
    {
        // Eski tarihli döküman oluştur
        $file = UploadedFile::fake()->create('eski-dokuman.pdf', 1024, 'application/pdf');
        
        $uploadSonuc = $this->dokumanUploadService->upload(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            DokumanTipi::TAPU
        );
        
        $dokuman = $uploadSonuc['dokuman'];
        
        // Dökümanı sil (soft delete)
        $this->dokumanUploadService->delete($dokuman);
        
        // Updated_at'i 35 gün öncesine ayarla
        $dokuman->update(['updated_at' => now()->subDays(35)]);
        
        // Temizleme işlemi
        $temizlikSonuc = $this->dokumanUploadService->eskiDosyalariTemizle(30);
        
        $this->assertArrayHasKey('silinen_sayisi', $temizlikSonuc);
        $this->assertArrayHasKey('toplam_boyut', $temizlikSonuc);
        $this->assertEquals(1, $temizlikSonuc['silinen_sayisi']);
    }

    protected function tearDown(): void
    {
        // Test sonrası temizlik
        Storage::fake('public');
        parent::tearDown();
    }
}