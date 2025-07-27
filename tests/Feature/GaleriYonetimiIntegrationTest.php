<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GaleriService;
use App\Services\ResimUploadService;
use App\Models\Resim;
use App\Models\User;
use App\Enums\ResimKategorisi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Galeri Yönetimi Entegrasyon Testleri
 * 
 * Bu test sınıfı galeri yönetimi sisteminin tüm bileşenlerinin
 * birlikte çalışmasını test eder.
 */
class GaleriYonetimiIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private GaleriService $galeriService;
    private ResimUploadService $resimUploadService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Test için fake storage kullan
        Storage::fake('public');
        
        // Test kullanıcısı oluştur
        $this->user = User::factory()->create();
        Auth::login($this->user);
        
        // Servisleri başlat
        $this->resimUploadService = new ResimUploadService();
        $this->galeriService = new GaleriService($this->resimUploadService);
    }

    /** @test */
    public function tam_galeri_yonetimi_workflow_testi()
    {
        $mulkType = 'App\\Models\\Mulk\\Konut\\Daire';
        $mulkId = '1';

        // 1. Galeri oluştur
        $galeriOlusturma = $this->galeriService->galeriOlustur($mulkType, $mulkId);
        $this->assertTrue($galeriOlusturma['basarili']);

        // 2. Resimler yükle
        $resimDosyalari = [
            UploadedFile::fake()->image('dis-cephe.jpg', 1200, 800)->size(2048),
            UploadedFile::fake()->image('salon.jpg', 1000, 600)->size(1536),
            UploadedFile::fake()->image('mutfak.jpg', 800, 600)->size(1024),
            UploadedFile::fake()->image('banyo.jpg', 600, 400)->size(768),
        ];

        $kategoriler = [
            ResimKategorisi::GALERI_DIS_CEPHE,
            ResimKategorisi::GALERI_SALON,
            ResimKategorisi::GALERI_MUTFAK,
            ResimKategorisi::GALERI_BANYO,
        ];

        $yuklenmisDosyalar = [];
        foreach ($resimDosyalari as $index => $dosya) {
            $uploadSonuc = $this->resimUploadService->uploadResim(
                $dosya,
                $mulkType,
                $mulkId,
                $kategoriler[$index],
                null,
                false,
                ['baslik' => "Test Resim " . ($index + 1)]
            );
            
            $this->assertTrue($uploadSonuc['basarili']);
            $yuklenmisDosyalar[] = $uploadSonuc['resim'];
        }

        // 3. Galeri resimlerini getir
        $galeriResimleri = $this->galeriService->galeriResimleriGetir($mulkType, $mulkId);
        $this->assertTrue($galeriResimleri['basarili']);
        $this->assertEquals(4, $galeriResimleri['toplam']);

        // 4. Ana resim belirle (dış cephe resmini)
        $disCepheResmi = collect($yuklenmisDosyalar)->firstWhere('kategori', ResimKategorisi::GALERI_DIS_CEPHE);
        $anaResimBelirle = $this->galeriService->anaResimBelirle($disCepheResmi->id, $mulkType, $mulkId);
        $this->assertTrue($anaResimBelirle['basarili']);

        // 5. Resim sıralamasını güncelle
        $resimIdleri = collect($yuklenmisDosyalar)->pluck('id')->toArray();
        shuffle($resimIdleri); // Karıştır
        
        $siralamaGuncelle = $this->galeriService->resimSiralamasiGuncelle($resimIdleri);
        $this->assertTrue($siralamaGuncelle['basarili']);

        // 6. Galeri istatistiklerini kontrol et
        $istatistikler = $this->galeriService->galeriIstatistikleri($mulkType, $mulkId);
        $this->assertTrue($istatistikler['basarili']);
        $this->assertEquals(4, $istatistikler['toplam_resim']);
        $this->assertTrue($istatistikler['galeri_tamamlandi']);
        $this->assertNotNull($istatistikler['ana_resim']);

        // 7. Kategori bazlı filtreleme
        $salonResimleri = $this->galeriService->galeriResimleriGetir(
            $mulkType, 
            $mulkId, 
            ResimKategorisi::GALERI_SALON
        );
        $this->assertTrue($salonResimleri['basarili']);
        $this->assertEquals(1, $salonResimleri['toplam']);

        // 8. Organizasyon önerilerini al
        $oneriler = $this->galeriService->galeriOrganizasyonuOner($mulkType, $mulkId);
        $this->assertTrue($oneriler['basarili']);
        // Galeri tamamlandığı için çok az öneri olmalı
        $this->assertLessThanOrEqual(2, $oneriler['oneri_sayisi']);

        // 9. Bir resmi sil
        $silinecekResim = $yuklenmisDosyalar[3]; // Son resim
        $resimSil = $this->resimUploadService->resimSil($silinecekResim);
        $this->assertTrue($resimSil);

        // 10. Final istatistikleri kontrol et
        $finalIstatistikler = $this->galeriService->galeriIstatistikleri($mulkType, $mulkId);
        $this->assertTrue($finalIstatistikler['basarili']);
        $this->assertEquals(3, $finalIstatistikler['toplam_resim']);
    }

    /** @test */
    public function toplu_resim_yukleme_ve_yonetimi_testi()
    {
        $mulkType = 'App\\Models\\Mulk\\Isyeri\\Ofis';
        $mulkId = '2';

        // Galeri oluştur
        $this->galeriService->galeriOlustur($mulkType, $mulkId);

        // Toplu resim yükleme
        $resimDosyalari = [
            UploadedFile::fake()->image('ofis1.jpg', 800, 600)->size(1024),
            UploadedFile::fake()->image('ofis2.jpg', 800, 600)->size(1024),
            UploadedFile::fake()->image('ofis3.jpg', 800, 600)->size(1024),
            UploadedFile::fake()->image('ofis4.jpg', 800, 600)->size(1024),
            UploadedFile::fake()->image('ofis5.jpg', 800, 600)->size(1024),
        ];

        $topluUpload = $this->resimUploadService->topluResimYukle(
            $resimDosyalari,
            $mulkType,
            $mulkId,
            ResimKategorisi::GALERI_OFIS
        );

        $this->assertEquals(5, $topluUpload['ozet']['toplam']);
        $this->assertEquals(5, $topluUpload['ozet']['basarili']);
        $this->assertEquals(0, $topluUpload['ozet']['hatali']);

        // Yüklenen resimlerin ID'lerini al
        $resimIdleri = [];
        foreach ($topluUpload['sonuclar'] as $sonuc) {
            if ($sonuc['basarili']) {
                $resimIdleri[] = $sonuc['resim']->id;
            }
        }

        // Toplu silme testi
        $ilkUcResim = array_slice($resimIdleri, 0, 3);
        $topluSilme = $this->galeriService->topluResimSil($ilkUcResim);
        
        $this->assertTrue($topluSilme['basarili']);
        $this->assertEquals(3, $topluSilme['silinen']);
        $this->assertEquals(0, $topluSilme['hatali']);

        // Kalan resim sayısını kontrol et
        $finalResimleri = $this->galeriService->galeriResimleriGetir($mulkType, $mulkId);
        $this->assertEquals(2, $finalResimleri['toplam']);
    }

    /** @test */
    public function farkli_mulk_tipleri_icin_galeri_kurallari_testi()
    {
        $mulkTipleri = [
            'App\\Models\\Mulk\\Konut\\Villa' => [
                'aktif' => true,
                'min_resim' => 3,
                'max_resim' => 50
            ],
            'App\\Models\\Mulk\\Isyeri\\Magaza' => [
                'aktif' => true,
                'min_resim' => 2,
                'max_resim' => 30
            ],
            'App\\Models\\Mulk\\TuristikTesis\\Hotel' => [
                'aktif' => true,
                'min_resim' => 5,
                'max_resim' => 100
            ],
            'App\\Models\\Mulk\\Arsa\\TicariArsa' => [
                'aktif' => false,
                'min_resim' => 0,
                'max_resim' => 0
            ]
        ];

        foreach ($mulkTipleri as $mulkType => $beklenenKurallar) {
            $kurallar = $this->galeriService->galeriKurallariniGetir($mulkType);
            
            $this->assertTrue($kurallar['basarili']);
            
            if ($beklenenKurallar['aktif']) {
                $this->assertTrue($kurallar['kurallar']['galeri_aktif']);
                $this->assertEquals($beklenenKurallar['min_resim'], $kurallar['kurallar']['min_resim']);
                $this->assertEquals($beklenenKurallar['max_resim'], $kurallar['kurallar']['max_resim']);
            } else {
                $this->assertFalse($kurallar['kurallar']['galeri_aktif']);
            }
        }
    }

    /** @test */
    public function galeri_organizasyon_onerileri_detay_testi()
    {
        $mulkType = 'App\\Models\\Mulk\\Konut\\Daire';
        $mulkId = '3';

        // Galeri oluştur
        $this->galeriService->galeriOlustur($mulkType, $mulkId);

        // Eksik kategorilerle resim yükle (sadece salon, mutfak eksik)
        $disCepheResmi = $this->resimUploadService->uploadResim(
            UploadedFile::fake()->image('dis-cephe.jpg', 800, 600)->size(1024),
            $mulkType,
            $mulkId,
            ResimKategorisi::GALERI_DIS_CEPHE,
            null,
            false,
            ['sira' => 0] // Sıralanmamış
        );

        $this->assertTrue($disCepheResmi['basarili']);

        // Organizasyon önerilerini al
        $oneriler = $this->galeriService->galeriOrganizasyonuOner($mulkType, $mulkId);
        
        $this->assertTrue($oneriler['basarili']);
        $this->assertGreaterThan(0, $oneriler['oneri_sayisi']);

        $oneriTipleri = collect($oneriler['oneriler'])->pluck('tip')->toArray();
        
        // Ana resim önerisi olmalı
        $this->assertContains('ana_resim', $oneriTipleri);
        
        // Eksik kategori önerileri olmalı
        $this->assertContains('eksik_kategori', $oneriTipleri);
        
        // Sıralama önerisi olmalı
        $this->assertContains('siralama', $oneriTipleri);

        // Eksik kategorileri kontrol et
        $eksikKategoriOnerileri = collect($oneriler['oneriler'])
            ->where('tip', 'eksik_kategori')
            ->pluck('kategori')
            ->toArray();
        
        $this->assertContains(ResimKategorisi::GALERI_SALON->value, $eksikKategoriOnerileri);
        $this->assertContains(ResimKategorisi::GALERI_MUTFAK->value, $eksikKategoriOnerileri);
    }

    /** @test */
    public function resim_metadata_ve_boyutlandirma_testi()
    {
        $mulkType = 'App\\Models\\Mulk\\Konut\\Daire';
        $mulkId = '4';

        // Büyük resim yükle
        $buyukResim = UploadedFile::fake()->image('buyuk-resim.jpg', 2400, 1600)->size(4096);
        
        $uploadSonuc = $this->resimUploadService->uploadResim(
            $buyukResim,
            $mulkType,
            $mulkId,
            ResimKategorisi::GALERI_SALON,
            ['thumbnail', 'small', 'medium', 'large', 'original']
        );

        $this->assertTrue($uploadSonuc['basarili']);
        
        // Metadata kontrolü
        $metadata = $uploadSonuc['metadata'];
        $this->assertEquals('buyuk-resim.jpg', $metadata['orijinal_ad']);
        $this->assertEquals('image/jpeg', $metadata['mime_type']);
        $this->assertEquals(2400, $metadata['genislik']);
        $this->assertEquals(1600, $metadata['yukseklik']);

        // Boyutlandırma kontrolü
        $boyutlar = $uploadSonuc['boyutlar'];
        $this->assertArrayHasKey('thumbnail', $boyutlar);
        $this->assertArrayHasKey('small', $boyutlar);
        $this->assertArrayHasKey('medium', $boyutlar);
        $this->assertArrayHasKey('large', $boyutlar);
        $this->assertArrayHasKey('original', $boyutlar);

        // Thumbnail boyutu doğru mu?
        $this->assertEquals(150, $boyutlar['thumbnail']['genislik']);
        $this->assertEquals(150, $boyutlar['thumbnail']['yukseklik']);

        // Medium boyutu doğru mu?
        $this->assertLessThanOrEqual(800, $boyutlar['medium']['genislik']);
        $this->assertLessThanOrEqual(600, $boyutlar['medium']['yukseklik']);
    }

    /** @test */
    public function galeri_istatistikleri_hesaplama_testi()
    {
        $mulkType = 'App\\Models\\Mulk\\TuristikTesis\\Hotel';
        $mulkId = '5';

        // Galeri oluştur
        $this->galeriService->galeriOlustur($mulkType, $mulkId);

        // Farklı kategorilerde resimler yükle
        $kategoriler = [
            ResimKategorisi::GALERI_DIS_CEPHE,
            ResimKategorisi::GALERI_RESEPSIYON,
            ResimKategorisi::GALERI_ODA,
            ResimKategorisi::GALERI_ODA, // İkinci oda resmi
            ResimKategorisi::GALERI_RESTORAN,
            ResimKategorisi::GALERI_HAVUZ,
        ];

        foreach ($kategoriler as $index => $kategori) {
            $resim = UploadedFile::fake()->image("resim{$index}.jpg", 800, 600)->size(1024);
            $uploadSonuc = $this->resimUploadService->uploadResim(
                $resim,
                $mulkType,
                $mulkId,
                $kategori
            );
            $this->assertTrue($uploadSonuc['basarili']);
        }

        // İstatistikleri al
        $istatistikler = $this->galeriService->galeriIstatistikleri($mulkType, $mulkId);
        
        $this->assertTrue($istatistikler['basarili']);
        $this->assertEquals(6, $istatistikler['toplam_resim']);
        $this->assertEquals(5, $istatistikler['min_resim']);
        $this->assertEquals(100, $istatistikler['max_resim']);
        $this->assertEquals(6.0, $istatistikler['doluluk_orani']); // 6/100 * 100
        $this->assertTrue($istatistikler['galeri_tamamlandi']); // Min resim sayısını geçti

        // Kategori dağılımı doğru mu?
        $kategoriDagilimi = $istatistikler['kategori_dagilimi'];
        $this->assertEquals(2, $kategoriDagilimi[ResimKategorisi::GALERI_ODA->value]); // 2 oda resmi
        $this->assertEquals(1, $kategoriDagilimi[ResimKategorisi::GALERI_HAVUZ->value]);
    }

    /** @test */
    public function hata_durumlarinda_graceful_handling_testi()
    {
        // Olmayan resim için ana resim belirleme
        $sonuc = $this->galeriService->anaResimBelirle(999, 'App\\Models\\Test', '1');
        $this->assertFalse($sonuc['basarili']);
        $this->assertStringContainsString('bulunamadı', $sonuc['hata']);

        // Arsa için galeri oluşturma
        $sonuc = $this->galeriService->galeriOlustur('App\\Models\\Mulk\\Arsa\\TicariArsa', '1');
        $this->assertFalse($sonuc['basarili']);
        $this->assertStringContainsString('oluşturulamaz', $sonuc['hata']);

        // Boş resim listesi ile sıralama güncelleme
        $sonuc = $this->galeriService->resimSiralamasiGuncelle([]);
        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals(0, $sonuc['guncellenen_resim_sayisi']);
    }

    protected function tearDown(): void
    {
        // Test sonrası temizlik
        Storage::fake('public');
        parent::tearDown();
    }
}