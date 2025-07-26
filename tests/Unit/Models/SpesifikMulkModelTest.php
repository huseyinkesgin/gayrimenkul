<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Mulk\Arsa\TicariArsa;
use App\Models\Mulk\Arsa\SanayiArsasi;
use App\Models\Mulk\Arsa\KonutArsasi;
use App\Models\Mulk\Isyeri\Fabrika;
use App\Models\Mulk\Isyeri\Depo;
use App\Models\Mulk\Isyeri\Ofis;
use App\Models\Mulk\Konut\Daire;
use App\Models\Mulk\Konut\Villa;
use App\Models\Mulk\TuristikTesis\ButikOtel;
use App\Models\Mulk\TuristikTesis\Hotel;
use App\Enums\MulkKategorisi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SpesifikMulkModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function arsa_modelleri_dogru_tip_ve_kategori_dondurur()
    {
        $ticariArsa = new TicariArsa();
        $this->assertEquals('ticari_arsa', $ticariArsa->getMulkType());
        $this->assertEquals(MulkKategorisi::ARSA, $ticariArsa->getMulkKategorisi());

        $sanayiArsasi = new SanayiArsasi();
        $this->assertEquals('sanayi_arsasi', $sanayiArsasi->getMulkType());
        $this->assertEquals(MulkKategorisi::ARSA, $sanayiArsasi->getMulkKategorisi());

        $konutArsasi = new KonutArsasi();
        $this->assertEquals('konut_arsasi', $konutArsasi->getMulkType());
        $this->assertEquals(MulkKategorisi::ARSA, $konutArsasi->getMulkKategorisi());
    }

    /** @test */
    public function isyeri_modelleri_dogru_tip_ve_kategori_dondurur()
    {
        $fabrika = new Fabrika();
        $this->assertEquals('fabrika', $fabrika->getMulkType());
        $this->assertEquals(MulkKategorisi::ISYERI, $fabrika->getMulkKategorisi());

        $depo = new Depo();
        $this->assertEquals('depo', $depo->getMulkType());
        $this->assertEquals(MulkKategorisi::ISYERI, $depo->getMulkKategorisi());

        $ofis = new Ofis();
        $this->assertEquals('ofis', $ofis->getMulkType());
        $this->assertEquals(MulkKategorisi::ISYERI, $ofis->getMulkKategorisi());
    }

    /** @test */
    public function konut_modelleri_dogru_tip_ve_kategori_dondurur()
    {
        $daire = new Daire();
        $this->assertEquals('daire', $daire->getMulkType());
        $this->assertEquals(MulkKategorisi::KONUT, $daire->getMulkKategorisi());

        $villa = new Villa();
        $this->assertEquals('villa', $villa->getMulkType());
        $this->assertEquals(MulkKategorisi::KONUT, $villa->getMulkKategorisi());
    }

    /** @test */
    public function turistik_tesis_modelleri_dogru_tip_ve_kategori_dondurur()
    {
        $butikOtel = new ButikOtel();
        $this->assertEquals('butik_otel', $butikOtel->getMulkType());
        $this->assertEquals(MulkKategorisi::TURISTIK_TESIS, $butikOtel->getMulkKategorisi());

        $hotel = new Hotel();
        $this->assertEquals('hotel', $hotel->getMulkType());
        $this->assertEquals(MulkKategorisi::TURISTIK_TESIS, $hotel->getMulkKategorisi());
    }

    /** @test */
    public function arsa_modelleri_gecerli_ozellikler_dondurur()
    {
        $ticariArsa = new TicariArsa();
        $ozellikler = $ticariArsa->getValidProperties();
        
        $this->assertIsArray($ozellikler);
        $this->assertContains('imar_durumu', $ozellikler);
        $this->assertContains('ticari_potansiyel', $ozellikler);
        $this->assertContains('ana_cadde_cephesi', $ozellikler);
    }

    /** @test */
    public function fabrika_modeli_ozel_ozellikler_dondurur()
    {
        $fabrika = new Fabrika();
        $ozellikler = $fabrika->getValidProperties();
        
        $this->assertContains('uretim_alani', $ozellikler);
        $this->assertContains('vinc_kapasitesi', $ozellikler);
        $this->assertContains('atiksu_aritma_sistemi', $ozellikler);
    }

    /** @test */
    public function depo_modeli_ozel_ozellikler_dondurur()
    {
        $depo = new Depo();
        $ozellikler = $depo->getValidProperties();
        
        $this->assertContains('ellecleme_alani', $ozellikler);
        $this->assertContains('rampa_sayisi', $ozellikler);
        $this->assertContains('raf_sistemi_var_mi', $ozellikler);
    }

    /** @test */
    public function daire_modeli_oda_salon_bilgisi_dondurur()
    {
        $daire = new Daire();
        
        // Mock property values
        $daire->addProperty('oda_sayisi', 3, 'sayi');
        $daire->addProperty('salon_sayisi', 1, 'sayi');
        
        $this->assertEquals('3+1', $daire->oda_salon_bilgisi);
    }

    /** @test */
    public function villa_modeli_ozel_ozellikler_dondurur()
    {
        $villa = new Villa();
        $ozellikler = $villa->getValidProperties();
        
        $this->assertContains('bahce_alani', $ozellikler);
        $this->assertContains('havuz_alani', $ozellikler);
        $this->assertContains('garaj_kapasitesi', $ozellikler);
    }

    /** @test */
    public function butik_otel_modeli_ozel_ozellikler_dondurur()
    {
        $butikOtel = new ButikOtel();
        $ozellikler = $butikOtel->getValidProperties();
        
        $this->assertContains('tema_konsepti', $ozellikler);
        $this->assertContains('tasarim_stili', $ozellikler);
        $this->assertContains('kişiselleştirilmiş_hizmet', $ozellikler);
    }

    /** @test */
    public function tum_modeller_validation_kurallari_dondurur()
    {
        $modeller = [
            new TicariArsa(),
            new Fabrika(),
            new Daire(),
            new ButikOtel(),
        ];

        foreach ($modeller as $model) {
            $rules = $model->getValidationRules();
            
            $this->assertIsArray($rules);
            $this->assertArrayHasKey('baslik', $rules); // Base rule
            $this->assertNotEmpty($rules);
        }
    }

    /** @test */
    public function arsa_modeli_imar_durumu_etiketi_dondurur()
    {
        $arsa = new TicariArsa();
        $arsa->addProperty('imar_durumu', 'imarlı', 'metin');
        
        $this->assertEquals('İmarlı', $arsa->imar_durumu_label);
    }

    /** @test */
    public function arsa_modeli_ada_parsel_bilgisi_dondurur()
    {
        $arsa = new TicariArsa();
        $arsa->addProperty('ada_no', '123', 'metin');
        $arsa->addProperty('parsel_no', '45', 'metin');
        
        $this->assertEquals('Ada: 123, Parsel: 45', $arsa->ada_parsel);
    }

    /** @test */
    public function isyeri_modeli_toplam_alan_hesaplar()
    {
        $isyeri = new Fabrika();
        $isyeri->addProperty('kapali_alan', 1000, 'sayi');
        $isyeri->addProperty('acik_alan', 500, 'sayi');
        
        $this->assertEquals(1500, $isyeri->toplam_alan);
    }

    /** @test */
    public function turistik_tesis_hizmet_kalitesi_puani_hesaplar()
    {
        $otel = new ButikOtel();
        $otel->addProperty('resepsiyon_var_mi', true, 'boolean');
        $otel->addProperty('restoran_var_mi', true, 'boolean');
        $otel->addProperty('havuz_var_mi', true, 'boolean');
        
        $kalitePuani = $otel->hizmet_kalitesi_puani;
        
        $this->assertIsArray($kalitePuani);
        $this->assertArrayHasKey('puan', $kalitePuani);
        $this->assertArrayHasKey('seviye', $kalitePuani);
        $this->assertGreaterThan(0, $kalitePuani['puan']);
    }
}