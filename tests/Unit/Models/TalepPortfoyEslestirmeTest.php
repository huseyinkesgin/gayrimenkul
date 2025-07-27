<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\TalepPortfoyEslestirme;
use App\Models\MusteriTalep;
use App\Models\Mulk\Konut\Daire;
use App\Models\Kisi\Personel;
use App\Models\User;
use App\Services\EslestirmeBildirimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;

class TalepPortfoyEslestirmeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /** @test */
    public function eslestirme_olusturulabilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $talep = MusteriTalep::factory()->create();
        $daire = Daire::factory()->create();

        // Act
        $eslestirme = TalepPortfoyEslestirme::create([
            'talep_id' => $talep->id,
            'mulk_id' => $daire->id,
            'mulk_type' => $daire->getMulkType(),
            'eslestirme_skoru' => 0.85,
            'eslestirme_detaylari' => [
                'test' => 'data'
            ],
        ]);

        // Assert
        $this->assertDatabaseHas('talep_portfoy_eslestirmeleri', [
            'id' => $eslestirme->id,
            'talep_id' => $talep->id,
            'mulk_id' => $daire->id,
            'durum' => 'yeni',
            'aktif_mi' => true,
            'olusturan_id' => $user->id,
        ]);

        $this->assertEquals(0.85, $eslestirme->eslestirme_skoru);
        $this->assertEquals(['test' => 'data'], $eslestirme->eslestirme_detaylari);
    }

    /** @test */
    public function eslestirme_iliskileri_dogru_calisir()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create();
        $daire = Daire::factory()->create();
        $personel = Personel::factory()->create();
        $user = User::factory()->create();

        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'mulk_id' => $daire->id,
            'mulk_type' => $daire->getMulkType(),
            'sunan_personel_id' => $personel->id,
            'olusturan_id' => $user->id,
        ]);

        // Act & Assert
        $this->assertEquals($talep->id, $eslestirme->talep->id);
        $this->assertEquals($daire->id, $eslestirme->mulk->id);
        $this->assertEquals($personel->id, $eslestirme->sunanPersonel->id);
        $this->assertEquals($user->id, $eslestirme->olusturan->id);
    }

    /** @test */
    public function durum_guncelleme_dogru_calisir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Bildirim servisini mock'la
        $bildirimServiceMock = Mockery::mock(EslestirmeBildirimService::class);
        $this->app->instance(EslestirmeBildirimService::class, $bildirimServiceMock);

        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'durum' => 'yeni'
        ]);

        // Kabul durumu için bildirim beklentisi
        $bildirimServiceMock
            ->shouldReceive('eslestirmeKabulBildirimi')
            ->once()
            ->with($eslestirme);

        // Act
        $eslestirme->durumGuncelle('kabul_edildi', 'Müşteri beğendi');

        // Assert
        $eslestirme->refresh();
        $this->assertEquals('kabul_edildi', $eslestirme->durum);
        $this->assertEquals('Müşteri beğendi', $eslestirme->personel_notu);
        $this->assertEquals($user->id, $eslestirme->guncelleyen_id);

        // Talep aktivitesi oluşturulmuş mu?
        $this->assertDatabaseHas('talep_aktiviteleri', [
            'talep_id' => $eslestirme->talep_id,
            'tip' => 'eslestirme_durum_degisiklik',
        ]);
    }

    /** @test */
    public function red_durumu_icin_bildirim_gonderilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $bildirimServiceMock = Mockery::mock(EslestirmeBildirimService::class);
        $this->app->instance(EslestirmeBildirimService::class, $bildirimServiceMock);

        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'durum' => 'sunuldu'
        ]);

        // Red durumu için bildirim beklentisi
        $bildirimServiceMock
            ->shouldReceive('eslestirmeRedBildirimi')
            ->once()
            ->with($eslestirme);

        // Act
        $eslestirme->durumGuncelle('reddedildi', 'Müşteri beğenmedi');

        // Assert
        $this->assertEquals('reddedildi', $eslestirme->durum);
    }

    /** @test */
    public function sunum_bilgileri_guncellenebilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $bildirimServiceMock = Mockery::mock(EslestirmeBildirimService::class);
        $this->app->instance(EslestirmeBildirimService::class, $bildirimServiceMock);

        $personel = Personel::factory()->create();
        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'durum' => 'incelendi'
        ]);

        // Sunum bildirimi beklentisi
        $bildirimServiceMock
            ->shouldReceive('eslestirmeSunulduBildirimi')
            ->once()
            ->with($eslestirme);

        // Act
        $eslestirme->sunumBilgileriniGuncelle($personel->id, 'Müşteri ilgilendi');

        // Assert
        $eslestirme->refresh();
        $this->assertEquals('sunuldu', $eslestirme->durum);
        $this->assertEquals($personel->id, $eslestirme->sunan_personel_id);
        $this->assertEquals('Müşteri ilgilendi', $eslestirme->musteri_geri_bildirimi);
        $this->assertNotNull($eslestirme->sunum_tarihi);

        // Talep aktivitesi oluşturulmuş mu?
        $this->assertDatabaseHas('talep_aktiviteleri', [
            'talep_id' => $eslestirme->talep_id,
            'tip' => 'portfoy_sunuldu',
        ]);
    }

    /** @test */
    public function geri_bildirim_kaydedilebilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $eslestirme = TalepPortfoyEslestirme::factory()->create();

        // Act
        $eslestirme->geriBildirimKaydet('Çok beğendik, düşünüyoruz', 'incelendi');

        // Assert
        $eslestirme->refresh();
        $this->assertEquals('Çok beğendik, düşünüyoruz', $eslestirme->musteri_geri_bildirimi);
        $this->assertEquals('incelendi', $eslestirme->durum);

        // Talep aktivitesi oluşturulmuş mu?
        $this->assertDatabaseHas('talep_aktiviteleri', [
            'talep_id' => $eslestirme->talep_id,
            'tip' => 'musteri_geri_bildirim',
        ]);
    }

    /** @test */
    public function eslestirme_detaylari_guncellenebilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'eslestirme_detaylari' => [
                'mevcut' => 'veri'
            ]
        ]);

        // Act
        $eslestirme->detaylariGuncelle([
            'yeni' => 'veri',
            'skor' => 0.9
        ]);

        // Assert
        $eslestirme->refresh();
        $detaylar = $eslestirme->eslestirme_detaylari;
        
        $this->assertEquals('veri', $detaylar['mevcut']);
        $this->assertEquals('veri', $detaylar['yeni']);
        $this->assertEquals(0.9, $detaylar['skor']);
    }

    /** @test */
    public function durum_etiketleri_dogru_doner()
    {
        // Arrange
        $eslestirmeler = [
            'yeni' => TalepPortfoyEslestirme::factory()->make(['durum' => 'yeni']),
            'incelendi' => TalepPortfoyEslestirme::factory()->make(['durum' => 'incelendi']),
            'sunuldu' => TalepPortfoyEslestirme::factory()->make(['durum' => 'sunuldu']),
            'reddedildi' => TalepPortfoyEslestirme::factory()->make(['durum' => 'reddedildi']),
            'kabul_edildi' => TalepPortfoyEslestirme::factory()->make(['durum' => 'kabul_edildi']),
        ];

        // Act & Assert
        $this->assertEquals('Yeni', $eslestirmeler['yeni']->durum_label);
        $this->assertEquals('İncelendi', $eslestirmeler['incelendi']->durum_label);
        $this->assertEquals('Sunuldu', $eslestirmeler['sunuldu']->durum_label);
        $this->assertEquals('Reddedildi', $eslestirmeler['reddedildi']->durum_label);
        $this->assertEquals('Kabul Edildi', $eslestirmeler['kabul_edildi']->durum_label);
    }

    /** @test */
    public function durum_renkleri_dogru_doner()
    {
        // Arrange
        $eslestirmeler = [
            'yeni' => TalepPortfoyEslestirme::factory()->make(['durum' => 'yeni']),
            'kabul_edildi' => TalepPortfoyEslestirme::factory()->make(['durum' => 'kabul_edildi']),
            'reddedildi' => TalepPortfoyEslestirme::factory()->make(['durum' => 'reddedildi']),
        ];

        // Act & Assert
        $this->assertEquals('blue', $eslestirmeler['yeni']->durum_renk);
        $this->assertEquals('green', $eslestirmeler['kabul_edildi']->durum_renk);
        $this->assertEquals('red', $eslestirmeler['reddedildi']->durum_renk);
    }

    /** @test */
    public function skor_yuzde_dogru_hesaplanir()
    {
        // Arrange
        $eslestirme1 = TalepPortfoyEslestirme::factory()->make(['eslestirme_skoru' => 0.85]);
        $eslestirme2 = TalepPortfoyEslestirme::factory()->make(['eslestirme_skoru' => null]);

        // Act & Assert
        $this->assertEquals(85, $eslestirme1->skor_yuzde);
        $this->assertEquals(0, $eslestirme2->skor_yuzde);
    }

    /** @test */
    public function kalite_degerlendirmesi_dogru_doner()
    {
        // Arrange
        $eslestirmeler = [
            TalepPortfoyEslestirme::factory()->make(['eslestirme_skoru' => 0.95]),
            TalepPortfoyEslestirme::factory()->make(['eslestirme_skoru' => 0.85]),
            TalepPortfoyEslestirme::factory()->make(['eslestirme_skoru' => 0.75]),
            TalepPortfoyEslestirme::factory()->make(['eslestirme_skoru' => 0.65]),
            TalepPortfoyEslestirme::factory()->make(['eslestirme_skoru' => 0.55]),
            TalepPortfoyEslestirme::factory()->make(['eslestirme_skoru' => 0.45]),
            TalepPortfoyEslestirme::factory()->make(['eslestirme_skoru' => null]),
        ];

        // Act & Assert
        $this->assertEquals('Mükemmel', $eslestirmeler[0]->kalite);
        $this->assertEquals('Çok İyi', $eslestirmeler[1]->kalite);
        $this->assertEquals('İyi', $eslestirmeler[2]->kalite);
        $this->assertEquals('Orta', $eslestirmeler[3]->kalite);
        $this->assertEquals('Zayıf', $eslestirmeler[4]->kalite);
        $this->assertEquals('Çok Zayıf', $eslestirmeler[5]->kalite);
        $this->assertEquals('Bilinmiyor', $eslestirmeler[6]->kalite);
    }

    /** @test */
    public function scope_metodlari_dogru_calisir()
    {
        // Arrange
        $aktifEslestirme = TalepPortfoyEslestirme::factory()->create([
            'aktif_mi' => true,
            'durum' => 'yeni',
            'eslestirme_skoru' => 0.9
        ]);

        $pasifEslestirme = TalepPortfoyEslestirme::factory()->create([
            'aktif_mi' => false,
            'durum' => 'yeni',
            'eslestirme_skoru' => 0.9
        ]);

        $sunulmusEslestirme = TalepPortfoyEslestirme::factory()->create([
            'aktif_mi' => true,
            'durum' => 'sunuldu',
            'eslestirme_skoru' => 0.6
        ]);

        // Act & Assert
        $this->assertCount(2, TalepPortfoyEslestirme::aktif()->get());
        $this->assertCount(1, TalepPortfoyEslestirme::durum('yeni')->get());
        $this->assertCount(1, TalepPortfoyEslestirme::yuksekSkor(0.8)->get());
        $this->assertCount(1, TalepPortfoyEslestirme::sunulmus()->get());
        $this->assertCount(1, TalepPortfoyEslestirme::bekleyen()->get());
    }

    /** @test */
    public function eslestirme_yasi_dogru_hesaplanir()
    {
        // Arrange
        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'olusturma_tarihi' => now()->subDays(3)
        ]);

        // Act
        $yas = $eslestirme->yasi;

        // Assert
        $this->assertEquals(3, $yas);
    }

    /** @test */
    public function sunumdan_beri_gecen_sure_dogru_hesaplanir()
    {
        // Arrange
        $eslestirme1 = TalepPortfoyEslestirme::factory()->create([
            'sunum_tarihi' => now()->subDays(2)
        ]);

        $eslestirme2 = TalepPortfoyEslestirme::factory()->create([
            'sunum_tarihi' => null
        ]);

        // Act & Assert
        $this->assertEquals(2, $eslestirme1->sunumdanberi);
        $this->assertNull($eslestirme2->sunumdanberi);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}