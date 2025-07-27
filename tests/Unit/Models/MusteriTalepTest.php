<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\MusteriTalep;
use App\Models\TalepAktivite;
use App\Models\TalepPortfoyEslestirme;
use App\Models\Musteri\Musteri;
use App\Models\Kisi\Personel;
use App\Models\User;
use App\Enums\TalepDurumu;
use App\Enums\MulkKategorisi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class MusteriTalepTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function talep_olusturulabilir()
    {
        // Arrange
        $musteri = Musteri::factory()->create();
        $personel = Personel::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user);

        // Act
        $talep = MusteriTalep::create([
            'musteri_id' => $musteri->id,
            'sorumlu_personel_id' => $personel->id,
            'baslik' => 'Test Talep',
            'aciklama' => 'Test açıklama',
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'min_fiyat' => 500000,
            'max_fiyat' => 1000000,
            'min_m2' => 80,
            'max_m2' => 120,
        ]);

        // Assert
        $this->assertDatabaseHas('musteri_talepleri', [
            'id' => $talep->id,
            'baslik' => 'Test Talep',
            'durum' => TalepDurumu::AKTIF->value,
            'aktif_mi' => true,
            'olusturan_id' => $user->id,
        ]);

        $this->assertEquals(MulkKategorisi::KONUT, $talep->mulk_kategorisi);
        $this->assertEquals(TalepDurumu::AKTIF, $talep->durum);
        $this->assertTrue($talep->aktif_mi);
    }

    /** @test */
    public function talep_iliskileri_dogru_calisir()
    {
        // Arrange
        $musteri = Musteri::factory()->create();
        $personel = Personel::factory()->create();
        $user = User::factory()->create();

        $talep = MusteriTalep::factory()->create([
            'musteri_id' => $musteri->id,
            'sorumlu_personel_id' => $personel->id,
            'olusturan_id' => $user->id,
        ]);

        // Act & Assert
        $this->assertEquals($musteri->id, $talep->musteri->id);
        $this->assertEquals($personel->id, $talep->sorumluPersonel->id);
        $this->assertEquals($user->id, $talep->olusturan->id);
    }

    /** @test */
    public function talep_aktivitesi_eklenebilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $talep = MusteriTalep::factory()->create();

        // Act
        $talep->aktiviteEkle('test_aktivite', [
            'test_veri' => 'test_deger'
        ]);

        // Assert
        $this->assertDatabaseHas('talep_aktiviteleri', [
            'talep_id' => $talep->id,
            'tip' => 'test_aktivite',
            'olusturan_id' => $user->id,
        ]);

        $aktivite = $talep->aktiviteler()->first();
        $this->assertEquals('test_aktivite', $aktivite->tip);
        $this->assertEquals(['test_veri' => 'test_deger'], $aktivite->detaylar);
    }

    /** @test */
    public function talep_durumu_guncellenebilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $talep = MusteriTalep::factory()->create([
            'durum' => TalepDurumu::AKTIF
        ]);

        // Act
        $talep->durumGuncelle(TalepDurumu::ESLESTI, 'Test açıklama');

        // Assert
        $talep->refresh();
        $this->assertEquals(TalepDurumu::ESLESTI, $talep->durum);
        $this->assertNotNull($talep->son_aktivite_tarihi);
        $this->assertNull($talep->tamamlanma_tarihi); // Henüz pasif değil

        // Aktivite kaydı oluşturulmuş mu?
        $this->assertDatabaseHas('talep_aktiviteleri', [
            'talep_id' => $talep->id,
            'tip' => 'durum_degisiklik',
        ]);
    }

    /** @test */
    public function pasif_duruma_gecince_tamamlanma_tarihi_set_edilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $talep = MusteriTalep::factory()->create([
            'durum' => TalepDurumu::AKTIF
        ]);

        // Act
        $talep->durumGuncelle(TalepDurumu::TAMAMLANDI);

        // Assert
        $talep->refresh();
        $this->assertEquals(TalepDurumu::TAMAMLANDI, $talep->durum);
        $this->assertNotNull($talep->tamamlanma_tarihi);
    }

    /** @test */
    public function not_eklenebilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $talep = MusteriTalep::factory()->create();

        // Act
        $talep->notEkle('Test not');

        // Assert
        $talep->refresh();
        $notlar = $talep->notlar;
        
        $this->assertCount(1, $notlar);
        $this->assertEquals('Test not', $notlar[0]['not']);
        $this->assertEquals($user->id, $notlar[0]['kullanici_id']);
        $this->assertArrayHasKey('tarih', $notlar[0]);
    }

    /** @test */
    public function ozel_gereksinim_eklenebilir()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $talep = MusteriTalep::factory()->create();

        // Act
        $talep->ozelGereksinimEkle('Asansör olmalı');

        // Assert
        $talep->refresh();
        $gereksinimler = $talep->ozel_gereksinimler;
        
        $this->assertCount(1, $gereksinimler);
        $this->assertEquals('Asansör olmalı', $gereksinimler[0]['gereksinim']);
        $this->assertEquals($user->id, $gereksinimler[0]['kullanici_id']);
    }

    /** @test */
    public function lokasyon_tercihi_eklenebilir()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create();

        // Act
        $talep->lokasyonTercihiEkle([
            'sehir_id' => 1,
            'ilce_id' => 5,
            'semt_id' => 10,
        ]);

        // Assert
        $talep->refresh();
        $tercihler = $talep->lokasyon_tercihleri;
        
        $this->assertCount(1, $tercihler);
        $this->assertEquals(1, $tercihler[0]['sehir_id']);
        $this->assertEquals(5, $tercihler[0]['ilce_id']);
        $this->assertEquals(10, $tercihler[0]['semt_id']);
    }

    /** @test */
    public function ayni_lokasyon_tercihi_tekrar_eklenmez()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create();

        $lokasyon = [
            'sehir_id' => 1,
            'ilce_id' => 5,
        ];

        // Act
        $talep->lokasyonTercihiEkle($lokasyon);
        $talep->lokasyonTercihiEkle($lokasyon); // Aynı lokasyonu tekrar ekle

        // Assert
        $talep->refresh();
        $this->assertCount(1, $talep->lokasyon_tercihleri);
    }

    /** @test */
    public function ozellik_kriteri_eklenebilir()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create();

        // Act
        $talep->ozellikKriteriEkle('oda_sayisi', 3);
        $talep->ozellikKriteriEkle('balkon', true);

        // Assert
        $talep->refresh();
        $kriterler = $talep->ozellik_kriterleri;
        
        $this->assertEquals(3, $kriterler['oda_sayisi']);
        $this->assertTrue($kriterler['balkon']);
    }

    /** @test */
    public function talep_ozeti_dogru_olusturulur()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'mulk_alt_tipi' => 'Daire',
            'min_m2' => 80,
            'max_m2' => 120,
            'min_fiyat' => 500000,
            'max_fiyat' => 1000000,
        ]);

        // Act
        $ozet = $talep->ozet;

        // Assert
        $this->assertStringContainsString('Konut', $ozet);
        $this->assertStringContainsString('Daire', $ozet);
        $this->assertStringContainsString('80-120 m²', $ozet);
        $this->assertStringContainsString('500.000-1.000.000 ₺', $ozet);
    }

    /** @test */
    public function talep_yasi_dogru_hesaplanir()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'created_at' => now()->subDays(5)
        ]);

        // Act
        $yas = $talep->yasi;

        // Assert
        $this->assertEquals(5, $yas);
    }

    /** @test */
    public function tamamlanma_orani_dogru_hesaplanir()
    {
        // Arrange - Tamamlanmış talep
        $tamamlanmisTalep = MusteriTalep::factory()->create([
            'durum' => TalepDurumu::TAMAMLANDI
        ]);

        // Arrange - Kısmen doldurulmuş talep
        $kısmenTalep = MusteriTalep::factory()->create([
            'baslik' => 'Test',
            'aciklama' => 'Test açıklama',
            'mulk_kategorisi' => MulkKategorisi::KONUT,
            'min_fiyat' => 500000,
            'max_fiyat' => 1000000,
            // min_m2, max_m2, lokasyon_tercihleri, ozellik_kriterleri yok
        ]);

        // Act & Assert
        $this->assertEquals(100, $tamamlanmisTalep->tamamlanma_orani);
        $this->assertEquals(70, $kısmenTalep->tamamlanma_orani); // 10+10+20+20+20 = 80, ama max 70
    }

    /** @test */
    public function acil_talep_tespiti_dogru_calisir()
    {
        // Arrange - Hedef tarihi yakın talep
        $yakinHedefTalep = MusteriTalep::factory()->create([
            'hedef_tarih' => now()->addDays(5)
        ]);

        // Arrange - Yüksek öncelikli talep
        $yuksekOncelikTalep = MusteriTalep::factory()->create([
            'oncelik' => 1
        ]);

        // Arrange - Eski talep
        $eskiTalep = MusteriTalep::factory()->create([
            'created_at' => now()->subDays(35)
        ]);

        // Arrange - Normal talep
        $normalTalep = MusteriTalep::factory()->create([
            'hedef_tarih' => now()->addDays(30),
            'oncelik' => 3,
            'created_at' => now()->subDays(10)
        ]);

        // Act & Assert
        $this->assertTrue($yakinHedefTalep->acil_mi);
        $this->assertTrue($yuksekOncelikTalep->acil_mi);
        $this->assertTrue($eskiTalep->acil_mi);
        $this->assertFalse($normalTalep->acil_mi);
    }

    /** @test */
    public function aktif_scope_dogru_calisir()
    {
        // Arrange
        $aktifTalep = MusteriTalep::factory()->create([
            'durum' => TalepDurumu::AKTIF,
            'aktif_mi' => true
        ]);

        $pasifTalep = MusteriTalep::factory()->create([
            'durum' => TalepDurumu::TAMAMLANDI,
            'aktif_mi' => true
        ]);

        $deaktifTalep = MusteriTalep::factory()->create([
            'durum' => TalepDurumu::AKTIF,
            'aktif_mi' => false
        ]);

        // Act
        $aktifTalepler = MusteriTalep::aktif()->get();

        // Assert
        $this->assertCount(1, $aktifTalepler);
        $this->assertEquals($aktifTalep->id, $aktifTalepler->first()->id);
    }

    /** @test */
    public function fiyat_araliginda_scope_dogru_calisir()
    {
        // Arrange
        $talep1 = MusteriTalep::factory()->create([
            'min_fiyat' => 500000,
            'max_fiyat' => 1000000
        ]);

        $talep2 = MusteriTalep::factory()->create([
            'min_fiyat' => 200000,
            'max_fiyat' => 400000
        ]);

        $talep3 = MusteriTalep::factory()->create([
            'min_fiyat' => null,
            'max_fiyat' => 800000
        ]);

        // Act
        $sonuclar = MusteriTalep::fiyatAraliginda(600000, 900000)->get();

        // Assert
        // talep1: 500k-1000k (600k-900k ile kesişir)
        // talep2: 200k-400k (kesişmez)
        // talep3: null-800k (600k-900k ile kesişir)
        $this->assertCount(2, $sonuclar);
        $this->assertTrue($sonuclar->contains($talep1));
        $this->assertTrue($sonuclar->contains($talep3));
    }

    /** @test */
    public function eslestirme_sayilari_dogru_hesaplanir()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create();

        TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'aktif_mi' => true
        ]);

        TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'aktif_mi' => false
        ]);

        TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'aktif_mi' => true
        ]);

        // Act & Assert
        $this->assertEquals(3, $talep->eslestirme_sayisi);
        $this->assertEquals(2, $talep->aktif_eslestirme_sayisi);
    }
}