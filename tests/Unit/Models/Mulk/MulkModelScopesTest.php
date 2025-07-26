<?php

namespace Tests\Unit\Models\Mulk;

use Tests\TestCase;
use App\Models\Mulk\Arsa\TicariArsa;
use App\Models\Mulk\Isyeri\Fabrika;
use App\Models\Mulk\Konut\Daire;
use App\Models\Mulk\TuristikTesis\Hotel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MulkModelScopesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_filters_aktif_mulkler()
    {
        // Aktif mülkler
        $aktifArsa = TicariArsa::factory()->create([
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);
        
        $aktifFabrika = Fabrika::factory()->create([
            'durum' => 'aktif',
            'aktif_mi' => true,
        ]);
        
        // Pasif mülkler
        $pasifDaire = Daire::factory()->create([
            'durum' => 'pasif',
            'aktif_mi' => true,
        ]);
        
        $deaktifHotel = Hotel::factory()->create([
            'durum' => 'aktif',
            'aktif_mi' => false,
        ]);
        
        $satilmisMulk = TicariArsa::factory()->create([
            'durum' => 'satildi',
            'aktif_mi' => true,
        ]);
        
        // Aktif mülkleri filtrele
        $aktifArsalar = TicariArsa::aktifMulkler()->get();
        $aktifFabrikalar = Fabrika::aktifMulkler()->get();
        $aktifDaireler = Daire::aktifMulkler()->get();
        $aktifHoteller = Hotel::aktifMulkler()->get();
        
        $this->assertCount(1, $aktifArsalar);
        $this->assertCount(1, $aktifFabrikalar);
        $this->assertCount(0, $aktifDaireler);
        $this->assertCount(0, $aktifHoteller);
        
        $this->assertTrue($aktifArsalar->contains($aktifArsa));
        $this->assertTrue($aktifFabrikalar->contains($aktifFabrika));
    }

    /** @test */
    public function it_filters_by_price_range()
    {
        $ucuzArsa = TicariArsa::factory()->create(['fiyat' => 500000]);
        $ortaArsa = TicariArsa::factory()->create(['fiyat' => 1500000]);
        $pahaliArsa = TicariArsa::factory()->create(['fiyat' => 3000000]);
        
        // Minimum fiyat filtresi
        $minFiyatSonuclari = TicariArsa::fiyatAraliginda(1000000)->get();
        $this->assertCount(2, $minFiyatSonuclari);
        $this->assertTrue($minFiyatSonuclari->contains($ortaArsa));
        $this->assertTrue($minFiyatSonuclari->contains($pahaliArsa));
        $this->assertFalse($minFiyatSonuclari->contains($ucuzArsa));
        
        // Maksimum fiyat filtresi
        $maxFiyatSonuclari = TicariArsa::fiyatAraliginda(null, 2000000)->get();
        $this->assertCount(2, $maxFiyatSonuclari);
        $this->assertTrue($maxFiyatSonuclari->contains($ucuzArsa));
        $this->assertTrue($maxFiyatSonuclari->contains($ortaArsa));
        $this->assertFalse($maxFiyatSonuclari->contains($pahaliArsa));
        
        // Aralık filtresi
        $aralikSonuclari = TicariArsa::fiyatAraliginda(1000000, 2000000)->get();
        $this->assertCount(1, $aralikSonuclari);
        $this->assertTrue($aralikSonuclari->contains($ortaArsa));
    }

    /** @test */
    public function it_filters_by_area_range()
    {
        $kucukFabrika = Fabrika::factory()->create(['metrekare' => 800]);
        $ortaFabrika = Fabrika::factory()->create(['metrekare' => 1500]);
        $buyukFabrika = Fabrika::factory()->create(['metrekare' => 3000]);
        
        // Minimum metrekare filtresi
        $minMetrekareSonuclari = Fabrika::metrekareAraliginda(1200)->get();
        $this->assertCount(2, $minMetrekareSonuclari);
        $this->assertTrue($minMetrekareSonuclari->contains($ortaFabrika));
        $this->assertTrue($minMetrekareSonuclari->contains($buyukFabrika));
        $this->assertFalse($minMetrekareSonuclari->contains($kucukFabrika));
        
        // Maksimum metrekare filtresi
        $maxMetrekareSonuclari = Fabrika::metrekareAraliginda(null, 2000)->get();
        $this->assertCount(2, $maxMetrekareSonuclari);
        $this->assertTrue($maxMetrekareSonuclari->contains($kucukFabrika));
        $this->assertTrue($maxMetrekareSonuclari->contains($ortaFabrika));
        $this->assertFalse($maxMetrekareSonuclari->contains($buyukFabrika));
        
        // Aralık filtresi
        $aralikSonuclari = Fabrika::metrekareAraliginda(1000, 2000)->get();
        $this->assertCount(1, $aralikSonuclari);
        $this->assertTrue($aralikSonuclari->contains($ortaFabrika));
    }

    /** @test */
    public function it_filters_by_mulk_type()
    {
        $ticariArsa = TicariArsa::factory()->create();
        $fabrika = Fabrika::factory()->create();
        $daire = Daire::factory()->create();
        
        $ticariArsaSonuclari = TicariArsa::byType('ticari_arsa')->get();
        $fabrikaSonuclari = Fabrika::byType('fabrika')->get();
        $daireSonuclari = Daire::byType('daire')->get();
        
        $this->assertCount(1, $ticariArsaSonuclari);
        $this->assertCount(1, $fabrikaSonuclari);
        $this->assertCount(1, $daireSonuclari);
        
        $this->assertTrue($ticariArsaSonuclari->contains($ticariArsa));
        $this->assertTrue($fabrikaSonuclari->contains($fabrika));
        $this->assertTrue($daireSonuclari->contains($daire));
        
        // Yanlış tip ile filtreleme
        $yanlisTipSonuclari = TicariArsa::byType('fabrika')->get();
        $this->assertCount(0, $yanlisTipSonuclari);
    }

    /** @test */
    public function it_filters_yayinlanan_mulkler()
    {
        // Yayınlanmış mülkler
        $yayinlanmisMulk1 = Daire::factory()->create([
            'yayinlanma_tarihi' => now()->subDays(5),
        ]);
        
        $yayinlanmisMulk2 = Daire::factory()->create([
            'yayinlanma_tarihi' => now()->subHours(2),
        ]);
        
        // Gelecek tarihli yayın
        $gelecekYayinMulk = Daire::factory()->create([
            'yayinlanma_tarihi' => now()->addDays(2),
        ]);
        
        // Yayınlanmamış mülk
        $yayinlanmamisMulk = Daire::factory()->create([
            'yayinlanma_tarihi' => null,
        ]);
        
        $yayinlananSonuclari = Daire::yayinlanan()->get();
        
        $this->assertCount(2, $yayinlananSonuclari);
        $this->assertTrue($yayinlananSonuclari->contains($yayinlanmisMulk1));
        $this->assertTrue($yayinlananSonuclari->contains($yayinlanmisMulk2));
        $this->assertFalse($yayinlananSonuclari->contains($gelecekYayinMulk));
        $this->assertFalse($yayinlananSonuclari->contains($yayinlanmamisMulk));
    }

    /** @test */
    public function it_combines_multiple_scopes()
    {
        // Test verileri oluştur
        $aktifYayinlanmisMulk = Hotel::factory()->create([
            'durum' => 'aktif',
            'aktif_mi' => true,
            'yayinlanma_tarihi' => now()->subDays(1),
            'fiyat' => 15000000,
            'metrekare' => 2500,
        ]);
        
        $pasifMulk = Hotel::factory()->create([
            'durum' => 'pasif',
            'aktif_mi' => true,
            'yayinlanma_tarihi' => now()->subDays(1),
            'fiyat' => 15000000,
            'metrekare' => 2500,
        ]);
        
        $ucuzMulk = Hotel::factory()->create([
            'durum' => 'aktif',
            'aktif_mi' => true,
            'yayinlanma_tarihi' => now()->subDays(1),
            'fiyat' => 5000000,
            'metrekare' => 2500,
        ]);
        
        $kucukMulk = Hotel::factory()->create([
            'durum' => 'aktif',
            'aktif_mi' => true,
            'yayinlanma_tarihi' => now()->subDays(1),
            'fiyat' => 15000000,
            'metrekare' => 1000,
        ]);
        
        // Birden fazla scope'u birleştir
        $filtreliSonuclar = Hotel::aktifMulkler()
            ->yayinlanan()
            ->fiyatAraliginda(10000000, 20000000)
            ->metrekareAraliginda(2000, 5000)
            ->get();
        
        $this->assertCount(1, $filtreliSonuclar);
        $this->assertTrue($filtreliSonuclar->contains($aktifYayinlanmisMulk));
        $this->assertFalse($filtreliSonuclar->contains($pasifMulk));
        $this->assertFalse($filtreliSonuclar->contains($ucuzMulk));
        $this->assertFalse($filtreliSonuclar->contains($kucukMulk));
    }

    /** @test */
    public function it_handles_null_values_in_price_range()
    {
        $mulk1 = TicariArsa::factory()->create(['fiyat' => 1000000]);
        $mulk2 = TicariArsa::factory()->create(['fiyat' => null]);
        
        // Null fiyatlı mülkler minimum fiyat filtresinde görünmemeli
        $minFiyatSonuclari = TicariArsa::fiyatAraliginda(500000)->get();
        $this->assertCount(1, $minFiyatSonuclari);
        $this->assertTrue($minFiyatSonuclari->contains($mulk1));
        $this->assertFalse($minFiyatSonuclari->contains($mulk2));
    }

    /** @test */
    public function it_handles_null_values_in_area_range()
    {
        $mulk1 = Fabrika::factory()->create(['metrekare' => 1500]);
        $mulk2 = Fabrika::factory()->create(['metrekare' => null]);
        
        // Null metrekareli mülkler minimum metrekare filtresinde görünmemeli
        $minMetrekareSonuclari = Fabrika::metrekareAraliginda(1000)->get();
        $this->assertCount(1, $minMetrekareSonuclari);
        $this->assertTrue($minMetrekareSonuclari->contains($mulk1));
        $this->assertFalse($minMetrekareSonuclari->contains($mulk2));
    }

    /** @test */
    public function it_returns_empty_collection_for_impossible_ranges()
    {
        TicariArsa::factory()->count(5)->create([
            'fiyat' => 1500000,
            'metrekare' => 800,
        ]);
        
        // İmkansız fiyat aralığı
        $imkansizFiyatSonuclari = TicariArsa::fiyatAraliginda(2000000, 1000000)->get();
        $this->assertCount(0, $imkansizFiyatSonuclari);
        
        // İmkansız metrekare aralığı
        $imkansizMetrekareSonuclari = TicariArsa::metrekareAraliginda(1000, 500)->get();
        $this->assertCount(0, $imkansizMetrekareSonuclari);
    }

    /** @test */
    public function it_works_with_different_property_types()
    {
        // Farklı mülk tiplerinde scope'ların çalıştığını test et
        $arsa = TicariArsa::factory()->aktif()->create(['fiyat' => 1500000]);
        $fabrika = Fabrika::factory()->aktif()->create(['fiyat' => 8000000]);
        $daire = Daire::factory()->aktif()->create(['fiyat' => 800000]);
        $hotel = Hotel::factory()->aktif()->create(['fiyat' => 25000000]);
        
        // Her tip için aktif mülkler scope'u çalışmalı
        $this->assertCount(1, TicariArsa::aktifMulkler()->get());
        $this->assertCount(1, Fabrika::aktifMulkler()->get());
        $this->assertCount(1, Daire::aktifMulkler()->get());
        $this->assertCount(1, Hotel::aktifMulkler()->get());
        
        // Her tip için fiyat aralığı scope'u çalışmalı
        $this->assertCount(1, TicariArsa::fiyatAraliginda(1000000, 2000000)->get());
        $this->assertCount(1, Fabrika::fiyatAraliginda(5000000, 10000000)->get());
        $this->assertCount(1, Daire::fiyatAraliginda(500000, 1000000)->get());
        $this->assertCount(1, Hotel::fiyatAraliginda(20000000, 30000000)->get());
    }
}