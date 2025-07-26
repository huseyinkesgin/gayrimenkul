<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Musteri\Musteri;
use App\Models\MusteriHizmet;
use App\Models\MusteriMulkIliskisi;
use App\Models\Mulk\BaseMulk;
use App\Models\User;
use App\Models\Kisi\Kisi;
use App\Enums\HizmetTipi;
use App\Enums\HizmetSonucu;
use App\Enums\IliskiTipi;
use App\Enums\IliskiDurumu;
use App\Enums\DegerlendirmeTipi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MusteriModulEntegrasyonTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_complete_customer_journey()
    {
        // 1. Müşteri oluştur
        $kisi = Kisi::factory()->create([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
            'telefon' => '0532 123 45 67',
            'email' => 'ahmet@example.com',
        ]);

        $musteri = Musteri::factory()->create([
            'kisi_id' => $kisi->id,
            'potansiyel_deger' => 2500000,
            'kaynak' => 'İnternet',
        ]);

        // 2. Mülk oluştur
        $mulk = BaseMulk::factory()->create([
            'baslik' => 'Merkezi Ofis',
            'fiyat' => 2000000,
            'metrekare' => 150,
        ]);

        // 3. İlk iletişim - Telefon görüşmesi
        $ilkGorusme = MusteriHizmet::factory()->telefon()->create([
            'musteri_id' => $musteri->id,
            'hizmet_tarihi' => now()->subDays(10),
            'aciklama' => 'İlk iletişim, mülk hakkında bilgi verildi',
            'sonuc' => 'Müşteri ilgilendi, randevu talep etti',
            'sonuc_tipi' => HizmetSonucu::TAKIP_GEREKLI,
            'sure_dakika' => 15,
        ]);

        // 4. Müşteri-mülk ilişkisi başlat
        $mulkIliskisi = MusteriMulkIliskisi::factory()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
            'mulk_type' => get_class($mulk),
            'iliski_tipi' => IliskiTipi::ILGILENIYOR,
            'durum' => IliskiDurumu::AKTIF,
            'ilgi_seviyesi' => 6,
            'baslangic_tarihi' => now()->subDays(10),
            'referans_kaynak' => 'İnternet',
        ]);

        // 5. Randevu ve mülk ziyareti
        $ziyaret = MusteriHizmet::factory()->ziyaret()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
            'mulk_type' => get_class($mulk),
            'hizmet_tarihi' => now()->subDays(7),
            'aciklama' => 'Mülk ziyareti gerçekleştirildi',
            'sonuc' => 'Müşteri mülkü beğendi, fiyat konuşuldu',
            'sonuc_tipi' => HizmetSonucu::BASARILI,
            'sure_dakika' => 90,
            'lokasyon' => 'Mülk adresi',
            'degerlendirme' => [
                'tip' => DegerlendirmeTipi::OLUMLU->value,
                'puan' => 8,
                'notlar' => 'Başarılı ziyaret',
                'tarih' => now()->subDays(7)->toISOString(),
            ],
        ]);

        // 6. İlişki güncelle - İlgi seviyesi arttı
        $mulkIliskisi->updateIliski([
            'iliski_tipi' => IliskiTipi::GORUSTU->value,
            'ilgi_seviyesi' => 8,
            'avantajlar' => ['Merkezi konum', 'Uygun fiyat', 'Geniş alan'],
            'dezavantajlar' => ['Otopark sorunu'],
        ]);

        // 7. Takip görüşmesi
        $takipGorusme = MusteriHizmet::factory()->telefon()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
            'mulk_type' => get_class($mulk),
            'hizmet_tarihi' => now()->subDays(5),
            'aciklama' => 'Ziyaret sonrası takip görüşmesi',
            'sonuc' => 'Müşteri teklif hazırlanmasını istedi',
            'sonuc_tipi' => HizmetSonucu::TAKIP_GEREKLI,
            'sure_dakika' => 20,
        ]);

        // 8. Teklif sunumu toplantısı
        $teklifToplantisi = MusteriHizmet::factory()->toplanti()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
            'mulk_type' => get_class($mulk),
            'hizmet_tarihi' => now()->subDays(3),
            'aciklama' => 'Teklif sunumu yapıldı',
            'sonuc' => 'Müşteri 1.800.000 TL teklif verdi',
            'sonuc_tipi' => HizmetSonucu::TEKLIF_ALINDI,
            'sure_dakika' => 60,
            'katilimcilar' => ['Ahmet Yılmaz', 'Satış Danışmanı'],
            'degerlendirme' => [
                'tip' => DegerlendirmeTipi::OLUMLU->value,
                'puan' => 9,
                'notlar' => 'Müşteri ciddi, teklif verdi',
                'tarih' => now()->subDays(3)->toISOString(),
            ],
        ]);

        // 9. İlişki güncelle - Teklif verildi
        $mulkIliskisi->updateIliski([
            'iliski_tipi' => IliskiTipi::TEKLIF_VERDI->value,
            'durum' => IliskiDurumu::BEKLEMEDE->value,
            'ilgi_seviyesi' => 9,
            'teklif_miktari' => 1800000,
            'son_teklif_tarihi' => now()->subDays(3),
            'beklenen_karar_tarihi' => now()->addDays(7),
            'aciliyet_seviyesi' => 8,
        ]);

        // 10. Müzakere süreci
        $muzakere = MusteriHizmet::factory()->telefon()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
            'mulk_type' => get_class($mulk),
            'hizmet_tarihi' => now()->subDay(),
            'aciklama' => 'Fiyat müzakeresi yapıldı',
            'sonuc' => '1.900.000 TL\'de anlaşıldı',
            'sonuc_tipi' => HizmetSonucu::ANLASILDI,
            'sure_dakika' => 25,
        ]);

        // 11. Final ilişki durumu
        $mulkIliskisi->updateIliski([
            'iliski_tipi' => IliskiTipi::MUZAKERE_EDIYOR->value,
            'teklif_miktari' => 1900000,
            'son_teklif_tarihi' => now()->subDay(),
            'beklenen_karar_tarihi' => now()->addDays(3),
            'finansman_durumu' => 'Kredi onayı bekleniyor',
        ]);

        // Test sonuçları
        $this->assertDatabaseHas('musteri', [
            'id' => $musteri->id,
            'potansiyel_deger' => 2500000,
        ]);

        $this->assertDatabaseHas('musteri_mulk_iliskileri', [
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
            'iliski_tipi' => IliskiTipi::MUZAKERE_EDIYOR->value,
            'teklif_miktari' => 1900000,
        ]);

        $this->assertCount(5, $musteri->hizmetler);
        $this->assertEquals('Premium', $musteri->musteri_segmenti);
        $this->assertEquals('Aktif', $musteri->musteri_durumu);

        // İlişki skoru yüksek olmalı
        $this->assertGreaterThan(70, $mulkIliskisi->fresh()->iliski_skoru);
    }

    /** @test */
    public function it_handles_failed_customer_journey()
    {
        // Başarısız müşteri yolculuğu testi
        $musteri = Musteri::factory()->create();
        $mulk = BaseMulk::factory()->create();

        // İlk iletişim
        $ilkGorusme = MusteriHizmet::factory()->telefon()->create([
            'musteri_id' => $musteri->id,
            'hizmet_tarihi' => now()->subDays(20),
            'sonuc_tipi' => HizmetSonucu::ILGISIZ,
            'degerlendirme' => [
                'tip' => DegerlendirmeTipi::OLUMSUZ->value,
                'puan' => 3,
                'notlar' => 'Müşteri ilgisiz',
                'tarih' => now()->subDays(20)->toISOString(),
            ],
        ]);

        // Düşük ilgi seviyesi ile ilişki
        $mulkIliskisi = MusteriMulkIliskisi::factory()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
            'mulk_type' => get_class($mulk),
            'iliski_tipi' => IliskiTipi::ILGILENIYOR,
            'durum' => IliskiDurumu::AKTIF,
            'ilgi_seviyesi' => 3,
            'son_aktivite_tarihi' => now()->subDays(20),
        ]);

        // Takip görüşmesi başarısız
        $takipGorusme = MusteriHizmet::factory()->telefon()->create([
            'musteri_id' => $musteri->id,
            'hizmet_tarihi' => now()->subDays(15),
            'sonuc_tipi' => HizmetSonucu::ULASILAMADI,
            'degerlendirme' => [
                'tip' => DegerlendirmeTipi::OLUMSUZ->value,
                'puan' => 2,
                'notlar' => 'Müşteriye ulaşılamadı',
                'tarih' => now()->subDays(15)->toISOString(),
            ],
        ]);

        // İlişki iptal
        $mulkIliskisi->updateIliski([
            'iliski_tipi' => IliskiTipi::IPTAL_ETTI->value,
            'durum' => IliskiDurumu::IPTAL->value,
            'bitis_tarihi' => now()->subDays(10),
            'karar_verme_sebebi' => 'Müşteri başka mülk aldı',
        ]);

        // Test sonuçları
        $this->assertEquals(IliskiTipi::IPTAL_ETTI, $mulkIliskisi->fresh()->iliski_tipi);
        $this->assertEquals(IliskiDurumu::IPTAL, $mulkIliskisi->fresh()->durum);
        $this->assertEquals('Durgun', $musteri->musteri_durumu);
        $this->assertLessThan(30, $mulkIliskisi->fresh()->iliski_skoru);
    }

    /** @test */
    public function it_tracks_multiple_property_interests()
    {
        $musteri = Musteri::factory()->create();
        $mulk1 = BaseMulk::factory()->create(['baslik' => 'Ofis 1']);
        $mulk2 = BaseMulk::factory()->create(['baslik' => 'Ofis 2']);
        $mulk3 = BaseMulk::factory()->create(['baslik' => 'Ofis 3']);

        // Birden fazla mülk ile ilişki
        $iliski1 = MusteriMulkIliskisi::factory()->ilgileniyor()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk1->id,
            'ilgi_seviyesi' => 7,
        ]);

        $iliski2 = MusteriMulkIliskisi::factory()->teklifVerdi()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk2->id,
            'ilgi_seviyesi' => 9,
            'teklif_miktari' => 1500000,
        ]);

        $iliski3 = MusteriMulkIliskisi::factory()->degerlendiriyor()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk3->id,
            'ilgi_seviyesi' => 8,
        ]);

        // Her mülk için farklı hizmetler
        MusteriHizmet::factory()->telefon()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk1->id,
            'aciklama' => 'Ofis 1 hakkında bilgi',
        ]);

        MusteriHizmet::factory()->ziyaret()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk2->id,
            'aciklama' => 'Ofis 2 ziyareti',
        ]);

        MusteriHizmet::factory()->toplanti()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk3->id,
            'aciklama' => 'Ofis 3 teklif sunumu',
        ]);

        // Test sonuçları
        $this->assertCount(3, $musteri->mulkIliskileri);
        $this->assertCount(3, $musteri->hizmetler);

        // Yüksek ilgi seviyesindeki mülkler
        $yuksekIlgiMulkleri = $musteri->yuksekIlgiMulkleri;
        $this->assertCount(3, $yuksekIlgiMulkleri); // Hepsi 7+ puan

        // En yüksek öncelikli ilişki
        $oncelikSiralama = $musteri->mulkIliskileri->sortByDesc('oncelik');
        $this->assertEquals($iliski2->id, $oncelikSiralama->first()->id); // Teklif veren en yüksek öncelik
    }

    /** @test */
    public function it_calculates_comprehensive_customer_metrics()
    {
        $musteri = Musteri::factory()->create([
            'potansiyel_deger' => 3500000,
            'kayit_tarihi' => now()->subDays(180),
        ]);

        // Çeşitli hizmetler
        MusteriHizmet::factory()->count(8)->create([
            'musteri_id' => $musteri->id,
            'hizmet_tarihi' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);

        MusteriHizmet::factory()->count(12)->create([
            'musteri_id' => $musteri->id,
            'hizmet_tarihi' => $this->faker->dateTimeBetween('-6 months', '-30 days'),
        ]);

        // Mülk ilişkileri
        MusteriMulkIliskisi::factory()->count(6)->create([
            'musteri_id' => $musteri->id,
            'son_aktivite_tarihi' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);

        // Metrik hesaplamaları
        $this->assertEquals(180, $musteri->musteri_yasi);
        $this->assertEquals('Premium', $musteri->musteri_segmenti);
        $this->assertEquals('Aktif', $musteri->musteri_durumu);
        $this->assertCount(20, $musteri->hizmetler);
        $this->assertCount(6, $musteri->mulkIliskileri);
    }

    /** @test */
    public function it_handles_service_evaluation_workflow()
    {
        $musteri = Musteri::factory()->create();
        $personel = User::factory()->create();

        // Hizmet oluştur
        $hizmet = MusteriHizmet::factory()->create([
            'musteri_id' => $musteri->id,
            'personel_id' => $personel->id,
            'hizmet_tipi' => HizmetTipi::TOPLANTI,
            'hizmet_tarihi' => now()->subHours(2),
            'bitis_tarihi' => now()->subHours(1),
            'sure_dakika' => 60,
        ]);

        // Değerlendirme ekle
        $hizmet->createEvaluation(DegerlendirmeTipi::OLUMLU, 8, 'Başarılı toplantı');

        // Etiket ekle
        $hizmet->addTag('Başarılı');
        $hizmet->addTag('VIP');

        // Katılımcı ekle
        $hizmet->addParticipant('Müşteri Temsilcisi');
        $hizmet->addParticipant('Teknik Uzman');

        // Dosya ekle
        $hizmet->addFile('toplanti_notlari.pdf', '/uploads/toplanti_notlari.pdf');

        // Test sonuçları
        $hizmet->refresh();
        $this->assertEquals(DegerlendirmeTipi::OLUMLU, $hizmet->degerlendirme_tipi);
        $this->assertEquals(8, $hizmet->degerlendirme_puani);
        $this->assertContains('Başarılı', $hizmet->etiketler);
        $this->assertContains('VIP', $hizmet->etiketler);
        $this->assertCount(2, $hizmet->katilimcilar);
        $this->assertCount(1, $hizmet->dosyalar);
        $this->assertEquals('1 saat', $hizmet->formatted_duration);
        $this->assertEquals('1 saat', $hizmet->formatted_gercek_sure);
    }

    /** @test */
    public function it_handles_relationship_priority_calculation()
    {
        $musteri = Musteri::factory()->create();

        // Farklı öncelik seviyelerinde ilişkiler
        $dusukOncelik = MusteriMulkIliskisi::factory()->create([
            'musteri_id' => $musteri->id,
            'iliski_tipi' => IliskiTipi::ILGILENIYOR,
            'ilgi_seviyesi' => 3,
            'aciliyet_seviyesi' => 2,
            'son_aktivite_tarihi' => now()->subMonth(),
        ]);

        $ortaOncelik = MusteriMulkIliskisi::factory()->create([
            'musteri_id' => $musteri->id,
            'iliski_tipi' => IliskiTipi::GORUSTU,
            'ilgi_seviyesi' => 6,
            'aciliyet_seviyesi' => 5,
            'son_aktivite_tarihi' => now()->subWeek(),
        ]);

        $yuksekOncelik = MusteriMulkIliskisi::factory()->create([
            'musteri_id' => $musteri->id,
            'iliski_tipi' => IliskiTipi::TEKLIF_VERDI,
            'ilgi_seviyesi' => 9,
            'aciliyet_seviyesi' => 8,
            'son_aktivite_tarihi' => now()->subDay(),
            'beklenen_karar_tarihi' => now()->addDays(2),
        ]);

        // Öncelik sıralaması
        $this->assertGreaterThan($ortaOncelik->oncelik, $yuksekOncelik->oncelik);
        $this->assertGreaterThan($dusukOncelik->oncelik, $ortaOncelik->oncelik);

        // Yüksek öncelik scope'u
        $yuksekOncelikIliskiler = MusteriMulkIliskisi::highPriority()->get();
        $this->assertTrue($yuksekOncelikIliskiler->contains($yuksekOncelik));
        $this->assertFalse($yuksekOncelikIliskiler->contains($dusukOncelik));
    }

    /** @test */
    public function it_generates_comprehensive_relationship_summary()
    {
        $musteri = Musteri::factory()->create();
        $mulk = BaseMulk::factory()->create(['fiyat' => 2000000]);

        $iliski = MusteriMulkIliskisi::factory()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk->id,
            'iliski_tipi' => IliskiTipi::TEKLIF_VERDI,
            'durum' => IliskiDurumu::BEKLEMEDE,
            'ilgi_seviyesi' => 8,
            'aciliyet_seviyesi' => 7,
            'teklif_miktari' => 1800000,
            'avantajlar' => ['Merkezi konum', 'Uygun fiyat'],
            'dezavantajlar' => ['Otopark sorunu'],
            'etiketler' => ['VIP', 'Acil'],
        ]);

        $ozet = $iliski->iliski_durumu_ozeti;

        $this->assertIsArray($ozet);
        $this->assertEquals('Teklif Verdi', $ozet['tip']);
        $this->assertEquals('Beklemede', $ozet['durum']);
        $this->assertEquals('Yüksek', $ozet['ilgi_seviyesi']);
        $this->assertEquals('Acil', $ozet['aciliyet']);
        $this->assertIsString($ozet['sure']);
        $this->assertIsString($ozet['son_aktivite']);
        $this->assertIsInt($ozet['oncelik']);

        // Sayısal değerler
        $this->assertEquals(2, $iliski->avantaj_sayisi);
        $this->assertEquals(1, $iliski->dezavantaj_sayisi);
        $this->assertEquals(2, $iliski->etiket_sayisi);

        // Formatlanmış değerler
        $this->assertEquals('1.800.000 ₺', $iliski->formatted_teklif_miktari);
        $this->assertGreaterThan(60, $iliski->iliski_skoru);
    }
}