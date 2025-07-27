<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ResimUploadService;
use App\Models\Resim;
use App\Enums\ResimKategorisi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * ResimUploadService Unit Testleri
 * 
 * Bu test sınıfı ResimUploadService'in tüm özelliklerini test eder:
 * - Resim yükleme işlemleri
 * - Güvenlik kontrolleri
 * - Boyutlandırma ve optimizasyon
 * - Metadata çıkarma
 * - Toplu işlemler
 */
class ResimUploadServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ResimUploadService $resimUploadService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->resimUploadService = new ResimUploadService();
        
        // Test için fake storage kullan
        Storage::fake('public');
        
        // Test kullanıcısı oluştur
        $user = \App\Models\User::factory()->create();
        Auth::login($user);
    }

    /** @test */
    public function basarili_resim_yukleme_testi()
    {
        // Test resmi oluştur
        $file = UploadedFile::fake()->image('test-resim.jpg', 800, 600)->size(1024);
        
        $sonuc = $this->resimUploadService->uploadResim(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertArrayHasKey('resim', $sonuc);
        $this->assertArrayHasKey('orijinal_yol', $sonuc);
        $this->assertArrayHasKey('boyutlar', $sonuc);
        $this->assertArrayHasKey('metadata', $sonuc);
        
        // Veritabanında kayıt oluşturuldu mu?
        $this->assertDatabaseHas('resimler', [
            'imagable_type' => 'App\\Models\\Mulk\\Konut\\Daire',
            'imagable_id' => '1',
            'kategori' => ResimKategorisi::GALERI_SALON->value,
            'aktif_mi' => true
        ]);
    }

    /** @test */
    public function gecersiz_dosya_tipi_reddi_testi()
    {
        // Geçersiz dosya tipi (PDF)
        $file = UploadedFile::fake()->create('dokuman.pdf', 1024, 'application/pdf');
        
        $sonuc = $this->resimUploadService->uploadResim(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $this->assertFalse($sonuc['basarili']);
        $this->assertArrayHasKey('hatalar', $sonuc);
        $this->assertContains('Desteklenmeyen dosya formatı', $sonuc['hatalar'][0]);
    }

    /** @test */
    public function buyuk_dosya_boyutu_reddi_testi()
    {
        // 15MB resim (limit 10MB)
        $file = UploadedFile::fake()->image('buyuk-resim.jpg', 2000, 2000)->size(15 * 1024);
        
        $sonuc = $this->resimUploadService->uploadResim(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $this->assertFalse($sonuc['basarili']);
        $this->assertArrayHasKey('hatalar', $sonuc);
        $this->assertStringContainsString('Dosya boyutu çok büyük', $sonuc['hatalar'][0]);
    }

    /** @test */
    public function zararlı_icerik_tespiti_testi()
    {
        // Zararlı içerik içeren fake dosya
        $tempFile = tmpfile();
        fwrite($tempFile, '<?php echo "Zararlı kod"; ?>');
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        
        $file = new UploadedFile($tempPath, 'zararlı.jpg', 'image/jpeg', null, true);
        
        $sonuc = $this->resimUploadService->uploadResim(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $this->assertFalse($sonuc['basarili']);
        $this->assertArrayHasKey('hatalar', $sonuc);
    }

    /** @test */
    public function farkli_boyutlarda_resim_olusturma_testi()
    {
        $file = UploadedFile::fake()->image('test-resim.jpg', 1200, 800)->size(2048);
        
        $sonuc = $this->resimUploadService->uploadResim(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON,
            ['thumbnail', 'medium', 'large']
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertArrayHasKey('boyutlar', $sonuc);
        
        $boyutlar = $sonuc['boyutlar'];
        $this->assertArrayHasKey('thumbnail', $boyutlar);
        $this->assertArrayHasKey('medium', $boyutlar);
        $this->assertArrayHasKey('large', $boyutlar);
        
        // Her boyut için gerekli bilgiler mevcut mu?
        foreach (['thumbnail', 'medium', 'large'] as $boyut) {
            $this->assertArrayHasKey('yol', $boyutlar[$boyut]);
            $this->assertArrayHasKey('url', $boyutlar[$boyut]);
            $this->assertArrayHasKey('genislik', $boyutlar[$boyut]);
            $this->assertArrayHasKey('yukseklik', $boyutlar[$boyut]);
        }
    }

    /** @test */
    public function metadata_cikarma_testi()
    {
        $file = UploadedFile::fake()->image('test-resim.jpg', 800, 600)->size(1024);
        
        $sonuc = $this->resimUploadService->uploadResim(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertArrayHasKey('metadata', $sonuc);
        
        $metadata = $sonuc['metadata'];
        $this->assertArrayHasKey('orijinal_ad', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
        $this->assertArrayHasKey('boyut', $metadata);
        $this->assertArrayHasKey('upload_tarihi', $metadata);
        $this->assertArrayHasKey('genislik', $metadata);
        $this->assertArrayHasKey('yukseklik', $metadata);
        
        $this->assertEquals('test-resim.jpg', $metadata['orijinal_ad']);
        $this->assertEquals('image/jpeg', $metadata['mime_type']);
    }

    /** @test */
    public function toplu_resim_yukleme_testi()
    {
        $files = [
            UploadedFile::fake()->image('resim1.jpg', 800, 600)->size(1024),
            UploadedFile::fake()->image('resim2.jpg', 800, 600)->size(1024),
            UploadedFile::fake()->image('resim3.jpg', 800, 600)->size(1024),
        ];
        
        $sonuc = $this->resimUploadService->topluResimYukle(
            $files,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $this->assertArrayHasKey('sonuclar', $sonuc);
        $this->assertArrayHasKey('ozet', $sonuc);
        
        $ozet = $sonuc['ozet'];
        $this->assertEquals(3, $ozet['toplam']);
        $this->assertEquals(3, $ozet['basarili']);
        $this->assertEquals(0, $ozet['hatali']);
        
        // Her resim için sonuç var mı?
        $this->assertCount(3, $sonuc['sonuclar']);
        foreach ($sonuc['sonuclar'] as $sonucItem) {
            $this->assertTrue($sonucItem['basarili']);
        }
    }

    /** @test */
    public function toplu_yukleme_kismen_basarili_testi()
    {
        $files = [
            UploadedFile::fake()->image('resim1.jpg', 800, 600)->size(1024), // Başarılı
            UploadedFile::fake()->create('dokuman.pdf', 1024, 'application/pdf'), // Başarısız
            UploadedFile::fake()->image('resim3.jpg', 800, 600)->size(1024), // Başarılı
        ];
        
        $sonuc = $this->resimUploadService->topluResimYukle(
            $files,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $ozet = $sonuc['ozet'];
        $this->assertEquals(3, $ozet['toplam']);
        $this->assertEquals(2, $ozet['basarili']);
        $this->assertEquals(1, $ozet['hatali']);
    }

    /** @test */
    public function guvenli_dosya_adi_olusturma_testi()
    {
        // Türkçe karakterler ve özel karakterler içeren dosya adı
        $file = UploadedFile::fake()->image('çok güzel resim!@#.jpg', 800, 600)->size(1024);
        
        $sonuc = $this->resimUploadService->uploadResim(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $this->assertTrue($sonuc['basarili']);
        
        // Dosya adı güvenli karakterlere dönüştürülmüş mü?
        $resim = $sonuc['resim'];
        $this->assertStringContainsString('cok_guzel_resim', $resim->dosya_adi);
        $this->assertStringNotContainsString('!@#', $resim->dosya_adi);
    }

    /** @test */
    public function resim_silme_testi()
    {
        // Önce resim yükle
        $file = UploadedFile::fake()->image('silinecek-resim.jpg', 800, 600)->size(1024);
        
        $uploadSonuc = $this->resimUploadService->uploadResim(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $this->assertTrue($uploadSonuc['basarili']);
        $resim = $uploadSonuc['resim'];
        
        // Şimdi sil
        $silmeSonucu = $this->resimUploadService->resimSil($resim);
        
        $this->assertTrue($silmeSonucu);
        
        // Veritabanından silinmiş mi?
        $this->assertDatabaseMissing('resimler', [
            'id' => $resim->id
        ]);
    }

    /** @test */
    public function resim_boyutlari_alma_testi()
    {
        $boyutlar = $this->resimUploadService->getResimBoyutlari();
        
        $this->assertIsArray($boyutlar);
        $this->assertArrayHasKey('thumbnail', $boyutlar);
        $this->assertArrayHasKey('small', $boyutlar);
        $this->assertArrayHasKey('medium', $boyutlar);
        $this->assertArrayHasKey('large', $boyutlar);
        $this->assertArrayHasKey('original', $boyutlar);
        
        // Her boyut için width, height, quality bilgisi var mı?
        foreach (['thumbnail', 'small', 'medium', 'large'] as $boyut) {
            $this->assertArrayHasKey('width', $boyutlar[$boyut]);
            $this->assertArrayHasKey('height', $boyutlar[$boyut]);
            $this->assertArrayHasKey('quality', $boyutlar[$boyut]);
        }
    }

    /** @test */
    public function izin_verilen_mime_types_alma_testi()
    {
        $mimeTypes = $this->resimUploadService->getIzinVerilenMimeTypes();
        
        $this->assertIsArray($mimeTypes);
        $this->assertContains('image/jpeg', $mimeTypes);
        $this->assertContains('image/png', $mimeTypes);
        $this->assertContains('image/gif', $mimeTypes);
        $this->assertNotContains('application/pdf', $mimeTypes);
    }

    /** @test */
    public function max_dosya_boyutu_alma_testi()
    {
        $maxBoyut = $this->resimUploadService->getMaxDosyaBoyutu();
        
        $this->assertIsInt($maxBoyut);
        $this->assertEquals(10 * 1024 * 1024, $maxBoyut); // 10MB
    }

    /** @test */
    public function resim_url_olusturma_testi()
    {
        // Mock resim oluştur
        $resim = new Resim([
            'boyutlar' => [
                'medium' => [
                    'url' => 'http://localhost/storage/resimler/test/medium/resim.jpg'
                ],
                'thumbnail' => [
                    'url' => 'http://localhost/storage/resimler/test/thumbnail/resim.jpg'
                ]
            ],
            'dosya_yolu' => 'resimler/test/original/resim.jpg'
        ]);
        
        $mediumUrl = $this->resimUploadService->resimUrlOlustur($resim, 'medium');
        $thumbnailUrl = $this->resimUploadService->resimUrlOlustur($resim, 'thumbnail');
        
        $this->assertEquals('http://localhost/storage/resimler/test/medium/resim.jpg', $mediumUrl);
        $this->assertEquals('http://localhost/storage/resimler/test/thumbnail/resim.jpg', $thumbnailUrl);
    }

    /** @test */
    public function resim_istatistikleri_alma_testi()
    {
        // Test resimleri yükle
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->image("resim{$i}.jpg", 800, 600)->size(1024);
            $this->resimUploadService->uploadResim(
                $file,
                'App\\Models\\Mulk\\Konut\\Daire',
                '1',
                ResimKategorisi::GALERI_SALON
            );
        }
        
        $istatistikler = $this->resimUploadService->resimIstatistikleriAl(
            'App\\Models\\Mulk\\Konut\\Daire',
            '1'
        );
        
        $this->assertArrayHasKey('toplam_resim', $istatistikler);
        $this->assertArrayHasKey('toplam_boyut', $istatistikler);
        $this->assertArrayHasKey('toplam_boyut_formatli', $istatistikler);
        $this->assertArrayHasKey('ortalama_boyut', $istatistikler);
        $this->assertArrayHasKey('kategorilere_bolum', $istatistikler);
        
        $this->assertEquals(3, $istatistikler['toplam_resim']);
        $this->assertGreaterThan(0, $istatistikler['toplam_boyut']);
    }

    /** @test */
    public function watermark_ekleme_testi()
    {
        // Watermark dosyası oluştur
        $watermarkPath = storage_path('app/watermarks');
        if (!is_dir($watermarkPath)) {
            mkdir($watermarkPath, 0755, true);
        }
        
        $watermarkFile = $watermarkPath . '/logo.png';
        copy(UploadedFile::fake()->image('logo.png', 100, 100)->getPathname(), $watermarkFile);
        
        $file = UploadedFile::fake()->image('test-resim.jpg', 800, 600)->size(1024);
        
        $sonuc = $this->resimUploadService->uploadResim(
            $file,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON,
            null,
            true // Watermark ekle
        );

        $this->assertTrue($sonuc['basarili']);
        
        // Cleanup
        if (file_exists($watermarkFile)) {
            unlink($watermarkFile);
        }
    }

    /** @test */
    public function kategori_bazli_filtreleme_testi()
    {
        // Farklı kategorilerde resimler yükle
        $kategoriler = [
            ResimKategorisi::GALERI_SALON,
            ResimKategorisi::GALERI_MUTFAK,
            ResimKategorisi::GALERI_BANYO
        ];
        
        foreach ($kategoriler as $kategori) {
            $file = UploadedFile::fake()->image("resim-{$kategori->value}.jpg", 800, 600)->size(1024);
            $this->resimUploadService->uploadResim(
                $file,
                'App\\Models\\Mulk\\Konut\\Daire',
                '1',
                $kategori
            );
        }
        
        // Salon kategorisindeki resimleri kontrol et
        $salonResimleri = Resim::where('imagable_type', 'App\\Models\\Mulk\\Konut\\Daire')
                              ->where('imagable_id', '1')
                              ->where('kategori', ResimKategorisi::GALERI_SALON)
                              ->get();
        
        $this->assertCount(1, $salonResimleri);
        $this->assertEquals(ResimKategorisi::GALERI_SALON, $salonResimleri->first()->kategori);
    }

    protected function tearDown(): void
    {
        // Test sonrası temizlik
        Storage::fake('public');
        parent::tearDown();
    }
}