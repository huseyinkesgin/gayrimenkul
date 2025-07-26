<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\MusteriHizmet;
use App\Models\Musteri\Musteri;
use App\Models\User;
use App\Models\Mulk\BaseMulk;
use App\Enums\HizmetTipi;
use App\Enums\HizmetSonucu;
use App\Enums\DegerlendirmeTipi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MusteriHizmetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_musteri_hizmet()
    {
        $musteri = Musteri::factory()->create();
        $personel = User::factory()->create();

        $hizmet = MusteriHizmet::create([
            'musteri_id' => $musteri->id,
            'personel_id' => $personel->id,
            'hizmet_tipi' => HizmetTipi::TELEFON,
            'hizmet_tarihi' => now(),
            'aciklama' => 'Test telefon görüşmesi',
            'sure_dakika' => 30,
            'aktif_mi' => true,
        ]);

        $this->assertInstanceOf(MusteriHizmet::class, $hizmet);
        $this->assertEquals($musteri->id, $hizmet->musteri_id);
        $this->assertEquals($personel->id, $hizmet->personel_id);
        $this->assertEquals(HizmetTipi::TELEFON, $hizmet->hizmet_tipi);
        $this->assertEquals(30, $hizmet->sure_dakika);
        $this->assertTrue($hizmet->aktif_mi);
    }

    /** @test */
    public function it_has_correct_relationships()
    {
        $hizmet = MusteriHizmet::factory()->create();

        // Müşteri ilişkisi
        $this->assertInstanceOf(Musteri::class, $hizmet->musteri);

        // Personel ilişkisi
        $this->assertInstanceOf(User::class, $hizmet->personel);

        // Mülk ilişkisi (opsiyonel)
        $hizmetWithProperty = MusteriHizmet::factory()->withProperty()->create();
        $this->assertInstanceOf(BaseMulk::class, $hizmetWithProperty->mulk);

        // Polymorphic ilişkiler
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $hizmet->notlar());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $hizmet->dokumanlar());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $hizmet->resimler());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $hizmet = MusteriHizmet::factory()->create([
            'hizmet_tipi' => HizmetTipi::TOPLANTI,
            'sonuc_tipi' => HizmetSonucu::BASARILI,
            'hizmet_tarihi' => '2024-01-15 10:30:00',
            'degerlendirme' => [
                'tip' => DegerlendirmeTipi::OLUMLU->value,
                'puan' => 8,
                'notlar' => 'Başarılı görüşme',
            ],
            'katilimcilar' => ['Ahmet Yılmaz', 'Mehmet Demir'],
            'etiketler' => ['VIP', 'Acil'],
        ]);

        $this->assertInstanceOf(HizmetTipi::class, $hizmet->hizmet_tipi);
        $this->assertInstanceOf(HizmetSonucu::class, $hizmet->sonuc_tipi);
        $this->assertInstanceOf(Carbon::class, $hizmet->hizmet_tarihi);
        $this->assertIsArray($hizmet->degerlendirme);
        $this->assertIsArray($hizmet->katilimcilar);
        $this->assertIsArray($hizmet->etiketler);
    }

    /** @test */
    public function it_calculates_hizmet_tipi_attributes_correctly()
    {
        $hizmet = MusteriHizmet::factory()->telefon()->create();

        $this->assertIsString($hizmet->hizmet_tipi_label);
        $this->assertIsString($hizmet->hizmet_tipi_icon);
        $this->assertIsString($hizmet->hizmet_tipi_color);
    }

    /** @test */
    public function it_calculates_sonuc_tipi_attributes_correctly()
    {
        $hizmet = MusteriHizmet::factory()->basarili()->create();

        $this->assertIsString($hizmet->sonuc_tipi_label);
        $this->assertIsString($hizmet->sonuc_tipi_color);
        $this->assertIsString($hizmet->sonuc_tipi_icon);
    }

    /** @test */
    public function it_handles_degerlendirme_attributes_correctly()
    {
        $hizmet = MusteriHizmet::factory()->create([
            'degerlendirme' => [
                'tip' => DegerlendirmeTipi::OLUMLU->value,
                'puan' => 9,
                'notlar' => 'Mükemmel görüşme',
                'tarih' => now()->toISOString(),
            ],
        ]);

        $this->assertEquals(DegerlendirmeTipi::OLUMLU, $hizmet->degerlendirme_tipi);
        $this->assertEquals(9, $hizmet->degerlendirme_puani);
        $this->assertEquals('Mükemmel görüşme', $hizmet->degerlendirme_notlari);
        $this->assertInstanceOf(Carbon::class, $hizmet->degerlendirme_tarihi);
        $this->assertIsString($hizmet->degerlendirme_color);
        $this->assertIsString($hizmet->degerlendirme_icon);
        $this->assertIsString($hizmet->degerlendirme_label);
    }

    /** @test */
    public function it_formats_duration_correctly()
    {
        // Dakika formatı
        $hizmet1 = MusteriHizmet::factory()->create(['sure_dakika' => 45]);
        $this->assertEquals('45 dakika', $hizmet1->formatted_duration);

        // Saat formatı
        $hizmet2 = MusteriHizmet::factory()->create(['sure_dakika' => 120]);
        $this->assertEquals('2 saat', $hizmet2->formatted_duration);

        // Saat + dakika formatı
        $hizmet3 = MusteriHizmet::factory()->create(['sure_dakika' => 135]);
        $this->assertEquals('2 saat 15 dakika', $hizmet3->formatted_duration);

        // Belirtilmemiş
        $hizmet4 = MusteriHizmet::factory()->create(['sure_dakika' => null]);
        $this->assertEquals('Belirtilmemiş', $hizmet4->formatted_duration);
    }

    /** @test */
    public function it_formats_maliyet_correctly()
    {
        $hizmet1 = MusteriHizmet::factory()->create([
            'maliyet' => 150.50,
            'para_birimi' => 'TRY'
        ]);
        $this->assertEquals('150,50 ₺', $hizmet1->formatted_maliyet);

        $hizmet2 = MusteriHizmet::factory()->create([
            'maliyet' => 100.00,
            'para_birimi' => 'USD'
        ]);
        $this->assertEquals('100,00 $', $hizmet2->formatted_maliyet);

        $hizmet3 = MusteriHizmet::factory()->create(['maliyet' => null]);
        $this->assertEquals('Belirtilmemiş', $hizmet3->formatted_maliyet);
    }

    /** @test */
    public function it_calculates_gercek_sure_correctly()
    {
        $baslangic = now();
        $bitis = $baslangic->copy()->addHours(2)->addMinutes(30);

        $hizmet = MusteriHizmet::factory()->create([
            'hizmet_tarihi' => $baslangic,
            'bitis_tarihi' => $bitis,
        ]);

        $this->assertEquals(150, $hizmet->gercek_sure); // 2.5 saat = 150 dakika

        // Bitiş tarihi olmayan hizmet
        $hizmet2 = MusteriHizmet::factory()->create([
            'hizmet_tarihi' => $baslangic,
            'bitis_tarihi' => null,
        ]);

        $this->assertNull($hizmet2->gercek_sure);
    }

    /** @test */
    public function it_counts_katilimci_etiket_dosya_correctly()
    {
        $hizmet = MusteriHizmet::factory()->create([
            'katilimcilar' => ['Ahmet', 'Mehmet', 'Ayşe'],
            'etiketler' => ['VIP', 'Acil'],
            'dosyalar' => [
                ['name' => 'doc1.pdf', 'path' => '/path1'],
                ['name' => 'doc2.pdf', 'path' => '/path2'],
            ],
        ]);

        $this->assertEquals(3, $hizmet->katilimci_sayisi);
        $this->assertEquals(2, $hizmet->etiket_sayisi);
        $this->assertEquals(2, $hizmet->dosya_sayisi);
    }

    /** @test */
    public function it_determines_hizmet_durumu_correctly()
    {
        // Takip bekliyor
        $hizmet1 = MusteriHizmet::factory()->create([
            'takip_tarihi' => now()->addDays(3),
        ]);
        $this->assertEquals('Takip Bekliyor', $hizmet1->hizmet_durumu);

        // Başarılı
        $hizmet2 = MusteriHizmet::factory()->basarili()->create();
        $this->assertContains($hizmet2->hizmet_durumu, ['Tamamlandı', 'Takip Gerekli']);

        // Başarısız
        $hizmet3 = MusteriHizmet::factory()->basarisiz()->create();
        $this->assertContains($hizmet3->hizmet_durumu, ['Başarısız', 'Takip Gerekli']);
    }

    /** @test */
    public function it_generates_display_name_correctly()
    {
        $musteri = Musteri::factory()->create();
        $hizmet = MusteriHizmet::factory()->telefon()->create([
            'musteri_id' => $musteri->id,
            'hizmet_tarihi' => Carbon::parse('2024-01-15 14:30:00'),
        ]);

        $displayName = $hizmet->display_name;
        $this->assertStringContainsString($musteri->display_name, $displayName);
        $this->assertStringContainsString('15.01.2024 14:30', $displayName);
    }

    /** @test */
    public function it_has_working_scopes()
    {
        // Hizmet tipleri oluştur
        $telefonHizmet = MusteriHizmet::factory()->telefon()->create();
        $toplantiHizmet = MusteriHizmet::factory()->toplanti()->create();
        $emailHizmet = MusteriHizmet::factory()->email()->create();

        // ByType scope
        $telefonSonuclari = MusteriHizmet::byType(HizmetTipi::TELEFON)->get();
        $this->assertCount(1, $telefonSonuclari);
        $this->assertTrue($telefonSonuclari->contains($telefonHizmet));

        // ByCustomer scope
        $musteriHizmetleri = MusteriHizmet::byCustomer($telefonHizmet->musteri_id)->get();
        $this->assertTrue($musteriHizmetleri->contains($telefonHizmet));

        // ByPersonel scope
        $personelHizmetleri = MusteriHizmet::byPersonel($telefonHizmet->personel_id)->get();
        $this->assertTrue($personelHizmetleri->contains($telefonHizmet));

        // Today scope
        $bugunHizmet = MusteriHizmet::factory()->today()->create();
        $bugunSonuclari = MusteriHizmet::today()->get();
        $this->assertTrue($bugunSonuclari->contains($bugunHizmet));

        // ThisWeek scope
        $buHaftaSonuclari = MusteriHizmet::thisWeek()->get();
        $this->assertTrue($buHaftaSonuclari->contains($bugunHizmet));

        // Positive scope
        $basariliHizmet = MusteriHizmet::factory()->basarili()->create();
        $olumluSonuclari = MusteriHizmet::positive()->get();
        $this->assertTrue($olumluSonuclari->contains($basariliHizmet));
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $rules = MusteriHizmet::getValidationRules();

        $this->assertArrayHasKey('musteri_id', $rules);
        $this->assertArrayHasKey('personel_id', $rules);
        $this->assertArrayHasKey('hizmet_tipi', $rules);
        $this->assertArrayHasKey('hizmet_tarihi', $rules);

        $this->assertContains('required', explode('|', $rules['musteri_id']));
        $this->assertContains('required', explode('|', $rules['personel_id']));
        $this->assertContains('required', explode('|', $rules['hizmet_tipi']));
        $this->assertContains('required', explode('|', $rules['hizmet_tarihi']));
    }

    /** @test */
    public function it_can_create_evaluation()
    {
        $hizmet = MusteriHizmet::factory()->create();

        $hizmet->createEvaluation(DegerlendirmeTipi::OLUMLU, 8, 'Başarılı görüşme');

        $hizmet->refresh();
        $this->assertEquals(DegerlendirmeTipi::OLUMLU, $hizmet->degerlendirme_tipi);
        $this->assertEquals(8, $hizmet->degerlendirme_puani);
        $this->assertEquals('Başarılı görüşme', $hizmet->degerlendirme_notlari);
    }

    /** @test */
    public function it_can_add_and_remove_tags()
    {
        $hizmet = MusteriHizmet::factory()->create(['etiketler' => ['Mevcut']]);

        // Etiket ekle
        $hizmet->addTag('Yeni Etiket');
        $this->assertContains('Yeni Etiket', $hizmet->fresh()->etiketler);

        // Aynı etiketi tekrar ekleme (eklenmemeli)
        $hizmet->addTag('Yeni Etiket');
        $etiketler = $hizmet->fresh()->etiketler;
        $this->assertEquals(1, array_count_values($etiketler)['Yeni Etiket']);

        // Etiket kaldır
        $hizmet->removeTag('Yeni Etiket');
        $this->assertNotContains('Yeni Etiket', $hizmet->fresh()->etiketler);
    }

    /** @test */
    public function it_can_add_and_remove_participants()
    {
        $hizmet = MusteriHizmet::factory()->create(['katilimcilar' => ['Mevcut Kişi']]);

        // Katılımcı ekle
        $hizmet->addParticipant('Yeni Katılımcı');
        $this->assertContains('Yeni Katılımcı', $hizmet->fresh()->katilimcilar);

        // Katılımcı kaldır
        $hizmet->removeParticipant('Yeni Katılımcı');
        $this->assertNotContains('Yeni Katılımcı', $hizmet->fresh()->katilimcilar);
    }

    /** @test */
    public function it_can_add_files()
    {
        $hizmet = MusteriHizmet::factory()->create(['dosyalar' => []]);

        $hizmet->addFile('test.pdf', '/uploads/test.pdf');

        $dosyalar = $hizmet->fresh()->dosyalar;
        $this->assertCount(1, $dosyalar);
        $this->assertEquals('test.pdf', $dosyalar[0]['name']);
        $this->assertEquals('/uploads/test.pdf', $dosyalar[0]['path']);
    }

    /** @test */
    public function it_generates_service_summary_correctly()
    {
        $hizmet = MusteriHizmet::factory()->telefon()->create([
            'sure_dakika' => 30,
            'maliyet' => 100,
            'para_birimi' => 'TRY',
        ]);

        $summary = $hizmet->getServiceSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('id', $summary);
        $this->assertArrayHasKey('musteri', $summary);
        $this->assertArrayHasKey('personel', $summary);
        $this->assertArrayHasKey('tip', $summary);
        $this->assertArrayHasKey('tarih', $summary);
        $this->assertArrayHasKey('sure', $summary);
        $this->assertArrayHasKey('maliyet', $summary);
        $this->assertArrayHasKey('durum', $summary);
    }

    /** @test */
    public function it_handles_date_range_scope()
    {
        $startDate = now()->subDays(7);
        $endDate = now()->subDays(1);

        $hizmetInRange = MusteriHizmet::factory()->create([
            'hizmet_tarihi' => now()->subDays(3),
        ]);

        $hizmetOutRange = MusteriHizmet::factory()->create([
            'hizmet_tarihi' => now()->subDays(10),
        ]);

        $results = MusteriHizmet::byDateRange($startDate, $endDate)->get();

        $this->assertTrue($results->contains($hizmetInRange));
        $this->assertFalse($results->contains($hizmetOutRange));
    }

    /** @test */
    public function it_handles_duration_scope()
    {
        $shortHizmet = MusteriHizmet::factory()->create(['sure_dakika' => 15]);
        $mediumHizmet = MusteriHizmet::factory()->create(['sure_dakika' => 60]);
        $longHizmet = MusteriHizmet::factory()->create(['sure_dakika' => 180]);

        // 30-120 dakika arası
        $results = MusteriHizmet::byDuration(30, 120)->get();

        $this->assertFalse($results->contains($shortHizmet));
        $this->assertTrue($results->contains($mediumHizmet));
        $this->assertFalse($results->contains($longHizmet));
    }

    /** @test */
    public function it_handles_cost_scope()
    {
        $cheapHizmet = MusteriHizmet::factory()->create(['maliyet' => 50]);
        $mediumHizmet = MusteriHizmet::factory()->create(['maliyet' => 200]);
        $expensiveHizmet = MusteriHizmet::factory()->create(['maliyet' => 500]);

        // 100-300 TL arası
        $results = MusteriHizmet::byCost(100, 300)->get();

        $this->assertFalse($results->contains($cheapHizmet));
        $this->assertTrue($results->contains($mediumHizmet));
        $this->assertFalse($results->contains($expensiveHizmet));
    }

    /** @test */
    public function it_handles_tag_scope()
    {
        $taggedHizmet = MusteriHizmet::factory()->withTags(['VIP', 'Acil'])->create();
        $untaggedHizmet = MusteriHizmet::factory()->create(['etiketler' => ['Normal']]);

        $vipResults = MusteriHizmet::byTag('VIP')->get();

        $this->assertTrue($vipResults->contains($taggedHizmet));
        $this->assertFalse($vipResults->contains($untaggedHizmet));
    }

    /** @test */
    public function it_handles_location_scope()
    {
        $istanbulHizmet = MusteriHizmet::factory()->create(['lokasyon' => 'İstanbul Kadıköy']);
        $ankaraHizmet = MusteriHizmet::factory()->create(['lokasyon' => 'Ankara Çankaya']);

        $istanbulResults = MusteriHizmet::byLocation('İstanbul')->get();

        $this->assertTrue($istanbulResults->contains($istanbulHizmet));
        $this->assertFalse($istanbulResults->contains($ankaraHizmet));
    }
}