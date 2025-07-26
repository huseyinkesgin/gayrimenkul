<?php

namespace Tests\Unit\Models\Mulk;

use Tests\TestCase;
use App\Models\Mulk\Arsa\TicariArsa;
use App\Models\Mulk\Isyeri\Fabrika;
use App\Models\Mulk\Konut\Daire;
use App\Models\Mulk\TuristikTesis\Hotel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BaseMulkFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_property_models_with_factories()
    {
        $ticariArsa = TicariArsa::factory()->create();
        $fabrika = Fabrika::factory()->create();
        $daire = Daire::factory()->create();
        $hotel = Hotel::factory()->create();

        $this->assertInstanceOf(TicariArsa::class, $ticariArsa);
        $this->assertInstanceOf(Fabrika::class, $fabrika);
        $this->assertInstanceOf(Daire::class, $daire);
        $this->assertInstanceOf(Hotel::class, $hotel);

        $this->assertDatabaseHas('mulkler', ['id' => $ticariArsa->id]);
        $this->assertDatabaseHas('mulkler', ['id' => $fabrika->id]);
        $this->assertDatabaseHas('mulkler', ['id' => $daire->id]);
        $this->assertDatabaseHas('mulkler', ['id' => $hotel->id]);
    }

    /** @test */
    public function it_creates_properties_with_correct_types()
    {
        $ticariArsa = TicariArsa::factory()->create();
        $fabrika = Fabrika::factory()->create();
        $daire = Daire::factory()->create();
        $hotel = Hotel::factory()->create();

        $this->assertEquals('ticari_arsa', $ticariArsa->getMulkType());
        $this->assertEquals('fabrika', $fabrika->getMulkType());
        $this->assertEquals('daire', $daire->getMulkType());
        $this->assertEquals('hotel', $hotel->getMulkType());
    }

    /** @test */
    public function it_creates_properties_with_valid_data()
    {
        $properties = [
            TicariArsa::factory()->create(),
            Fabrika::factory()->create(),
            Daire::factory()->create(),
            Hotel::factory()->create(),
        ];

        foreach ($properties as $property) {
            $this->assertNotEmpty($property->baslik);
            $this->assertIsNumeric($property->fiyat);
            $this->assertIsNumeric($property->metrekare);
            $this->assertContains($property->durum, ['aktif', 'pasif', 'satildi', 'kiralandi']);
            $this->assertContains($property->para_birimi, ['TRY', 'USD', 'EUR']);
            $this->assertIsBool($property->aktif_mi);
        }
    }

    /** @test */
    public function it_creates_properties_with_states()
    {
        $aktifArsa = TicariArsa::factory()->aktif()->create();
        $satilmisFabrika = Fabrika::factory()->satilmis()->create();
        $yuksekFiyatliDaire = Daire::factory()->yuksekFiyatli()->create();
        $buyukHotel = Hotel::factory()->buyukMetrekare()->create();

        $this->assertEquals('aktif', $aktifArsa->durum);
        $this->assertTrue($aktifArsa->aktif_mi);

        $this->assertEquals('satildi', $satilmisFabrika->durum);
        $this->assertFalse($satilmisFabrika->aktif_mi);

        $this->assertGreaterThanOrEqual(2000000, $yuksekFiyatliDaire->fiyat);
        $this->assertContains($yuksekFiyatliDaire->para_birimi, ['USD', 'EUR']);

        $this->assertGreaterThanOrEqual(500, $buyukHotel->metrekare);
    }

    /** @test */
    public function it_creates_multiple_properties_with_factory()
    {
        $arsalar = TicariArsa::factory()->count(5)->create();
        $fabrikalar = Fabrika::factory()->count(3)->create();

        $this->assertCount(5, $arsalar);
        $this->assertCount(3, $fabrikalar);

        foreach ($arsalar as $arsa) {
            $this->assertEquals('ticari_arsa', $arsa->getMulkType());
        }

        foreach ($fabrikalar as $fabrika) {
            $this->assertEquals('fabrika', $fabrika->getMulkType());
        }
    }

    /** @test */
    public function it_creates_properties_with_specific_attributes()
    {
        $customArsa = TicariArsa::factory()->create([
            'baslik' => 'Özel Ticari Arsa',
            'fiyat' => 1500000,
            'metrekare' => 800,
            'durum' => 'aktif',
        ]);

        $this->assertEquals('Özel Ticari Arsa', $customArsa->baslik);
        $this->assertEquals(1500000, $customArsa->fiyat);
        $this->assertEquals(800, $customArsa->metrekare);
        $this->assertEquals('aktif', $customArsa->durum);
    }

    /** @test */
    public function it_creates_properties_with_realistic_price_ranges()
    {
        $arsa = TicariArsa::factory()->create();
        $fabrika = Fabrika::factory()->create();
        $daire = Daire::factory()->create();
        $hotel = Hotel::factory()->create();

        // Arsa fiyat aralığı kontrolü
        $this->assertGreaterThanOrEqual(200000, $arsa->fiyat);
        $this->assertLessThanOrEqual(4000000, $arsa->fiyat);

        // Fabrika fiyat aralığı kontrolü
        $this->assertGreaterThanOrEqual(1500000, $fabrika->fiyat);
        $this->assertLessThanOrEqual(15000000, $fabrika->fiyat);

        // Daire fiyat aralığı kontrolü
        $this->assertGreaterThanOrEqual(500000, $daire->fiyat);
        $this->assertLessThanOrEqual(2500000, $daire->fiyat);

        // Hotel fiyat aralığı kontrolü
        $this->assertGreaterThanOrEqual(5000000, $hotel->fiyat);
        $this->assertLessThanOrEqual(50000000, $hotel->fiyat);
    }

    /** @test */
    public function it_creates_properties_with_realistic_area_ranges()
    {
        $arsa = TicariArsa::factory()->create();
        $fabrika = Fabrika::factory()->create();
        $daire = Daire::factory()->create();
        $hotel = Hotel::factory()->create();

        // Arsa metrekare aralığı
        $this->assertGreaterThanOrEqual(500, $arsa->metrekare);
        $this->assertLessThanOrEqual(3000, $arsa->metrekare);

        // Fabrika metrekare aralığı
        $this->assertGreaterThanOrEqual(1000, $fabrika->metrekare);
        $this->assertLessThanOrEqual(10000, $fabrika->metrekare);

        // Daire metrekare aralığı
        $this->assertGreaterThanOrEqual(80, $daire->metrekare);
        $this->assertLessThanOrEqual(250, $daire->metrekare);

        // Hotel metrekare aralığı
        $this->assertGreaterThanOrEqual(2000, $hotel->metrekare);
        $this->assertLessThanOrEqual(15000, $hotel->metrekare);
    }
}