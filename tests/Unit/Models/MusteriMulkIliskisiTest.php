<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\MusteriMulkIliskisi;
use App\Models\Musteri\Musteri;
use App\Models\Mulk\BaseMulk;
use App\Models\User;
use App\Models\MusteriHizmet;
use App\Enums\IliskiTipi;
use App\Enums\IliskiDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MusteriMulkIliskisiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_musteri_mulk_iliskisi()
    {
        $musteri = Musteri::factory()->create();
        $mulk = BaseMulk::factory()->create();
        $personel = User::factory()->create();

        $iliski = MusteriMulkIliskisi::create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
            'mulk_type' => get_class($mulk),
            'iliski_tipi' => IliskiTipi::ILGILENIYOR,
            'durum' => IliskiDurumu::AKTIF,
            'ilgi_seviyesi' => 7,
            'sorumlu_personel_id' => $personel->id,
            'aktif_mi' => true,
        ]);

        $this->assertInstanceOf(MusteriMulkIliskisi::class, $iliski);
        $this->assertEquals($musteri->id, $iliski->musteri_id);
        $this->assertEquals($mulk->id, $iliski->mulk_id);
        $this->assertEquals(IliskiTipi::ILGILENIYOR, $iliski->iliski_tipi);
        $this->assertEquals(IliskiDurumu::AKTIF, $iliski->durum);
        $this->assertEquals(7, $iliski->ilgi_seviyesi);
        $this->assertTrue($iliski->aktif_mi);
    }

    /** @test */
    public function it_has_correct_relationships()
    {
        $iliski = MusteriMulkIliskisi::factory()->create();

        // Müşteri ilişkisi
        $this->assertInstanceOf(Musteri::class, $iliski->musteri);

        // Mülk ilişkisi
        $this->assertInstanceOf(BaseMulk::class, $iliski->mulk);

        // Sorumlu personel ilişkisi
        $this->assertInstanceOf(User::class, $iliski->sorumluPersonel);

        // Polymorphic ilişkiler
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $iliski->iliskiNotlari());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $iliski->dokumanlar());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $iliski->resimler());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $iliski->hatirlatmalar());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $iliski = MusteriMulkIliskisi::factory()->create([
            'iliski_tipi' => IliskiTipi::TEKLIF_VERDI,
            'durum' => IliskiDurumu::BEKLEMEDE,
            'baslangic_tarihi' => '2024-01-15 10:30:00',
            'teklif_miktari' => 1500000.50,
            'avantajlar' => ['Merkezi konum', 'Uygun fiyat'],
            'dezavantajlar' => ['Eski yapı'],
            'etiketler' => ['VIP', 'Acil'],
        ]);

        $this->assertInstanceOf(IliskiTipi::class, $iliski->iliski_tipi);
        $this->assertInstanceOf(IliskiDurumu::class, $iliski->durum);
        $this->assertInstanceOf(Carbon::class, $iliski->baslangic_tarihi);
        $this->assertIsFloat($iliski->teklif_miktari);
        $this->assertIsArray($iliski->avantajlar);
        $this->assertIsArray($iliski->dezavantajlar);
        $this->assertIsArray($iliski->etiketler);
    }

    /** @test */
    public function it_calculates_iliski_tipi_attributes_correctly()
    {
        $iliski = MusteriMulkIliskisi::factory()->ilgileniyor()->create();

        $this->assertIsString($iliski->iliski_tipi_label);
        $this->assertIsString($iliski->iliski_tipi_color);
        $this->assertIsString($iliski->iliski_tipi_icon);
    }

    /** @test */
    public function it_calculates_durum_attributes_correctly()
    {
        $iliski = MusteriMulkIliskisi::factory()->create(['durum' => IliskiDurumu::AKTIF]);

        $this->assertIsString($iliski->durum_label);
        $this->assertIsString($iliski->durum_color);
        $this->assertIsString($iliski->durum_icon);
    }

    /** @test */
    public function it_calculates_ilgi_seviyesi_attributes_correctly()
    {
        // Yüksek ilgi
        $yuksekIlgi = MusteriMulkIliskisi::factory()->create(['ilgi_seviyesi' => 9]);
        $this->assertEquals('emerald', $yuksekIlgi->ilgi_seviyesi_color);
        $this->assertEquals('Çok Yüksek', $yuksekIlgi->ilgi_seviyesi_label);

        // Orta ilgi
        $ortaIlgi = MusteriMulkIliskisi::factory()->create(['ilgi_seviyesi' => 5]);
        $this->assertEquals('yellow', $ortaIlgi->ilgi_seviyesi_color);
        $this->assertEquals('Orta', $ortaIlgi->ilgi_seviyesi_label);

        // Düşük ilgi
        $dusukIlgi = MusteriMulkIliskisi::factory()->create(['ilgi_seviyesi' => 2]);
        $this->assertEquals('red', $dusukIlgi->ilgi_seviyesi_color);
        $this->assertEquals('Çok Düşük', $dusukIlgi->ilgi_seviyesi_label);
    }

    /** @test */
    public function it_calculates_aciliyet_seviyesi_attributes_correctly()
    {
        // Çok acil
        $cokAcil = MusteriMulkIliskisi::factory()->create(['aciliyet_seviyesi' => 10]);
        $this->assertEquals('red', $cokAcil->aciliyet_seviyesi_color);
        $this->assertEquals('Çok Acil', $cokAcil->aciliyet_seviyesi_label);

        // Orta aciliyet
        $ortaAciliyet = MusteriMulkIliskisi::factory()->create(['aciliyet_seviyesi' => 5]);
        $this->assertEquals('yellow', $ortaAciliyet->aciliyet_seviyesi_color);
        $this->assertEquals('Orta', $ortaAciliyet->aciliyet_seviyesi_label);
    }

    /** @test */
    public function it_formats_teklif_miktari_correctly()
    {
        $iliski1 = MusteriMulkIliskisi::factory()->create([
            'teklif_miktari' => 1500000,
            'teklif_para_birimi' => 'TRY'
        ]);
        $this->assertEquals('1.500.000 ₺', $iliski1->formatted_teklif_miktari);

        $iliski2 = MusteriMulkIliskisi::factory()->create([
            'teklif_miktari' => 100000,
            'teklif_para_birimi' => 'USD'
        ]);
        $this->assertEquals('100.000 $', $iliski2->formatted_teklif_miktari);

        $iliski3 = MusteriMulkIliskisi::factory()->create(['teklif_miktari' => null]);
        $this->assertEquals('Teklif Verilmemiş', $iliski3->formatted_teklif_miktari);
    }

    /** @test */
    public function it_calculates_iliski_suresi_correctly()
    {
        $baslangic = now()->subDays(45);
        $iliski = MusteriMulkIliskisi::factory()->create([
            'baslangic_tarihi' => $baslangic,
        ]);

        $this->assertEquals(45, $iliski->iliski_suresi);
        $this->assertEquals('1 ay 15 gün', $iliski->formatted_iliski_suresi);

        // Haftalık test
        $haftalikIliski = MusteriMulkIliskisi::factory()->create([
            'baslangic_tarihi' => now()->subDays(10),
        ]);
        $this->assertEquals('1 hafta 3 gün', $haftalikIliski->formatted_iliski_suresi);

        // Günlük test
        $gunlukIliski = MusteriMulkIliskisi::factory()->create([
            'baslangic_tarihi' => now()->subDays(3),
        ]);
        $this->assertEquals('3 gün', $gunlukIliski->formatted_iliski_suresi);
    }

    /** @test */
    public function it_calculates_son_aktivite_suresi_correctly()
    {
        // Son aktivite var
        $iliski1 = MusteriMulkIliskisi::factory()->create([
            'son_aktivite_tarihi' => now()->subDays(5),
        ]);
        $this->assertEquals(5, $iliski1->son_aktivite_suresi);
        $this->assertEquals('5 gün önce', $iliski1->formatted_son_aktivite_suresi);

        // Bugün aktivite
        $iliski2 = MusteriMulkIliskisi::factory()->create([
            'son_aktivite_tarihi' => now(),
        ]);
        $this->assertEquals('Bugün', $iliski2->formatted_son_aktivite_suresi);

        // Dün aktivite
        $iliski3 = MusteriMulkIliskisi::factory()->create([
            'son_aktivite_tarihi' => now()->subDay(),
        ]);
        $this->assertEquals('Dün', $iliski3->formatted_son_aktivite_suresi);

        // Hiç aktivite yok
        $iliski4 = MusteriMulkIliskisi::factory()->create([
            'son_aktivite_tarihi' => null,
        ]);
        $this->assertEquals('Hiç aktivite yok', $iliski4->formatted_son_aktivite_suresi);
    }

    /** @test */
    public function it_calculates_karar_tarihine_kalan_sure_correctly()
    {
        // Gelecek tarih
        $iliski1 = MusteriMulkIliskisi::factory()->create([
            'beklenen_karar_tarihi' => now()->addDays(5),
        ]);
        $this->assertEquals(5, $iliski1->karar_tarihine_kalan_sure);
        $this->assertEquals('5 gün kaldı', $iliski1->formatted_karar_tarihine_kalan_sure);

        // Bugün
        $iliski2 = MusteriMulkIliskisi::factory()->create([
            'beklenen_karar_tarihi' => now(),
        ]);
        $this->assertEquals('Bugün karar verilmeli', $iliski2->formatted_karar_tarihine_kalan_sure);

        // Geçmiş tarih
        $iliski3 = MusteriMulkIliskisi::factory()->create([
            'beklenen_karar_tarihi' => now()->subDays(3),
        ]);
        $this->assertEquals('3 gün gecikmiş', $iliski3->formatted_karar_tarihine_kalan_sure);

        // Tarih belirlenmemiş
        $iliski4 = MusteriMulkIliskisi::factory()->create([
            'beklenen_karar_tarihi' => null,
        ]);
        $this->assertEquals('Karar tarihi belirlenmemiş', $iliski4->formatted_karar_tarihine_kalan_sure);
    }

    /** @test */
    public function it_calculates_oncelik_correctly()
    {
        // Yüksek öncelikli ilişki
        $yuksekOncelik = MusteriMulkIliskisi::factory()->create([
            'iliski_tipi' => IliskiTipi::TEKLIF_VERDI,
            'ilgi_seviyesi' => 10,
            'aciliyet_seviyesi' => 9,
            'son_aktivite_tarihi' => now()->subDay(),
            'beklenen_karar_tarihi' => now()->addDays(2),
        ]);

        $this->assertGreaterThan(100, $yuksekOncelik->oncelik);

        // Düşük öncelikli ilişki
        $dusukOncelik = MusteriMulkIliskisi::factory()->create([
            'iliski_tipi' => IliskiTipi::ILGILENIYOR,
            'ilgi_seviyesi' => 3,
            'aciliyet_seviyesi' => 2,
            'son_aktivite_tarihi' => now()->subMonth(),
        ]);

        $this->assertLessThan(100, $dusukOncelik->oncelik);
    }

    /** @test */
    public function it_calculates_iliski_skoru_correctly()
    {
        // Yüksek skorlu ilişki
        $yuksekSkor = MusteriMulkIliskisi::factory()->create([
            'iliski_tipi' => IliskiTipi::SOZLESME_IMZALADI,
            'ilgi_seviyesi' => 10,
            'son_aktivite_tarihi' => now(),
        ]);

        $this->assertGreaterThan(80, $yuksekSkor->iliski_skoru);
        $this->assertEquals('Mükemmel', $yuksekSkor->iliski_skoru_label);
        $this->assertEquals('green', $yuksekSkor->iliski_skoru_color);

        // Düşük skorlu ilişki
        $dusukSkor = MusteriMulkIliskisi::factory()->create([
            'iliski_tipi' => IliskiTipi::ILGILENIYOR,
            'ilgi_seviyesi' => 2,
            'son_aktivite_tarihi' => now()->subMonths(2),
        ]);

        $this->assertLessThan(40, $dusukSkor->iliski_skoru);
        $this->assertContains($dusukSkor->iliski_skoru_label, ['Zayıf', 'Çok Zayıf']);
        $this->assertContains($dusukSkor->iliski_skoru_color, ['orange', 'red']);
    }

    /** @test */
    public function it_generates_display_name_correctly()
    {
        $musteri = Musteri::factory()->create();
        $mulk = BaseMulk::factory()->create(['baslik' => 'Test Mülk']);
        $iliski = MusteriMulkIliskisi::factory()->ilgileniyor()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
        ]);

        $displayName = $iliski->display_name;
        $this->assertStringContainsString($musteri->display_name, $displayName);
        $this->assertStringContainsString('Test Mülk', $displayName);
        $this->assertStringContainsString('İlgileniyor', $displayName);
    }

    /** @test */
    public function it_has_working_scopes()
    {
        // Farklı durumlar oluştur
        $aktifIliski = MusteriMulkIliskisi::factory()->ilgileniyor()->create();
        $tamamlanmisIliski = MusteriMulkIliskisi::factory()->satinAldi()->create();
        $iptalIliski = MusteriMulkIliskisi::factory()->iptalEtti()->create();

        // Active scope
        $aktifSonuclar = MusteriMulkIliskisi::active()->get();
        $this->assertTrue($aktifSonuclar->contains($aktifIliski));
        $this->assertFalse($aktifSonuclar->contains($iptalIliski));

        // Completed scope
        $tamamlanmisSonuclar = MusteriMulkIliskisi::completed()->get();
        $this->assertTrue($tamamlanmisSonuclar->contains($tamamlanmisIliski));

        // ByType scope
        $ilgilenenSonuclar = MusteriMulkIliskisi::byType(IliskiTipi::ILGILENIYOR)->get();
        $this->assertTrue($ilgilenenSonuclar->contains($aktifIliski));

        // HighInterest scope
        $yuksekIlgiIliski = MusteriMulkIliskisi::factory()->yuksekIlgi()->create();
        $yuksekIlgiSonuclar = MusteriMulkIliskisi::highInterest()->get();
        $this->assertTrue($yuksekIlgiSonuclar->contains($yuksekIlgiIliski));

        // WithOffer scope
        $teklifliIliski = MusteriMulkIliskisi::factory()->teklifVerdi()->create();
        $teklifliSonuclar = MusteriMulkIliskisi::withOffer()->get();
        $this->assertTrue($teklifliSonuclar->contains($teklifliIliski));
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $rules = MusteriMulkIliskisi::getValidationRules();

        $this->assertArrayHasKey('musteri_id', $rules);
        $this->assertArrayHasKey('mulk_id', $rules);
        $this->assertArrayHasKey('mulk_type', $rules);
        $this->assertArrayHasKey('iliski_tipi', $rules);
        $this->assertArrayHasKey('durum', $rules);

        $this->assertContains('required', explode('|', $rules['musteri_id']));
        $this->assertContains('required', explode('|', $rules['mulk_id']));
        $this->assertContains('required', explode('|', $rules['mulk_type']));
        $this->assertContains('required', explode('|', $rules['iliski_tipi']));
        $this->assertContains('required', explode('|', $rules['durum']));
    }

    /** @test */
    public function it_can_update_iliski_with_logging()
    {
        $iliski = MusteriMulkIliskisi::factory()->create([
            'ilgi_seviyesi' => 5,
            'durum' => IliskiDurumu::AKTIF,
            'notlar' => 'Eski not',
        ]);

        $iliski->updateIliski([
            'ilgi_seviyesi' => 8,
            'durum' => IliskiDurumu::BEKLEMEDE->value,
            'teklif_miktari' => 1000000,
        ]);

        $iliski->refresh();
        $this->assertEquals(8, $iliski->ilgi_seviyesi);
        $this->assertEquals(IliskiDurumu::BEKLEMEDE, $iliski->durum);
        $this->assertEquals(1000000, $iliski->teklif_miktari);

        // Değişiklik loglarının notlara eklendiğini kontrol et
        $this->assertStringContainsString('İlgi seviyesi 5 → 8', $iliski->notlar);
        $this->assertStringContainsString('Durum', $iliski->notlar);
        $this->assertStringContainsString('Teklif miktarı', $iliski->notlar);
    }

    /** @test */
    public function it_can_add_and_remove_tags()
    {
        $iliski = MusteriMulkIliskisi::factory()->create(['etiketler' => ['Mevcut']]);

        // Etiket ekle
        $iliski->addTag('Yeni Etiket');
        $this->assertContains('Yeni Etiket', $iliski->fresh()->etiketler);

        // Etiket kaldır
        $iliski->removeTag('Yeni Etiket');
        $this->assertNotContains('Yeni Etiket', $iliski->fresh()->etiketler);
    }

    /** @test */
    public function it_can_add_advantages_disadvantages_and_requests()
    {
        $iliski = MusteriMulkIliskisi::factory()->create([
            'avantajlar' => [],
            'dezavantajlar' => [],
            'ozel_istekler' => [],
        ]);

        // Avantaj ekle
        $iliski->addAdvantage('Merkezi konum');
        $this->assertContains('Merkezi konum', $iliski->fresh()->avantajlar);

        // Dezavantaj ekle
        $iliski->addDisadvantage('Yüksek fiyat');
        $this->assertContains('Yüksek fiyat', $iliski->fresh()->dezavantajlar);

        // Özel istek ekle
        $iliski->addSpecialRequest('Mobilyalı teslim');
        $this->assertContains('Mobilyalı teslim', $iliski->fresh()->ozel_istekler);
    }

    /** @test */
    public function it_counts_various_attributes_correctly()
    {
        $iliski = MusteriMulkIliskisi::factory()->create([
            'avantajlar' => ['Avantaj 1', 'Avantaj 2', 'Avantaj 3'],
            'dezavantajlar' => ['Dezavantaj 1', 'Dezavantaj 2'],
            'ozel_istekler' => ['İstek 1'],
            'etiketler' => ['Etiket 1', 'Etiket 2'],
        ]);

        $this->assertEquals(3, $iliski->avantaj_sayisi);
        $this->assertEquals(2, $iliski->dezavantaj_sayisi);
        $this->assertEquals(1, $iliski->ozel_istek_sayisi);
        $this->assertEquals(2, $iliski->etiket_sayisi);
    }

    /** @test */
    public function it_handles_decision_due_scope()
    {
        $yakindaKarar = MusteriMulkIliskisi::factory()->create([
            'beklenen_karar_tarihi' => now()->addDays(3),
        ]);

        $uzakKarar = MusteriMulkIliskisi::factory()->create([
            'beklenen_karar_tarihi' => now()->addDays(15),
        ]);

        $results = MusteriMulkIliskisi::decisionDue(7)->get();

        $this->assertTrue($results->contains($yakindaKarar));
        $this->assertFalse($results->contains($uzakKarar));
    }

    /** @test */
    public function it_handles_overdue_scope()
    {
        $gecikmiş = MusteriMulkIliskisi::factory()->create([
            'beklenen_karar_tarihi' => now()->subDays(3),
            'durum' => IliskiDurumu::AKTIF,
        ]);

        $zamaninda = MusteriMulkIliskisi::factory()->create([
            'beklenen_karar_tarihi' => now()->addDays(3),
            'durum' => IliskiDurumu::AKTIF,
        ]);

        $results = MusteriMulkIliskisi::overdue()->get();

        $this->assertTrue($results->contains($gecikmiş));
        $this->assertFalse($results->contains($zamaninda));
    }

    /** @test */
    public function it_handles_stale_scope()
    {
        $eskiAktivite = MusteriMulkIliskisi::factory()->create([
            'son_aktivite_tarihi' => now()->subDays(45),
            'durum' => IliskiDurumu::AKTIF,
        ]);

        $yeniAktivite = MusteriMulkIliskisi::factory()->create([
            'son_aktivite_tarihi' => now()->subDays(5),
            'durum' => IliskiDurumu::AKTIF,
        ]);

        $results = MusteriMulkIliskisi::stale(30)->get();

        $this->assertTrue($results->contains($eskiAktivite));
        $this->assertFalse($results->contains($yeniAktivite));
    }

    /** @test */
    public function it_handles_high_priority_scope()
    {
        $yuksekOncelik = MusteriMulkIliskisi::factory()->create([
            'ilgi_seviyesi' => 9,
            'aciliyet_seviyesi' => 8,
            'iliski_tipi' => IliskiTipi::TEKLIF_VERDI,
        ]);

        $dusukOncelik = MusteriMulkIliskisi::factory()->create([
            'ilgi_seviyesi' => 4,
            'aciliyet_seviyesi' => 3,
            'iliski_tipi' => IliskiTipi::ILGILENIYOR,
        ]);

        $results = MusteriMulkIliskisi::highPriority()->get();

        $this->assertTrue($results->contains($yuksekOncelik));
        $this->assertFalse($results->contains($dusukOncelik));
    }

    /** @test */
    public function it_generates_iliski_durumu_ozeti_correctly()
    {
        $iliski = MusteriMulkIliskisi::factory()->teklifVerdi()->create();

        $ozet = $iliski->iliski_durumu_ozeti;

        $this->assertIsArray($ozet);
        $this->assertArrayHasKey('tip', $ozet);
        $this->assertArrayHasKey('tip_rengi', $ozet);
        $this->assertArrayHasKey('durum', $ozet);
        $this->assertArrayHasKey('durum_rengi', $ozet);
        $this->assertArrayHasKey('ilgi_seviyesi', $ozet);
        $this->assertArrayHasKey('ilgi_rengi', $ozet);
        $this->assertArrayHasKey('aciliyet', $ozet);
        $this->assertArrayHasKey('aciliyet_rengi', $ozet);
        $this->assertArrayHasKey('sure', $ozet);
        $this->assertArrayHasKey('son_aktivite', $ozet);
        $this->assertArrayHasKey('oncelik', $ozet);
    }
}