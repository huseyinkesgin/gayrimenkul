<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GaleriService;
use App\Services\ResimUploadService;
use App\Models\Resim;
use App\Enums\ResimKategorisi;
use App\Enums\MulkKategorisi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;

/**
 * GaleriService Unit Testleri
 * 
 * Bu test sınıfı GaleriService'in tüm özelliklerini test eder:
 * - Galeri oluşturma ve kurallar
 * - Resim sıralama ve organizasyon
 * - Ana resim yönetimi
 * - İstatistikler ve öneriler
 */
class GaleriServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private GaleriService $galeriService;
    private ResimUploadService $resimUploadService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->resimUploadService = $this->createMock(ResimUploadService::class);
        $this->galeriService = new GaleriService($this->resimUploadService);
        
        // Test kullanıcısı oluştur
        $user = \App\Models\User::factory()->create();
        Auth::login($user);
    }

    /** @test */
    public function konut_icin_galeri_olusturma_testi()
    {
        $sonuc = $this->galeriService->galeriOlustur(
            'App\\Models\\Mulk\\Konut\\Daire',
            '1'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals('1', $sonuc['mulk_id']);
        $this->assertEquals(MulkKategorisi::KONUT->value, $sonuc['mulk_tipi']);
        $this->assertArrayHasKey('kurallar', $sonuc);
        
        $kurallar = $sonuc['kurallar'];
        $this->assertTrue($kurallar['galeri_aktif']);
        $this->assertEquals(3, $kurallar['min_resim']);
        $this->assertEquals(50, $kurallar['max_resim']);
        $this->assertContains(ResimKategorisi::GALERI_DIS_CEPHE, $kurallar['zorunlu_kategoriler']);
        $this->assertContains(ResimKategorisi::GALERI_SALON, $kurallar['zorunlu_kategoriler']);
    }

    /** @test */
    public function arsa_icin_galeri_olusturma_reddi_testi()
    {
        $sonuc = $this->galeriService->galeriOlustur(
            'App\\Models\\Mulk\\Arsa\\TicariArsa',
            '1'
        );

        $this->assertFalse($sonuc['basarili']);
        $this->assertStringContainsString('galeri oluşturulamaz', $sonuc['hata']);
        $this->assertEquals(MulkKategorisi::ARSA->value, $sonuc['mulk_tipi']);
    }

    /** @test */
    public function isyeri_icin_galeri_kuralları_testi()
    {
        $sonuc = $this->galeriService->galeriOlustur(
            'App\\Models\\Mulk\\Isyeri\\Ofis',
            '1'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals(MulkKategorisi::ISYERI->value, $sonuc['mulk_tipi']);
        
        $kurallar = $sonuc['kurallar'];
        $this->assertTrue($kurallar['galeri_aktif']);
        $this->assertEquals(2, $kurallar['min_resim']);
        $this->assertEquals(30, $kurallar['max_resim']);
        $this->assertContains(ResimKategorisi::GALERI_DIS_CEPHE, $kurallar['zorunlu_kategoriler']);
        $this->assertContains(ResimKategorisi::GALERI_IC_MEKAN, $kurallar['zorunlu_kategoriler']);
    }

    /** @test */
    public function turistik_tesis_icin_galeri_kuralları_testi()
    {
        $sonuc = $this->galeriService->galeriOlustur(
            'App\\Models\\Mulk\\TuristikTesis\\Hotel',
            '1'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals(MulkKategorisi::TURISTIK_TESIS->value, $sonuc['mulk_tipi']);
        
        $kurallar = $sonuc['kurallar'];
        $this->assertTrue($kurallar['galeri_aktif']);
        $this->assertEquals(5, $kurallar['min_resim']);
        $this->assertEquals(100, $kurallar['max_resim']);
        $this->assertContains(ResimKategorisi::GALERI_RESEPSIYON, $kurallar['zorunlu_kategoriler']);
        $this->assertContains(ResimKategorisi::GALERI_ODA, $kurallar['zorunlu_kategoriler']);
    }

    /** @test */
    public function galeri_resimleri_getirme_testi()
    {
        // Test resimleri oluştur
        $resimler = collect([
            $this->createTestResim(1, ResimKategorisi::GALERI_SALON, 1, true),
            $this->createTestResim(2, ResimKategorisi::GALERI_MUTFAK, 2, false),
            $this->createTestResim(3, ResimKategorisi::GALERI_BANYO, 3, false),
        ]);

        // Mock ResimUploadService
        $this->resimUploadService->method('resimUrlOlustur')
            ->willReturn('http://test.com/resim.jpg');

        $sonuc = $this->galeriService->galeriResimleriGetir(
            'App\\Models\\Mulk\\Konut\\Daire',
            '1'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertArrayHasKey('resimler', $sonuc);
        $this->assertArrayHasKey('toplam', $sonuc);
        $this->assertEquals('sira_asc', $sonuc['siralama']);
    }

    /** @test */
    public function kategori_bazli_filtreleme_testi()
    {
        // Test resimleri oluştur
        $this->createTestResim(1, ResimKategorisi::GALERI_SALON, 1);
        $this->createTestResim(2, ResimKategorisi::GALERI_MUTFAK, 2);
        $this->createTestResim(3, ResimKategorisi::GALERI_SALON, 3);

        $sonuc = $this->galeriService->galeriResimleriGetir(
            'App\\Models\\Mulk\\Konut\\Daire',
            '1',
            ResimKategorisi::GALERI_SALON
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals(ResimKategorisi::GALERI_SALON->value, $sonuc['kategori']);
        
        // Sadece salon kategorisindeki resimler dönmeli
        foreach ($sonuc['resimler'] as $resim) {
            $this->assertEquals(ResimKategorisi::GALERI_SALON, $resim['kategori']);
        }
    }

    /** @test */
    public function ana_resim_belirleme_testi()
    {
        // Test resimleri oluştur
        $resim1 = $this->createTestResim(1, ResimKategorisi::GALERI_SALON, 1, false);
        $resim2 = $this->createTestResim(2, ResimKategorisi::GALERI_MUTFAK, 2, false);

        $sonuc = $this->galeriService->anaResimBelirle(
            $resim2->id,
            'App\\Models\\Mulk\\Konut\\Daire',
            '1'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertArrayHasKey('resim', $sonuc);
        
        // Veritabanında ana resim güncellenmiş mi?
        $this->assertDatabaseHas('resimler', [
            'id' => $resim2->id,
            'ana_resim_mi' => true,
            'sira' => 1
        ]);
        
        // Diğer resimler ana resim olmaktan çıkmış mı?
        $this->assertDatabaseHas('resimler', [
            'id' => $resim1->id,
            'ana_resim_mi' => false
        ]);
    }

    /** @test */
    public function resim_siralama_guncelleme_testi()
    {
        // Test resimleri oluştur
        $resim1 = $this->createTestResim(1, ResimKategorisi::GALERI_SALON, 1);
        $resim2 = $this->createTestResim(2, ResimKategorisi::GALERI_MUTFAK, 2);
        $resim3 = $this->createTestResim(3, ResimKategorisi::GALERI_BANYO, 3);

        // Sıralamayı değiştir: [resim3, resim1, resim2]
        $yeniSiralama = [$resim3->id, $resim1->id, $resim2->id];

        $sonuc = $this->galeriService->resimSiralamasiGuncelle($yeniSiralama);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals(3, $sonuc['guncellenen_resim_sayisi']);
        
        // Veritabanında sıralama güncellenmiş mi?
        $this->assertDatabaseHas('resimler', ['id' => $resim3->id, 'sira' => 1]);
        $this->assertDatabaseHas('resimler', ['id' => $resim1->id, 'sira' => 2]);
        $this->assertDatabaseHas('resimler', ['id' => $resim2->id, 'sira' => 3]);
    }

    /** @test */
    public function galeri_istatistikleri_testi()
    {
        // Test resimleri oluştur
        $this->createTestResim(1, ResimKategorisi::GALERI_DIS_CEPHE, 1, true);
        $this->createTestResim(2, ResimKategorisi::GALERI_SALON, 2);
        $this->createTestResim(3, ResimKategorisi::GALERI_MUTFAK, 3);

        $sonuc = $this->galeriService->galeriIstatistikleri(
            'App\\Models\\Mulk\\Konut\\Daire',
            '1'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals(MulkKategorisi::KONUT->value, $sonuc['mulk_tipi']);
        $this->assertEquals(3, $sonuc['toplam_resim']);
        $this->assertEquals(3, $sonuc['min_resim']);
        $this->assertEquals(50, $sonuc['max_resim']);
        $this->assertArrayHasKey('ana_resim', $sonuc);
        $this->assertArrayHasKey('kategori_dagilimi', $sonuc);
        $this->assertArrayHasKey('eksik_kategoriler', $sonuc);
        $this->assertTrue($sonuc['galeri_tamamlandi']);
        $this->assertEquals(6.0, $sonuc['doluluk_orani']); // 3/50 * 100
    }

    /** @test */
    public function eksik_kategoriler_tespiti_testi()
    {
        // Sadece dış cephe resmi ekle (salon ve mutfak eksik)
        $this->createTestResim(1, ResimKategorisi::GALERI_DIS_CEPHE, 1);

        $sonuc = $this->galeriService->galeriIstatistikleri(
            'App\\Models\\Mulk\\Konut\\Daire',
            '1'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertFalse($sonuc['galeri_tamamlandi']);
        
        $eksikKategoriler = $sonuc['eksik_kategoriler'];
        $this->assertContains(ResimKategorisi::GALERI_SALON, $eksikKategoriler);
        $this->assertContains(ResimKategorisi::GALERI_MUTFAK, $eksikKategoriler);
    }

    /** @test */
    public function toplu_resim_silme_testi()
    {
        // Test resimleri oluştur
        $resim1 = $this->createTestResim(1, ResimKategorisi::GALERI_SALON, 1);
        $resim2 = $this->createTestResim(2, ResimKategorisi::GALERI_MUTFAK, 2);
        $resim3 = $this->createTestResim(3, ResimKategorisi::GALERI_BANYO, 3);

        // Mock ResimUploadService silme işlemi
        $this->resimUploadService->method('resimSil')
            ->willReturn(true);

        $silinecekResimler = [$resim1->id, $resim3->id];

        $sonuc = $this->galeriService->topluResimSil($silinecekResimler);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals(2, $sonuc['toplam']);
        $this->assertEquals(2, $sonuc['silinen']);
        $this->assertEquals(0, $sonuc['hatali']);
    }

    /** @test */
    public function galeri_organizasyon_onerileri_testi()
    {
        // Ana resim olmayan resimler oluştur
        $this->createTestResim(1, ResimKategorisi::GALERI_DIS_CEPHE, 1, false);
        $this->createTestResim(2, ResimKategorisi::GALERI_SALON, 0); // Sıralanmamış

        $sonuc = $this->galeriService->galeriOrganizasyonuOner(
            'App\\Models\\Mulk\\Konut\\Daire',
            '1'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertArrayHasKey('oneriler', $sonuc);
        $this->assertGreaterThan(0, $sonuc['oneri_sayisi']);
        
        $oneriler = $sonuc['oneriler'];
        
        // Ana resim önerisi var mı?
        $anaResimOnerisi = collect($oneriler)->firstWhere('tip', 'ana_resim');
        $this->assertNotNull($anaResimOnerisi);
        
        // Sıralama önerisi var mı?
        $siralamaOnerisi = collect($oneriler)->firstWhere('tip', 'siralama');
        $this->assertNotNull($siralamaOnerisi);
        
        // Eksik kategori önerisi var mı?
        $eksikKategoriOnerisi = collect($oneriler)->firstWhere('tip', 'eksik_kategori');
        $this->assertNotNull($eksikKategoriOnerisi);
    }

    /** @test */
    public function mulk_tipi_icin_kategoriler_getirme_testi()
    {
        $sonuc = $this->galeriService->mulkTipiIcinKategorileriGetir(
            'App\\Models\\Mulk\\Konut\\Daire'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals(MulkKategorisi::KONUT->value, $sonuc['mulk_tipi']);
        $this->assertArrayHasKey('zorunlu_kategoriler', $sonuc);
        $this->assertArrayHasKey('opsiyonel_kategoriler', $sonuc);
        $this->assertArrayHasKey('tum_kategoriler', $sonuc);
        
        $zorunluKategoriler = $sonuc['zorunlu_kategoriler'];
        $this->assertContains(ResimKategorisi::GALERI_DIS_CEPHE, $zorunluKategoriler);
        $this->assertContains(ResimKategorisi::GALERI_SALON, $zorunluKategoriler);
        $this->assertContains(ResimKategorisi::GALERI_MUTFAK, $zorunluKategoriler);
    }

    /** @test */
    public function galeri_kurallari_getirme_testi()
    {
        $sonuc = $this->galeriService->galeriKurallariniGetir(
            'App\\Models\\Mulk\\Konut\\Daire'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals(MulkKategorisi::KONUT->value, $sonuc['mulk_tipi']);
        $this->assertArrayHasKey('kurallar', $sonuc);
        
        $kurallar = $sonuc['kurallar'];
        $this->assertTrue($kurallar['galeri_aktif']);
        $this->assertIsInt($kurallar['min_resim']);
        $this->assertIsInt($kurallar['max_resim']);
        $this->assertIsArray($kurallar['zorunlu_kategoriler']);
        $this->assertIsArray($kurallar['opsiyonel_kategoriler']);
    }

    /** @test */
    public function mulk_tipinden_kategori_belirleme_testi()
    {
        // Private metoda erişim için reflection kullan
        $reflection = new \ReflectionClass($this->galeriService);
        $method = $reflection->getMethod('mulkTipindenKategoriBelirle');
        $method->setAccessible(true);

        // Konut tipleri
        $this->assertEquals(
            MulkKategorisi::KONUT->value,
            $method->invoke($this->galeriService, 'App\\Models\\Mulk\\Konut\\Daire')
        );
        
        $this->assertEquals(
            MulkKategorisi::KONUT->value,
            $method->invoke($this->galeriService, 'App\\Models\\Mulk\\Konut\\Villa')
        );

        // İşyeri tipleri
        $this->assertEquals(
            MulkKategorisi::ISYERI->value,
            $method->invoke($this->galeriService, 'App\\Models\\Mulk\\Isyeri\\Ofis')
        );

        // Turistik tesis tipleri
        $this->assertEquals(
            MulkKategorisi::TURISTIK_TESIS->value,
            $method->invoke($this->galeriService, 'App\\Models\\Mulk\\TuristikTesis\\Hotel')
        );

        // Arsa tipleri
        $this->assertEquals(
            MulkKategorisi::ARSA->value,
            $method->invoke($this->galeriService, 'App\\Models\\Mulk\\Arsa\\TicariArsa')
        );
    }

    /** @test */
    public function galeri_aktif_kontrolu_testi()
    {
        // Private metoda erişim için reflection kullan
        $reflection = new \ReflectionClass($this->galeriService);
        $method = $reflection->getMethod('galeriAktifMi');
        $method->setAccessible(true);

        // Konut için aktif olmalı
        $this->assertTrue($method->invoke($this->galeriService, MulkKategorisi::KONUT->value));
        
        // İşyeri için aktif olmalı
        $this->assertTrue($method->invoke($this->galeriService, MulkKategorisi::ISYERI->value));
        
        // Turistik tesis için aktif olmalı
        $this->assertTrue($method->invoke($this->galeriService, MulkKategorisi::TURISTIK_TESIS->value));
        
        // Arsa için aktif olmamalı
        $this->assertFalse($method->invoke($this->galeriService, MulkKategorisi::ARSA->value));
    }

    /**
     * Test resmi oluştur
     */
    private function createTestResim(
        int $id,
        ResimKategorisi $kategori,
        int $sira = 0,
        bool $anaResimMi = false
    ): Resim {
        return Resim::create([
            'id' => $id,
            'baslik' => "Test Resim {$id}",
            'kategori' => $kategori,
            'dosya_adi' => "resim{$id}.jpg",
            'orijinal_dosya_adi' => "resim{$id}.jpg",
            'dosya_yolu' => "resimler/test/resim{$id}.jpg",
            'dosya_boyutu' => 1024,
            'mime_type' => 'image/jpeg',
            'genislik' => 800,
            'yukseklik' => 600,
            'boyutlar' => [
                'thumbnail' => ['url' => "http://test.com/thumb{$id}.jpg"],
                'medium' => ['url' => "http://test.com/medium{$id}.jpg"],
            ],
            'metadata' => ['test' => true],
            'imagable_type' => 'App\\Models\\Mulk\\Konut\\Daire',
            'imagable_id' => '1',
            'olusturan_id' => Auth::id(),
            'aktif_mi' => true,
            'sira' => $sira,
            'ana_resim_mi' => $anaResimMi
        ]);
    }
}