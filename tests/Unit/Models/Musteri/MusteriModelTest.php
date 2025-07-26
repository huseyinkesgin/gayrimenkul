<?php

namespace Tests\Unit\Models\Musteri;

use Tests\TestCase;
use App\Models\Musteri\Musteri;
use App\Models\Musteri\MusteriKategori;
use App\Models\Musteri\Firma;
use App\Models\Kisi\Kisi;
use App\Enums\MusteriTipi;
use App\Enums\MusteriKategorisi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MusteriModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_musteri_with_kisi()
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz'
        ]);

        $musteri = Musteri::create([
            'kisi_id' => $kisi->id,
            'tip' => MusteriTipi::BIREYSEL,
            'musteri_no' => 'MST-2024-001',
            'kayit_tarihi' => now(),
            'aktif_mi' => true,
        ]);

        $this->assertInstanceOf(Musteri::class, $musteri);
        $this->assertEquals('Ahmet Yılmaz', $musteri->full_name);
        $this->assertEquals(MusteriTipi::BIREYSEL, $musteri->tip);
        $this->assertTrue($musteri->aktif_mi);
    }

    /** @test */
    public function it_has_correct_relationships()
    {
        $kisi = Kisi::factory()->create();
        $musteri = Musteri::factory()->create(['kisi_id' => $kisi->id]);

        // Kişi ilişkisi
        $this->assertInstanceOf(Kisi::class, $musteri->kisi);
        $this->assertEquals($kisi->id, $musteri->kisi->id);

        // Kategoriler ilişkisi
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $musteri->kategoriler());

        // Firmalar ilişkisi
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $musteri->firmalar());

        // Mülk ilişkileri
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $musteri->mulkIliskileri());
    }

    /** @test */
    public function it_can_add_and_remove_kategoriler()
    {
        // Kategori oluştur
        MusteriKategori::createFromEnum();
        
        $kisi = Kisi::factory()->create();
        $musteri = Musteri::factory()->create(['kisi_id' => $kisi->id]);

        // Kategori ekle
        $musteri->addKategori(MusteriKategorisi::ALICI, 'Test notu');

        $this->assertTrue($musteri->hasKategori(MusteriKategorisi::ALICI));

        // Kategori kaldır
        $musteri->removeKategori(MusteriKategorisi::ALICI);

        $this->assertFalse($musteri->fresh()->hasKategori(MusteriKategorisi::ALICI));
    }

    /** @test */
    public function it_calculates_musteri_segmenti_correctly()
    {
        $kisi = Kisi::factory()->create();
        
        // VIP müşteri
        $vipMusteri = Musteri::factory()->create([
            'kisi_id' => $kisi->id,
            'potansiyel_deger' => 6000000
        ]);

        $this->assertEquals('VIP', $vipMusteri->musteri_segmenti);

        // Standart müşteri
        $standartMusteri = Musteri::factory()->create([
            'kisi_id' => $kisi->id,
            'potansiyel_deger' => 750000
        ]);

        $this->assertEquals('Standart', $standartMusteri->musteri_segmenti);
    }

    /** @test */
    public function it_formats_potansiyel_deger_correctly()
    {
        $kisi = Kisi::factory()->create();
        
        $musteri = Musteri::factory()->create([
            'kisi_id' => $kisi->id,
            'potansiyel_deger' => 1500000,
            'para_birimi' => 'TRY'
        ]);

        $this->assertEquals('1.500.000 ₺', $musteri->formatted_potansiyel_deger);

        // USD ile test
        $musteri->update(['para_birimi' => 'USD']);
        $this->assertEquals('1.500.000 $', $musteri->formatted_potansiyel_deger);
    }

    /** @test */
    public function it_has_working_scopes()
    {
        $kisi1 = Kisi::factory()->create();
        $kisi2 = Kisi::factory()->create();

        $bireyselMusteri = Musteri::factory()->create([
            'kisi_id' => $kisi1->id,
            'tip' => MusteriTipi::BIREYSEL
        ]);

        $kurumsalMusteri = Musteri::factory()->create([
            'kisi_id' => $kisi2->id,
            'tip' => MusteriTipi::KURUMSAL
        ]);

        // Bireysel scope
        $bireyselSonuc = Musteri::bireysel()->get();
        $this->assertCount(1, $bireyselSonuc);
        $this->assertTrue($bireyselSonuc->contains($bireyselMusteri));

        // Kurumsal scope
        $kurumsalSonuc = Musteri::kurumsal()->get();
        $this->assertCount(1, $kurumsalSonuc);
        $this->assertTrue($kurumsalSonuc->contains($kurumsalMusteri));
    }

    /** @test */
    public function it_handles_referans_musteri_relationship()
    {
        $kisi1 = Kisi::factory()->create();
        $kisi2 = Kisi::factory()->create();

        $referansMusteri = Musteri::factory()->create(['kisi_id' => $kisi1->id]);
        $yeniMusteri = Musteri::factory()->create([
            'kisi_id' => $kisi2->id,
            'referans_musteri_id' => $referansMusteri->id
        ]);

        // Referans müşteri ilişkisi
        $this->assertEquals($referansMusteri->id, $yeniMusteri->referansMusteri->id);

        // Referans alan müşteriler
        $this->assertTrue($referansMusteri->referansAlanMusteriler->contains($yeniMusteri));
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $rules = Musteri::getValidationRules();

        $this->assertArrayHasKey('kisi_id', $rules);
        $this->assertArrayHasKey('tip', $rules);
        $this->assertContains('required', explode('|', $rules['kisi_id']));
        $this->assertContains('required', explode('|', $rules['tip']));
    }

    /** @test */
    public function it_calculates_display_name_correctly()
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Mehmet',
            'soyad' => 'Demir'
        ]);

        // Bireysel müşteri
        $bireyselMusteri = Musteri::factory()->create([
            'kisi_id' => $kisi->id,
            'tip' => MusteriTipi::BIREYSEL
        ]);

        $this->assertEquals('Mehmet Demir', $bireyselMusteri->display_name);

        // Kurumsal müşteri
        $kurumsalMusteri = Musteri::factory()->create([
            'kisi_id' => $kisi->id,
            'tip' => MusteriTipi::KURUMSAL
        ]);

        $this->assertEquals('Mehmet Demir', $kurumsalMusteri->display_name);
    }
}