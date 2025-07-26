<?php

namespace Tests\Unit\Models\Mulk;

use Tests\TestCase;
use App\Models\Mulk\Arsa\TicariArsa;
use App\Models\Mulk\Isyeri\Fabrika;
use App\Models\Mulk\Konut\Daire;
use App\Models\Mulk\TuristikTesis\Hotel;
use App\Models\MulkOzellik;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MulkModelPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_add_properties_to_mulk()
    {
        $fabrika = Fabrika::factory()->create();
        
        $ozellik = $fabrika->addProperty('uretim_alani', 1500, 'sayi', 'm2');
        
        $this->assertInstanceOf(MulkOzellik::class, $ozellik);
        $this->assertEquals('uretim_alani', $ozellik->ozellik_adi);
        $this->assertEquals([1500], $ozellik->ozellik_degeri);
        $this->assertEquals('sayi', $ozellik->ozellik_tipi);
        $this->assertEquals('m2', $ozellik->birim);
        $this->assertTrue($ozellik->aktif_mi);
    }

    /** @test */
    public function it_can_get_property_values()
    {
        $daire = Daire::factory()->create();
        
        $daire->addProperty('oda_sayisi', 3, 'sayi');
        $daire->addProperty('asansor_var_mi', true, 'boolean');
        $daire->addProperty('ozellikler', ['balkon', 'teras'], 'liste');
        
        $this->assertEquals(3, $daire->getProperty('oda_sayisi'));
        $this->assertTrue($daire->getProperty('asansor_var_mi'));
        $this->assertEquals(['balkon', 'teras'], $daire->getProperty('ozellikler'));
        $this->assertNull($daire->getProperty('olmayan_ozellik'));
        $this->assertEquals('varsayilan', $daire->getProperty('olmayan_ozellik', 'varsayilan'));
    }

    /** @test */
    public function it_can_update_property_values()
    {
        $arsa = TicariArsa::factory()->create();
        
        $arsa->addProperty('imar_durumu', 'imarsız', 'metin');
        $this->assertEquals('imarsız', $arsa->getProperty('imar_durumu'));
        
        $updated = $arsa->updateProperty('imar_durumu', 'imarlı');
        $this->assertTrue($updated);
        $this->assertEquals('imarlı', $arsa->getProperty('imar_durumu'));
        
        // Olmayan özellik güncellenemez
        $notUpdated = $arsa->updateProperty('olmayan_ozellik', 'değer');
        $this->assertFalse($notUpdated);
    }

    /** @test */
    public function it_can_remove_properties()
    {
        $hotel = Hotel::factory()->create();
        
        $hotel->addProperty('yildiz_sayisi', 5, 'sayi');
        $this->assertEquals(5, $hotel->getProperty('yildiz_sayisi'));
        
        $removed = $hotel->removeProperty('yildiz_sayisi');
        $this->assertTrue($removed);
        $this->assertNull($hotel->getProperty('yildiz_sayisi'));
        
        // Olmayan özellik silinemez
        $notRemoved = $hotel->removeProperty('olmayan_ozellik');
        $this->assertFalse($notRemoved);
    }

    /** @test */
    public function it_returns_properties_as_array()
    {
        $fabrika = Fabrika::factory()->create();
        
        $fabrika->addProperty('uretim_alani', 2000, 'sayi', 'm2');
        $fabrika->addProperty('vinc_var_mi', true, 'boolean');
        $fabrika->addProperty('ozellikler', ['modern', 'güvenli'], 'liste');
        
        $properties = $fabrika->getPropertiesArray();
        
        $this->assertIsArray($properties);
        $this->assertArrayHasKey('uretim_alani', $properties);
        $this->assertArrayHasKey('vinc_var_mi', $properties);
        $this->assertArrayHasKey('ozellikler', $properties);
        
        $this->assertEquals(2000, $properties['uretim_alani']['value']);
        $this->assertEquals('sayi', $properties['uretim_alani']['type']);
        $this->assertEquals('m2', $properties['uretim_alani']['unit']);
        
        $this->assertTrue($properties['vinc_var_mi']['value']);
        $this->assertEquals('boolean', $properties['vinc_var_mi']['type']);
        
        $this->assertEquals(['modern', 'güvenli'], $properties['ozellikler']['value']);
        $this->assertEquals('liste', $properties['ozellikler']['type']);
    }

    /** @test */
    public function it_validates_property_names_against_valid_properties()
    {
        $fabrika = Fabrika::factory()->create();
        
        // Geçerli özellik eklenebilir
        $validProperty = $fabrika->addProperty('uretim_alani', 1000, 'sayi');
        $this->assertInstanceOf(MulkOzellik::class, $validProperty);
        
        // Geçersiz özellik eklenemez
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'gecersiz_ozellik' bu mülk tipi için geçerli bir özellik değil.");
        
        $fabrika->addProperty('gecersiz_ozellik', 'değer', 'metin');
    }

    /** @test */
    public function it_handles_single_value_arrays_correctly()
    {
        $daire = Daire::factory()->create();
        
        // Tek değer array olarak saklanır ama tek değer olarak döner
        $daire->addProperty('oda_sayisi', 3, 'sayi');
        
        $ozellik = $daire->aktifOzellikler()->where('ozellik_adi', 'oda_sayisi')->first();
        $this->assertEquals([3], $ozellik->ozellik_degeri);
        
        $value = $daire->getProperty('oda_sayisi');
        $this->assertEquals(3, $value); // Array değil, direkt değer
    }

    /** @test */
    public function it_handles_multiple_value_arrays_correctly()
    {
        $arsa = TicariArsa::factory()->create();
        
        // Çoklu değer array olarak kalır
        $arsa->addProperty('avantajlar', ['köşe', 'ana cadde', 'imar'], 'liste');
        
        $ozellik = $arsa->aktifOzellikler()->where('ozellik_adi', 'avantajlar')->first();
        $this->assertEquals(['köşe', 'ana cadde', 'imar'], $ozellik->ozellik_degeri);
        
        $value = $arsa->getProperty('avantajlar');
        $this->assertEquals(['köşe', 'ana cadde', 'imar'], $value); // Array olarak kalır
    }

    /** @test */
    public function it_only_shows_active_properties()
    {
        $hotel = Hotel::factory()->create();
        
        // Aktif özellik
        $aktifOzellik = $hotel->addProperty('oda_sayisi', 50, 'sayi');
        
        // Pasif özellik
        $pasifOzellik = MulkOzellik::create([
            'mulk_id' => $hotel->id,
            'mulk_type' => $hotel->getMulkType(),
            'ozellik_adi' => 'eski_ozellik',
            'ozellik_degeri' => ['eski değer'],
            'ozellik_tipi' => 'metin',
            'aktif_mi' => false,
        ]);
        
        // Sadece aktif özellik görünmeli
        $this->assertEquals(50, $hotel->getProperty('oda_sayisi'));
        $this->assertNull($hotel->getProperty('eski_ozellik'));
        
        $properties = $hotel->getPropertiesArray();
        $this->assertArrayHasKey('oda_sayisi', $properties);
        $this->assertArrayNotHasKey('eski_ozellik', $properties);
    }

    /** @test */
    public function it_filters_properties_by_mulk_type()
    {
        $fabrika = Fabrika::factory()->create();
        $daire = Daire::factory()->create();
        
        // Her mülk için farklı özellikler ekle
        $fabrika->addProperty('uretim_alani', 1000, 'sayi');
        $daire->addProperty('oda_sayisi', 3, 'sayi');
        
        // Fabrika sadece kendi özelliklerini görmeli
        $this->assertEquals(1000, $fabrika->getProperty('uretim_alani'));
        $this->assertNull($fabrika->getProperty('oda_sayisi'));
        
        // Daire sadece kendi özelliklerini görmeli
        $this->assertEquals(3, $daire->getProperty('oda_sayisi'));
        $this->assertNull($daire->getProperty('uretim_alani'));
    }

    /** @test */
    public function it_handles_different_property_types()
    {
        $arsa = TicariArsa::factory()->create();
        
        // Farklı veri tiplerini test et
        $arsa->addProperty('alan', 1500.5, 'sayi', 'm2');
        $arsa->addProperty('imar_durumu', 'imarlı', 'metin');
        $arsa->addProperty('satilik_mi', true, 'boolean');
        $arsa->addProperty('avantajlar', ['köşe', 'merkezi'], 'liste');
        
        $this->assertEquals(1500.5, $arsa->getProperty('alan'));
        $this->assertEquals('imarlı', $arsa->getProperty('imar_durumu'));
        $this->assertTrue($arsa->getProperty('satilik_mi'));
        $this->assertEquals(['köşe', 'merkezi'], $arsa->getProperty('avantajlar'));
        
        $properties = $arsa->getPropertiesArray();
        $this->assertEquals('sayi', $properties['alan']['type']);
        $this->assertEquals('metin', $properties['imar_durumu']['type']);
        $this->assertEquals('boolean', $properties['satilik_mi']['type']);
        $this->assertEquals('liste', $properties['avantajlar']['type']);
    }
}