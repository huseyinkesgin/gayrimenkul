<?php

namespace Tests\Unit\Models\Musteri;

use Tests\TestCase;
use App\Models\Musteri\Musteri;
use App\Models\Musteri\MusteriKategori;
use App\Models\Musteri\Firma;
use App\Models\Kisi\Kisi;
use App\Models\MusteriHizmet;
use App\Models\MusteriMulkIliskisi;
use App\Models\Mulk\BaseMulk;
use App\Models\User;
use App\Enums\MusteriTipi;
use App\Enums\MusteriKategorisi;
use App\Enums\HizmetTipi;
use App\Enums\IliskiTipi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MusteriIliskiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_manage_musteri_kategorileri()
    {
        // Kategorileri oluştur
        MusteriKategori::createFromEnum();
        
        $musteri = Musteri::factory()->create();

        // Kategori ekle
        $musteri->addKategori(MusteriKategorisi::ALICI, 'Potansiyel alıcı');
        $musteri->addKategori(MusteriKategorisi::VIP, 'VIP müşteri');

        $this->assertTrue($musteri->hasKategori(MusteriKategorisi::ALICI));
        $this->assertTrue($musteri->hasKategori(MusteriKategorisi::VIP));

        // Aktif kategoriler
        $aktifKategoriler = $musteri->aktifKategoriler;
        $this->assertCount(2, $aktifKategoriler);

        // Kategori kaldır
        $musteri->removeKategori(MusteriKategorisi::ALICI);
        $this->assertFalse($musteri->fresh()->hasKategori(MusteriKategorisi::ALICI));
        $this->assertTrue($musteri->fresh()->hasKategori(MusteriKategorisi::VIP));
    }

    /** @test */
    public function it_can_manage_firma_relationships()
    {
        $musteri = Musteri::factory()->create(['tip' => MusteriTipi::KURUMSAL]);
        $firma1 = Firma::factory()->create();
        $firma2 = Firma::factory()->create();

        // Firma ilişkisi ekle
        $musteri->firmalar()->attach($firma1->id, [
            'pozisyon' => 'Genel Müdür',
            'yetki_seviyesi' => 10,
            'aktif_mi' => true,
        ]);

        $musteri->firmalar()->attach($firma2->id, [
            'pozisyon' => 'Satış Temsilcisi',
            'yetki_seviyesi' => 5,
            'aktif_mi' => true,
        ]);

        // Firma ilişkilerini kontrol et
        $this->assertCount(2, $musteri->firmalar);
        $this->assertCount(2, $musteri->aktifFirmalar);

        // Ana firma (en yüksek yetki seviyesi)
        $anaFirma = $musteri->anaFirma();
        $this->assertEquals($firma1->id, $anaFirma->id);
        $this->assertEquals('Genel Müdür', $anaFirma->pivot->pozisyon);
    }

    /** @test */
    public function it_can_track_mulk_relationships()
    {
        $musteri = Musteri::factory()->create();
        $mulk1 = BaseMulk::factory()->create();
        $mulk2 = BaseMulk::factory()->create();

        // Mülk ilişkileri oluştur
        $iliski1 = MusteriMulkIliskisi::factory()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk1->id,
            'iliski_tipi' => IliskiTipi::ILGILENIYOR,
            'ilgi_seviyesi' => 8,
            'durum' => 'aktif',
        ]);

        $iliski2 = MusteriMulkIliskisi::factory()->create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $mulk2->id,
            'iliski_tipi' => IliskiTipi::TEKLIF_VERDI,
            'ilgi_seviyesi' => 9,
            'durum' => 'aktif',
        ]);

        // İlişkileri kontrol et
        $this->assertCount(2, $musteri->mulkIliskileri);
        $this->assertCount(2, $musteri->aktifMulkIliskileri);

        // Yüksek ilgi seviyesindeki mülkler
        $yuksekIlgiMulkleri = $musteri->yuksekIlgiMulkleri;
        $this->assertCount(2, $yuksekIlgiMulkleri); // İkisi de 7+ puan
    }

    /** @test */
    public function it_can_track_hizmet_history()
    {
        $musteri = Musteri::factory()->create();
        $personel = User::factory()->create();

        // Farklı hizmetler oluştur
        $telefonHizmet = MusteriHizmet::factory()->telefon()->create([
            'musteri_id' => $musteri->id,
            'personel_id' => $personel->id,
            'hizmet_tarihi' => now()->subDays(5),
        ]);

        $toplantiHizmet = MusteriHizmet::factory()->toplanti()->create([
            'musteri_id' => $musteri->id,
            'personel_id' => $personel->id,
            'hizmet_tarihi' => now()->subDays(2),
        ]);

        $emailHizmet = MusteriHizmet::factory()->email()->create([
            'musteri_id' => $musteri->id,
            'personel_id' => $personel->id,
            'hizmet_tarihi' => now()->subDay(),
        ]);

        // Hizmet geçmişini kontrol et
        $this->assertCount(3, $musteri->hizmetler);

        // Son hizmetler (tarih sırasına göre)
        $sonHizmetler = $musteri->sonHizmetler;
        $this->assertEquals($emailHizmet->id, $sonHizmetler->first()->id);
        $this->assertEquals($telefonHizmet->id, $sonHizmetler->last()->id);
    }

    /** @test */
    public function it_calculates_musteri_segmenti_with_multiple_factors()
    {
        $musteri = Musteri::factory()->create([
            'potansiyel_deger' => 3000000, // 30 puan
        ]);

        // Hizmet geçmişi ekle (15 hizmet = 20 puan)
        MusteriHizmet::factory()->count(15)->create([
            'musteri_id' => $musteri->id,
        ]);

        // Mülk ilgisi ekle (8 ilişki = 20 puan)
        MusteriMulkIliskisi::factory()->count(8)->create([
            'musteri_id' => $musteri->id,
        ]);

        // Toplam: 30 + 20 + 20 = 70 puan (Premium segment)
        $this->assertEquals('Premium', $musteri->musteri_segmenti);

        // VIP segment için test
        $vipMusteri = Musteri::factory()->create([
            'potansiyel_deger' => 6000000, // 40 puan
        ]);

        MusteriHizmet::factory()->count(25)->create([
            'musteri_id' => $vipMusteri->id,
        ]); // 30 puan

        MusteriMulkIliskisi::factory()->count(12)->create([
            'musteri_id' => $vipMusteri->id,
        ]); // 30 puan

        // Toplam: 40 + 30 + 30 = 100 puan (VIP segment)
        $this->assertEquals('VIP', $vipMusteri->musteri_segmenti);
    }

    /** @test */
    public function it_calculates_musteri_durumu_based_on_activity()
    {
        $musteri = Musteri::factory()->create(['aktif_mi' => true]);

        // Pasif müşteri
        $pasifMusteri = Musteri::factory()->create(['aktif_mi' => false]);
        $this->assertEquals('Pasif', $pasifMusteri->musteri_durumu);

        // Yeni müşteri (hiç hizmet yok)
        $this->assertEquals('Yeni', $musteri->musteri_durumu);

        // Aktif müşteri (son 7 gün içinde hizmet)
        MusteriHizmet::factory()->create([
            'musteri_id' => $musteri->id,
            'hizmet_tarihi' => now()->subDays(3),
        ]);
        $this->assertEquals('Aktif', $musteri->fresh()->musteri_durumu);

        // Orta aktif müşteri (son 30 gün içinde hizmet)
        $ortaAktifMusteri = Musteri::factory()->create();
        MusteriHizmet::factory()->create([
            'musteri_id' => $ortaAktifMusteri->id,
            'hizmet_tarihi' => now()->subDays(15),
        ]);
        $this->assertEquals('Orta Aktif', $ortaAktifMusteri->musteri_durumu);

        // Durgun müşteri (90+ gün önce hizmet)
        $durgunMusteri = Musteri::factory()->create();
        MusteriHizmet::factory()->create([
            'musteri_id' => $durgunMusteri->id,
            'hizmet_tarihi' => now()->subDays(100),
        ]);
        $this->assertEquals('Durgun', $durgunMusteri->musteri_durumu);
    }

    /** @test */
    public function it_handles_referans_musteri_chain()
    {
        $anaMusteri = Musteri::factory()->create();
        $referansMusteri1 = Musteri::factory()->create([
            'referans_musteri_id' => $anaMusteri->id,
        ]);
        $referansMusteri2 = Musteri::factory()->create([
            'referans_musteri_id' => $anaMusteri->id,
        ]);

        // Ana müşterinin referans aldığı müşteriler
        $this->assertCount(2, $anaMusteri->referansAlanMusteriler);
        $this->assertTrue($anaMusteri->referansAlanMusteriler->contains($referansMusteri1));
        $this->assertTrue($anaMusteri->referansAlanMusteriler->contains($referansMusteri2));

        // Referans müşterilerinin ana müşteri ilişkisi
        $this->assertEquals($anaMusteri->id, $referansMusteri1->referansMusteri->id);
        $this->assertEquals($anaMusteri->id, $referansMusteri2->referansMusteri->id);
    }

    /** @test */
    public function it_handles_polymorphic_relationships()
    {
        $musteri = Musteri::factory()->create();

        // Adres ilişkisi
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $musteri->adresler());

        // Profil resmi ilişkisi
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphOne::class, $musteri->profilResmi());

        // Döküman ilişkisi
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $musteri->dokumanlar());

        // Not ilişkisi
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $musteri->notlar());

        // Hatırlatma ilişkisi
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $musteri->hatirlatmalar());
    }

    /** @test */
    public function it_has_working_scopes_for_filtering()
    {
        // Farklı müşteri tipleri oluştur
        $bireyselMusteri = Musteri::factory()->create(['tip' => MusteriTipi::BIREYSEL]);
        $kurumsalMusteri = Musteri::factory()->create(['tip' => MusteriTipi::KURUMSAL]);

        // Tip scope'ları
        $bireyselSonuc = Musteri::bireysel()->get();
        $this->assertTrue($bireyselSonuc->contains($bireyselMusteri));
        $this->assertFalse($bireyselSonuc->contains($kurumsalMusteri));

        $kurumsalSonuc = Musteri::kurumsal()->get();
        $this->assertTrue($kurumsalSonuc->contains($kurumsalMusteri));
        $this->assertFalse($kurumsalSonuc->contains($bireyselMusteri));

        // Aktif scope
        $aktifMusteri = Musteri::factory()->create(['aktif_mi' => true]);
        $pasifMusteri = Musteri::factory()->create(['aktif_mi' => false]);

        $aktifSonuc = Musteri::aktif()->get();
        $this->assertTrue($aktifSonuc->contains($aktifMusteri));
        $this->assertFalse($aktifSonuc->contains($pasifMusteri));

        // Potansiyel değer scope
        $yuksekDegerMusteri = Musteri::factory()->create(['potansiyel_deger' => 2000000]);
        $dusukDegerMusteri = Musteri::factory()->create(['potansiyel_deger' => 500000]);

        $yuksekDegerSonuc = Musteri::byPotansiyelDeger(1000000, 3000000)->get();
        $this->assertTrue($yuksekDegerSonuc->contains($yuksekDegerMusteri));
        $this->assertFalse($yuksekDegerSonuc->contains($dusukDegerMusteri));
    }

    /** @test */
    public function it_calculates_kategori_etiketleri_correctly()
    {
        MusteriKategori::createFromEnum();
        
        $musteri = Musteri::factory()->create();
        $musteri->addKategori(MusteriKategorisi::ALICI);
        $musteri->addKategori(MusteriKategorisi::VIP);

        $etiketler = $musteri->kategori_etiketleri;

        $this->assertIsArray($etiketler);
        $this->assertCount(2, $etiketler);

        foreach ($etiketler as $etiket) {
            $this->assertArrayHasKey('value', $etiket);
            $this->assertArrayHasKey('label', $etiket);
            $this->assertArrayHasKey('color', $etiket);
        }
    }

    /** @test */
    public function it_formats_display_name_for_different_types()
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz'
        ]);

        // Bireysel müşteri
        $bireyselMusteri = Musteri::factory()->create([
            'kisi_id' => $kisi->id,
            'tip' => MusteriTipi::BIREYSEL,
        ]);

        $this->assertEquals('Ahmet Yılmaz', $bireyselMusteri->display_name);

        // Kurumsal müşteri (firma bilgisi olmadan)
        $kurumsalMusteri = Musteri::factory()->create([
            'kisi_id' => $kisi->id,
            'tip' => MusteriTipi::KURUMSAL,
        ]);

        $this->assertEquals('Ahmet Yılmaz', $kurumsalMusteri->display_name);
    }

    /** @test */
    public function it_calculates_musteri_yasi_correctly()
    {
        $kayitTarihi = now()->subDays(150);
        $musteri = Musteri::factory()->create([
            'kayit_tarihi' => $kayitTarihi,
        ]);

        $this->assertEquals(150, $musteri->musteri_yasi);

        // Kayıt tarihi olmayan müşteri
        $yeniMusteri = Musteri::factory()->create([
            'kayit_tarihi' => null,
        ]);

        $this->assertNull($yeniMusteri->musteri_yasi);
    }

    /** @test */
    public function it_handles_segment_scope()
    {
        $vipMusteri = Musteri::factory()->create(['potansiyel_deger' => 6000000]);
        $premiumMusteri = Musteri::factory()->create(['potansiyel_deger' => 3000000]);
        $standartMusteri = Musteri::factory()->create(['potansiyel_deger' => 750000]);
        $yeniMusteri = Musteri::factory()->create(['potansiyel_deger' => 200000]);

        // VIP segment
        $vipSonuc = Musteri::bySegment('VIP')->get();
        $this->assertTrue($vipSonuc->contains($vipMusteri));
        $this->assertFalse($vipSonuc->contains($premiumMusteri));

        // Premium segment
        $premiumSonuc = Musteri::bySegment('Premium')->get();
        $this->assertTrue($premiumSonuc->contains($premiumMusteri));
        $this->assertFalse($premiumSonuc->contains($vipMusteri));

        // Standart segment
        $standartSonuc = Musteri::bySegment('Standart')->get();
        $this->assertTrue($standartSonuc->contains($standartMusteri));

        // Yeni segment
        $yeniSonuc = Musteri::bySegment('Yeni')->get();
        $this->assertTrue($yeniSonuc->contains($yeniMusteri));
    }

    /** @test */
    public function it_handles_son_aktivite_scope()
    {
        $aktifMusteri = Musteri::factory()->create();
        $inaktifMusteri = Musteri::factory()->create();

        // Son 30 gün içinde hizmet alan müşteri
        MusteriHizmet::factory()->create([
            'musteri_id' => $aktifMusteri->id,
            'hizmet_tarihi' => now()->subDays(15),
        ]);

        // 45 gün önce hizmet alan müşteri
        MusteriHizmet::factory()->create([
            'musteri_id' => $inaktifMusteri->id,
            'hizmet_tarihi' => now()->subDays(45),
        ]);

        $sonAktiviteSonuc = Musteri::bySonAktivite(30)->get();

        $this->assertTrue($sonAktiviteSonuc->contains($aktifMusteri));
        $this->assertFalse($sonAktiviteSonuc->contains($inaktifMusteri));
    }

    /** @test */
    public function it_validates_update_rules_correctly()
    {
        $musteri = Musteri::factory()->create(['musteri_no' => 'MST-001']);

        $updateRules = $musteri->getUpdateValidationRules();

        // Müşteri numarası unique kuralı kendi ID'sini hariç tutmalı
        $this->assertStringContainsString('unique:musteri,musteri_no,' . $musteri->id, $updateRules['musteri_no']);
    }
}