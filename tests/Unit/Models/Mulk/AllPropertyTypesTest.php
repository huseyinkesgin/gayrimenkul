<?php

namespace Tests\Unit\Models\Mulk;

use Tests\TestCase;
use App\Models\Mulk\Arsa\Arsa;
use App\Models\Mulk\Arsa\TicariArsa;
use App\Models\Mulk\Arsa\SanayiArsasi;
use App\Models\Mulk\Arsa\KonutArsasi;
use App\Models\Mulk\Isyeri\Isyeri;
use App\Models\Mulk\Isyeri\Fabrika;
use App\Models\Mulk\Isyeri\Depo;
use App\Models\Mulk\Isyeri\Magaza;
use App\Models\Mulk\Isyeri\Ofis;
use App\Models\Mulk\Isyeri\Dukkan;
use App\Models\Mulk\Konut\Konut;
use App\Models\Mulk\Konut\Daire;
use App\Models\Mulk\Konut\Rezidans;
use App\Models\Mulk\Konut\Villa;
use App\Models\Mulk\Konut\Yali;
use App\Models\Mulk\Konut\Yazlik;
use App\Models\Mulk\TuristikTesis\TuristikTesis;
use App\Models\Mulk\TuristikTesis\ButikOtel;
use App\Models\Mulk\TuristikTesis\ApartOtel;
use App\Models\Mulk\TuristikTesis\Hotel;
use App\Models\Mulk\TuristikTesis\Motel;
use App\Models\Mulk\TuristikTesis\TatilKoyu;
use App\Enums\MulkKategorisi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AllPropertyTypesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_all_arsa_types_with_correct_properties()
    {
        $arsaTypes = [
            ['class' => TicariArsa::class, 'type' => 'ticari_arsa', 'category' => MulkKategorisi::ARSA],
            ['class' => SanayiArsasi::class, 'type' => 'sanayi_arsasi', 'category' => MulkKategorisi::ARSA],
            ['class' => KonutArsasi::class, 'type' => 'konut_arsasi', 'category' => MulkKategorisi::ARSA],
        ];

        foreach ($arsaTypes as $arsaType) {
            $arsa = $arsaType['class']::factory()->create();
            
            $this->assertInstanceOf($arsaType['class'], $arsa);
            $this->assertEquals($arsaType['type'], $arsa->getMulkType());
            $this->assertEquals($arsaType['category'], $arsa->getMulkKategorisi());
            $this->assertIsArray($arsa->getValidProperties());
            $this->assertIsArray($arsa->getValidationRules());
            
            // Arsa özelliklerini kontrol et
            $validProperties = $arsa->getValidProperties();
            $this->assertContains('imar_durumu', $validProperties);
            $this->assertContains('ada_no', $validProperties);
            $this->assertContains('parsel_no', $validProperties);
        }
    }

    /** @test */
    public function it_creates_all_isyeri_types_with_correct_properties()
    {
        $isyeriTypes = [
            ['class' => Fabrika::class, 'type' => 'fabrika'],
            ['class' => Depo::class, 'type' => 'depo'],
            ['class' => Magaza::class, 'type' => 'magaza'],
            ['class' => Ofis::class, 'type' => 'ofis'],
            ['class' => Dukkan::class, 'type' => 'dukkan'],
        ];

        foreach ($isyeriTypes as $isyeriType) {
            $isyeri = $isyeriType['class']::factory()->create();
            
            $this->assertInstanceOf($isyeriType['class'], $isyeri);
            $this->assertEquals($isyeriType['type'], $isyeri->getMulkType());
            $this->assertEquals(MulkKategorisi::ISYERI, $isyeri->getMulkKategorisi());
            $this->assertIsArray($isyeri->getValidProperties());
            $this->assertIsArray($isyeri->getValidationRules());
            
            // İşyeri özelliklerini kontrol et
            $validProperties = $isyeri->getValidProperties();
            $this->assertContains('kapali_alan', $validProperties);
            $this->assertContains('acik_alan', $validProperties);
            $this->assertContains('yukseklik', $validProperties);
        }
    }

    /** @test */
    public function it_creates_all_konut_types_with_correct_properties()
    {
        $konutTypes = [
            ['class' => Daire::class, 'type' => 'daire'],
            ['class' => Rezidans::class, 'type' => 'rezidans'],
            ['class' => Villa::class, 'type' => 'villa'],
            ['class' => Yali::class, 'type' => 'yali'],
            ['class' => Yazlik::class, 'type' => 'yazlik'],
        ];

        foreach ($konutTypes as $konutType) {
            $konut = $konutType['class']::factory()->create();
            
            $this->assertInstanceOf($konutType['class'], $konut);
            $this->assertEquals($konutType['type'], $konut->getMulkType());
            $this->assertEquals(MulkKategorisi::KONUT, $konut->getMulkKategorisi());
            $this->assertIsArray($konut->getValidProperties());
            $this->assertIsArray($konut->getValidationRules());
            
            // Konut özelliklerini kontrol et
            $validProperties = $konut->getValidProperties();
            $this->assertContains('oda_sayisi', $validProperties);
            $this->assertContains('salon_sayisi', $validProperties);
            $this->assertContains('banyo_sayisi', $validProperties);
        }
    }

    /** @test */
    public function it_creates_all_turistik_tesis_types_with_correct_properties()
    {
        $turistikTesisTypes = [
            ['class' => ButikOtel::class, 'type' => 'butik_otel'],
            ['class' => ApartOtel::class, 'type' => 'apart_otel'],
            ['class' => Hotel::class, 'type' => 'hotel'],
            ['class' => Motel::class, 'type' => 'motel'],
            ['class' => TatilKoyu::class, 'type' => 'tatil_koyu'],
        ];

        foreach ($turistikTesisTypes as $tesisType) {
            $tesis = $tesisType['class']::factory()->create();
            
            $this->assertInstanceOf($tesisType['class'], $tesis);
            $this->assertEquals($tesisType['type'], $tesis->getMulkType());
            $this->assertEquals(MulkKategorisi::TURISTIK_TESIS, $tesis->getMulkKategorisi());
            $this->assertIsArray($tesis->getValidProperties());
            $this->assertIsArray($tesis->getValidationRules());
            
            // Turistik tesis özelliklerini kontrol et
            $validProperties = $tesis->getValidProperties();
            $this->assertContains('oda_sayisi', $validProperties);
            $this->assertContains('yatak_kapasitesi', $validProperties);
            $this->assertContains('resepsiyon_var_mi', $validProperties);
        }
    }

    /** @test */
    public function it_validates_specific_properties_for_each_type()
    {
        // Fabrika özel özellikleri
        $fabrika = Fabrika::factory()->create();
        $fabrikaProperties = $fabrika->getValidProperties();
        $this->assertContains('uretim_alani', $fabrikaProperties);
        $this->assertContains('vinc_kapasitesi', $fabrikaProperties);
        $this->assertContains('atiksu_aritma_sistemi', $fabrikaProperties);

        // Depo özel özellikleri
        $depo = Depo::factory()->create();
        $depoProperties = $depo->getValidProperties();
        $this->assertContains('ellecleme_alani', $depoProperties);
        $this->assertContains('rampa_sayisi', $depoProperties);
        $this->assertContains('raf_sistemi_var_mi', $depoProperties);

        // Villa özel özellikleri
        $villa = Villa::factory()->create();
        $villaProperties = $villa->getValidProperties();
        $this->assertContains('bahce_alani', $villaProperties);
        $this->assertContains('havuz_alani', $villaProperties);
        $this->assertContains('garaj_kapasitesi', $villaProperties);

        // Butik Otel özel özellikleri
        $butikOtel = ButikOtel::factory()->create();
        $butikOtelProperties = $butikOtel->getValidProperties();
        $this->assertContains('tema_konsepti', $butikOtelProperties);
        $this->assertContains('tasarim_stili', $butikOtelProperties);
        $this->assertContains('kişiselleştirilmiş_hizmet', $butikOtelProperties);
    }

    /** @test */
    public function it_has_correct_inheritance_hierarchy()
    {
        // Arsa hiyerarşisi
        $ticariArsa = new TicariArsa();
        $this->assertInstanceOf(Arsa::class, $ticariArsa);
        $this->assertInstanceOf(\App\Models\Mulk\BaseMulk::class, $ticariArsa);

        // İşyeri hiyerarşisi
        $fabrika = new Fabrika();
        $this->assertInstanceOf(Isyeri::class, $fabrika);
        $this->assertInstanceOf(\App\Models\Mulk\BaseMulk::class, $fabrika);

        // Konut hiyerarşisi
        $daire = new Daire();
        $this->assertInstanceOf(Konut::class, $daire);
        $this->assertInstanceOf(\App\Models\Mulk\BaseMulk::class, $daire);

        // Turistik tesis hiyerarşisi
        $hotel = new Hotel();
        $this->assertInstanceOf(TuristikTesis::class, $hotel);
        $this->assertInstanceOf(\App\Models\Mulk\BaseMulk::class, $hotel);
    }

    /** @test */
    public function it_can_add_and_retrieve_properties_for_all_types()
    {
        $propertyTypes = [
            TicariArsa::factory()->create(),
            Fabrika::factory()->create(),
            Daire::factory()->create(),
            Hotel::factory()->create(),
        ];

        foreach ($propertyTypes as $property) {
            // Her tip için özellik ekleme
            $validProperties = $property->getValidProperties();
            if (!empty($validProperties)) {
                $firstProperty = $validProperties[0];
                $property->addProperty($firstProperty, 'test_value', 'metin');
                
                $this->assertEquals('test_value', $property->getProperty($firstProperty));
            }
        }
    }

    /** @test */
    public function it_validates_all_property_types_correctly()
    {
        $allPropertyClasses = [
            TicariArsa::class, SanayiArsasi::class, KonutArsasi::class,
            Fabrika::class, Depo::class, Magaza::class, Ofis::class, Dukkan::class,
            Daire::class, Rezidans::class, Villa::class, Yali::class, Yazlik::class,
            ButikOtel::class, ApartOtel::class, Hotel::class, Motel::class, TatilKoyu::class,
        ];

        foreach ($allPropertyClasses as $propertyClass) {
            $property = new $propertyClass();
            $rules = $property->getValidationRules();
            
            $this->assertIsArray($rules);
            $this->assertArrayHasKey('baslik', $rules); // Base rule
            $this->assertArrayHasKey('durum', $rules); // Base rule
            
            // Her sınıfın kendine özgü kuralları olmalı
            $specificRules = $property->getSpecificValidationRules();
            $this->assertIsArray($specificRules);
        }
    }

    /** @test */
    public function it_handles_factory_states_for_all_types()
    {
        // Aktif state testi
        $aktifProperties = [
            TicariArsa::factory()->aktif()->create(),
            Fabrika::factory()->aktif()->create(),
            Daire::factory()->aktif()->create(),
            Hotel::factory()->aktif()->create(),
        ];

        foreach ($aktifProperties as $property) {
            $this->assertEquals('aktif', $property->durum);
            $this->assertTrue($property->aktif_mi);
        }

        // Satılmış state testi
        $satilmisProperties = [
            TicariArsa::factory()->satilmis()->create(),
            Fabrika::factory()->satilmis()->create(),
            Daire::factory()->satilmis()->create(),
            Hotel::factory()->satilmis()->create(),
        ];

        foreach ($satilmisProperties as $property) {
            $this->assertEquals('satildi', $property->durum);
            $this->assertFalse($property->aktif_mi);
        }
    }

    /** @test */
    public function it_maintains_data_consistency_across_all_types()
    {
        $allProperties = [
            TicariArsa::factory()->create(['fiyat' => 1000000, 'metrekare' => 200]),
            Fabrika::factory()->create(['fiyat' => 1000000, 'metrekare' => 200]),
            Daire::factory()->create(['fiyat' => 1000000, 'metrekare' => 200]),
            Hotel::factory()->create(['fiyat' => 1000000, 'metrekare' => 200]),
        ];

        foreach ($allProperties as $property) {
            // Formatlanmış değerler tutarlı olmalı
            $this->assertEquals('1.000.000 ₺', $property->formatted_price);
            $this->assertEquals('200 m²', $property->formatted_area);
            $this->assertEquals(5000.0, $property->price_per_square_meter);
            
            // Durum renkleri tutarlı olmalı
            $this->assertContains($property->status_color, ['green', 'gray', 'red', 'blue']);
            
            // Display name boş olmamalı
            $this->assertNotEmpty($property->getDisplayName());
        }
    }

    /** @test */
    public function it_creates_database_records_for_all_types()
    {
        $propertyInstances = [
            TicariArsa::factory()->create(),
            SanayiArsasi::factory()->create(),
            KonutArsasi::factory()->create(),
            Fabrika::factory()->create(),
            Depo::factory()->create(),
            Magaza::factory()->create(),
            Ofis::factory()->create(),
            Dukkan::factory()->create(),
            Daire::factory()->create(),
            Rezidans::factory()->create(),
            Villa::factory()->create(),
            Yali::factory()->create(),
            Yazlik::factory()->create(),
            ButikOtel::factory()->create(),
            ApartOtel::factory()->create(),
            Hotel::factory()->create(),
            Motel::factory()->create(),
            TatilKoyu::factory()->create(),
        ];

        foreach ($propertyInstances as $property) {
            $this->assertDatabaseHas('mulkler', [
                'id' => $property->id,
                'mulk_type' => $property->getMulkType(),
            ]);
        }

        // Toplam 18 farklı mülk tipi oluşturulmuş olmalı
        $this->assertCount(18, $propertyInstances);
    }

    /** @test */
    public function it_has_unique_mulk_types_for_all_classes()
    {
        $allClasses = [
            TicariArsa::class, SanayiArsasi::class, KonutArsasi::class,
            Fabrika::class, Depo::class, Magaza::class, Ofis::class, Dukkan::class,
            Daire::class, Rezidans::class, Villa::class, Yali::class, Yazlik::class,
            ButikOtel::class, ApartOtel::class, Hotel::class, Motel::class, TatilKoyu::class,
        ];

        $mulkTypes = [];
        foreach ($allClasses as $class) {
            $instance = new $class();
            $mulkType = $instance->getMulkType();
            
            $this->assertNotContains($mulkType, $mulkTypes, "Mülk tipi '{$mulkType}' birden fazla sınıfta kullanılıyor");
            $mulkTypes[] = $mulkType;
        }

        // Toplam 18 benzersiz mülk tipi olmalı
        $this->assertCount(18, array_unique($mulkTypes));
    }
}