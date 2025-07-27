<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TalepEslestirmeService;
use App\Services\EslestirmeBildirimService;
use App\Models\MusteriTalep;
use App\Models\TalepPortfoyEslestirme;
use App\Models\Mulk\Konut\Daire;
use App\Models\Musteri\Musteri;
use App\Models\Kisi\Personel;
use App\Enums\TalepDurumu;
use App\Enums\MulkKategorisi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;

class TalepEslestirmeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TalepEslestirmeService $service;
    protected $bildirimServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Bildirim servisini mock'la
        $this->bildirimServiceMock = Mockery::mock(EslestirmeBildirimService::class);
        $this->app->instance(EslestirmeBildirimService::class, $this->bildirimServiceMock);
        
        $this->service = new TalepEslestirmeService($this->bildirimServiceMock);
        
        // Bildirimleri fake'le
        Notification::fake();
    }

    /** @test */
    public function talep_icin_eslestirme_yapabilir()
    {
        // Arrange
        $musteri = Musteri::factory()->create();
        $personel = Personel::factory()->create();
        
        $talep = MusteriTalep::factory()->create([
            'musteri_id' => $musteri->id,
            'sorumlu_personel_id' => $personel->id,
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'min_fiyat' => 500000,
            'max_fiyat' => 1000000,
            'min_m2' => 80,
            'max_m2' => 120,
            'durum' => TalepDurumu::AKTIF,
        ]);

        $daire = Daire::factory()->create([
            'fiyat' => 750000,
            'metrekare' => 100,
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);

        // Bildirim servisinin çağrılacağını bekle
        $this->bildirimServiceMock
            ->shouldReceive('yeniEslestirmeBildirimi')
            ->once()
            ->with($talep, Mockery::type('Illuminate\Support\Collection'));

        // Act
        $eslestirmeler = $this->service->talepIcinEslestirmeYap($talep);

        // Assert
        $this->assertNotEmpty($eslestirmeler);
        $this->assertGreaterThan(0, $eslestirmeler->count());
        
        $ilkEslestirme = $eslestirmeler->first();
        $this->assertEquals($talep->id, $ilkEslestirme['talep_id']);
        $this->assertEquals($daire->id, $ilkEslestirme['mulk_id']);
        $this->assertGreaterThanOrEqual(0.3, $ilkEslestirme['eslestirme_skoru']);
        
        // Veritabanında eşleştirme kaydı oluşturulmuş mu?
        $this->assertDatabaseHas('talep_portfoy_eslestirmeleri', [
            'talep_id' => $talep->id,
            'mulk_id' => $daire->id,
        ]);
    }

    /** @test */
    public function pasif_talep_icin_eslestirme_yapmaz()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'durum' => TalepDurumu::TAMAMLANDI,
        ]);

        // Act
        $eslestirmeler = $this->service->talepIcinEslestirmeYap($talep);

        // Assert
        $this->assertEmpty($eslestirmeler);
        
        // Bildirim gönderilmemeli
        $this->bildirimServiceMock->shouldNotHaveReceived('yeniEslestirmeBildirimi');
    }

    /** @test */
    public function eslestirme_skoru_dogru_hesaplanir()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'min_fiyat' => 500000,
            'max_fiyat' => 1000000,
            'min_m2' => 80,
            'max_m2' => 120,
        ]);

        // Tam uyumlu mülk
        $perfectMatch = Daire::factory()->create([
            'fiyat' => 750000, // Tam ortada
            'metrekare' => 100, // Tam ortada
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);

        // Kısmen uyumlu mülk
        $partialMatch = Daire::factory()->create([
            'fiyat' => 1200000, // Aralık dışında
            'metrekare' => 90, // Aralık içinde
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);

        $this->bildirimServiceMock
            ->shouldReceive('yeniEslestirmeBildirimi')
            ->once();

        // Act
        $eslestirmeler = $this->service->talepIcinEslestirmeYap($talep);

        // Assert
        $perfectMatchEslestirme = $eslestirmeler->where('mulk_id', $perfectMatch->id)->first();
        $partialMatchEslestirme = $eslestirmeler->where('mulk_id', $partialMatch->id)->first();

        // Tam uyumlu mülkün skoru daha yüksek olmalı
        if ($perfectMatchEslestirme && $partialMatchEslestirme) {
            $this->assertGreaterThan(
                $partialMatchEslestirme['eslestirme_skoru'],
                $perfectMatchEslestirme['eslestirme_skoru']
            );
        }

        // Tam uyumlu mülkün skoru yüksek olmalı
        if ($perfectMatchEslestirme) {
            $this->assertGreaterThan(0.7, $perfectMatchEslestirme['eslestirme_skoru']);
        }
    }

    /** @test */
    public function minimum_skor_altindaki_eslestirmeler_elenir()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'min_fiyat' => 500000,
            'max_fiyat' => 600000,
        ]);

        // Çok uyumsuz mülk (farklı kategori)
        $uyumsuzMulk = \App\Models\Mulk\Arsa\TicariArsa::factory()->create([
            'fiyat' => 2000000, // Çok yüksek
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);

        $this->bildirimServiceMock
            ->shouldReceive('yeniEslestirmeBildirimi')
            ->once();

        // Act
        $eslestirmeler = $this->service->talepIcinEslestirmeYap($talep);

        // Assert
        // Uyumsuz mülk eşleştirmelerde olmamalı
        $uyumsuzEslestirme = $eslestirmeler->where('mulk_id', $uyumsuzMulk->id)->first();
        $this->assertNull($uyumsuzEslestirme);
    }

    /** @test */
    public function maksimum_eslestirme_sayisi_sinirlanir()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'durum' => TalepDurumu::AKTIF,
        ]);

        // 25 adet uyumlu mülk oluştur (limit 20)
        for ($i = 0; $i < 25; $i++) {
            Daire::factory()->create([
                'fiyat' => 500000 + ($i * 10000),
                'metrekare' => 80 + $i,
                'durum' => 'aktif',
                'aktif_mi' => true,
            ]);
        }

        $this->bildirimServiceMock
            ->shouldReceive('yeniEslestirmeBildirimi')
            ->once();

        // Act
        $eslestirmeler = $this->service->talepIcinEslestirmeYap($talep);

        // Assert
        $this->assertLessThanOrEqual(20, $eslestirmeler->count());
    }

    /** @test */
    public function eslestirmeler_skora_gore_siralanir()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'min_fiyat' => 500000,
            'max_fiyat' => 600000,
            'durum' => TalepDurumu::AKTIF,
        ]);

        // Farklı skorlara sahip mülkler
        $yuksekSkorMulk = Daire::factory()->create([
            'fiyat' => 550000, // Tam aralık içinde
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);

        $dusukSkorMulk = Daire::factory()->create([
            'fiyat' => 650000, // Aralık dışında
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);

        $this->bildirimServiceMock
            ->shouldReceive('yeniEslestirmeBildirimi')
            ->once();

        // Act
        $eslestirmeler = $this->service->talepIcinEslestirmeYap($talep);

        // Assert
        if ($eslestirmeler->count() >= 2) {
            $ilkEslestirme = $eslestirmeler->first();
            $ikinciEslestirme = $eslestirmeler->skip(1)->first();
            
            $this->assertGreaterThanOrEqual(
                $ikinciEslestirme['eslestirme_skoru'],
                $ilkEslestirme['eslestirme_skoru']
            );
        }
    }

    /** @test */
    public function eslestirme_detaylari_dogru_olusturulur()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'min_fiyat' => 500000,
            'max_fiyat' => 1000000,
            'durum' => TalepDurumu::AKTIF,
        ]);

        $daire = Daire::factory()->create([
            'fiyat' => 750000,
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);

        $this->bildirimServiceMock
            ->shouldReceive('yeniEslestirmeBildirimi')
            ->once();

        // Act
        $eslestirmeler = $this->service->talepIcinEslestirmeYap($talep);

        // Assert
        $eslestirme = $eslestirmeler->first();
        $detaylar = $eslestirme['eslestirme_detaylari'];

        $this->assertArrayHasKey('toplam_skor', $detaylar);
        $this->assertArrayHasKey('skor_detaylari', $detaylar);
        $this->assertArrayHasKey('eslestirme_tarihi', $detaylar);
        $this->assertArrayHasKey('algoritma_versiyonu', $detaylar);

        $skorDetaylari = $detaylar['skor_detaylari'];
        $this->assertArrayHasKey('kategori', $skorDetaylari);
        $this->assertArrayHasKey('fiyat', $skorDetaylari);
        $this->assertArrayHasKey('metrekare', $skorDetaylari);
        $this->assertArrayHasKey('lokasyon', $skorDetaylari);
        $this->assertArrayHasKey('ozellikler', $skorDetaylari);

        // Her kriter için skor ve ağırlık bilgisi olmalı
        foreach ($skorDetaylari as $kriter) {
            $this->assertArrayHasKey('skor', $kriter);
            $this->assertArrayHasKey('agirlik', $kriter);
            $this->assertArrayHasKey('aciklama', $kriter);
        }
    }

    /** @test */
    public function eslestirme_istatistikleri_dogru_hesaplanir()
    {
        // Arrange
        $talep1 = MusteriTalep::factory()->create(['durum' => TalepDurumu::AKTIF]);
        $talep2 = MusteriTalep::factory()->create(['durum' => TalepDurumu::AKTIF]);
        $talep3 = MusteriTalep::factory()->create(['durum' => TalepDurumu::TAMAMLANDI]);

        TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep1->id,
            'eslestirme_skoru' => 0.9,
            'durum' => 'yeni',
            'aktif_mi' => true,
        ]);

        TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep1->id,
            'eslestirme_skoru' => 0.6,
            'durum' => 'sunuldu',
            'aktif_mi' => true,
        ]);

        // Act
        $istatistikler = $this->service->eslestirmeIstatistikleri();

        // Assert
        $this->assertEquals(2, $istatistikler['toplam_aktif_talep']);
        $this->assertEquals(1, $istatistikler['eslestirmesi_olan_talep']);
        $this->assertEquals(2, $istatistikler['toplam_eslestirme']);
        $this->assertEquals(1, $istatistikler['yuksek_skorlu_eslestirme']);
        $this->assertEquals(1, $istatistikler['sunulmus_eslestirme']);
        $this->assertEquals(1, $istatistikler['bekleyen_eslestirme']);
    }

    /** @test */
    public function otomatik_kaydet_false_ise_veritabanina_kaydetmez()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'durum' => TalepDurumu::AKTIF,
        ]);

        Daire::factory()->create([
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);

        // Act
        $eslestirmeler = $this->service->talepIcinEslestirmeYap($talep, false);

        // Assert
        $this->assertNotEmpty($eslestirmeler);
        
        // Veritabanında kayıt olmamalı
        $this->assertDatabaseMissing('talep_portfoy_eslestirmeleri', [
            'talep_id' => $talep->id,
        ]);

        // Bildirim gönderilmemeli
        $this->bildirimServiceMock->shouldNotHaveReceived('yeniEslestirmeBildirimi');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}