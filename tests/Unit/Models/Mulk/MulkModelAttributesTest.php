<?php

namespace Tests\Unit\Models\Mulk;

use Tests\TestCase;
use App\Models\Mulk\Arsa\TicariArsa;
use App\Models\Mulk\Isyeri\Fabrika;
use App\Models\Mulk\Konut\Daire;
use App\Models\Mulk\TuristikTesis\Hotel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MulkModelAttributesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_formats_price_correctly()
    {
        $testCases = [
            ['fiyat' => 1500000, 'para_birimi' => 'TRY', 'expected' => '1.500.000 ₺'],
            ['fiyat' => 250000, 'para_birimi' => 'USD', 'expected' => '250.000 $'],
            ['fiyat' => 180000, 'para_birimi' => 'EUR', 'expected' => '180.000 €'],
            ['fiyat' => 5000000, 'para_birimi' => 'TRY', 'expected' => '5.000.000 ₺'],
            ['fiyat' => null, 'para_birimi' => 'TRY', 'expected' => 'Belirtilmemiş'],
            ['fiyat' => 0, 'para_birimi' => 'TRY', 'expected' => 'Belirtilmemiş'],
        ];

        foreach ($testCases as $testCase) {
            $arsa = TicariArsa::factory()->create([
                'fiyat' => $testCase['fiyat'],
                'para_birimi' => $testCase['para_birimi'],
            ]);

            $this->assertEquals($testCase['expected'], $arsa->formatted_price);
        }
    }

    /** @test */
    public function it_formats_area_correctly()
    {
        $testCases = [
            ['metrekare' => 120.5, 'expected' => '121 m²'],
            ['metrekare' => 1500, 'expected' => '1.500 m²'],
            ['metrekare' => 85.2, 'expected' => '85 m²'],
            ['metrekare' => 2500.8, 'expected' => '2.501 m²'],
            ['metrekare' => null, 'expected' => 'Belirtilmemiş'],
            ['metrekare' => 0, 'expected' => 'Belirtilmemiş'],
        ];

        foreach ($testCases as $testCase) {
            $fabrika = Fabrika::factory()->create([
                'metrekare' => $testCase['metrekare'],
            ]);

            $this->assertEquals($testCase['expected'], $fabrika->formatted_area);
        }
    }

    /** @test */
    public function it_calculates_price_per_square_meter()
    {
        $testCases = [
            ['fiyat' => 600000, 'metrekare' => 120, 'expected' => 5000.0],
            ['fiyat' => 1500000, 'metrekare' => 300, 'expected' => 5000.0],
            ['fiyat' => 800000, 'metrekare' => 160, 'expected' => 5000.0],
            ['fiyat' => 750000, 'metrekare' => 150, 'expected' => 5000.0],
            ['fiyat' => 1000000, 'metrekare' => 333, 'expected' => 3003.0], // Rounded
            ['fiyat' => null, 'metrekare' => 120, 'expected' => null],
            ['fiyat' => 600000, 'metrekare' => null, 'expected' => null],
            ['fiyat' => 600000, 'metrekare' => 0, 'expected' => null],
        ];

        foreach ($testCases as $testCase) {
            $daire = Daire::factory()->create([
                'fiyat' => $testCase['fiyat'],
                'metrekare' => $testCase['metrekare'],
            ]);

            $this->assertEquals($testCase['expected'], $daire->price_per_square_meter);
        }
    }

    /** @test */
    public function it_returns_correct_status_colors()
    {
        $statusColors = [
            'aktif' => 'green',
            'pasif' => 'gray',
            'satildi' => 'red',
            'kiralandi' => 'blue',
            'unknown_status' => 'gray', // Default
        ];

        foreach ($statusColors as $status => $expectedColor) {
            $hotel = Hotel::factory()->create(['durum' => $status]);
            $this->assertEquals($expectedColor, $hotel->status_color);
        }
    }

    /** @test */
    public function it_returns_correct_status_labels()
    {
        $statusLabels = [
            'aktif' => 'Aktif',
            'pasif' => 'Pasif',
            'satildi' => 'Satıldı',
            'kiralandi' => 'Kiralandı',
            'unknown_status' => 'Bilinmiyor', // Default
        ];

        foreach ($statusLabels as $status => $expectedLabel) {
            $arsa = TicariArsa::factory()->create(['durum' => $status]);
            $this->assertEquals($expectedLabel, $arsa->status_label);
        }
    }

    /** @test */
    public function it_generates_property_url()
    {
        $fabrika = Fabrika::factory()->create();
        $daire = Daire::factory()->create();
        $hotel = Hotel::factory()->create();

        $this->assertEquals(route('mulk.fabrika.show', $fabrika->id), $fabrika->url);
        $this->assertEquals(route('mulk.daire.show', $daire->id), $daire->url);
        $this->assertEquals(route('mulk.hotel.show', $hotel->id), $hotel->url);
    }

    /** @test */
    public function it_generates_seo_friendly_slug()
    {
        $testCases = [
            'Modern Fabrika Satılık' => 'modern-fabrika-satilik',
            'Deniz Manzaralı Villa' => 'deniz-manzarali-villa',
            'Merkezi Konumda Ofis' => 'merkezi-konumda-ofis',
            'Lüks Hotel İmkanı' => 'luks-hotel-imkani',
        ];

        foreach ($testCases as $baslik => $expectedSlugPart) {
            $arsa = TicariArsa::factory()->create(['baslik' => $baslik]);
            $slug = $arsa->slug;
            
            $this->assertIsString($slug);
            $this->assertStringContainsString($expectedSlugPart, $slug);
        }
    }

    /** @test */
    public function it_returns_display_name()
    {
        $basliklar = [
            'Satılık Ticari Arsa',
            'Modern Fabrika',
            'Deniz Manzaralı Daire',
            'Butik Hotel',
        ];

        foreach ($basliklar as $baslik) {
            $mulk = TicariArsa::factory()->create(['baslik' => $baslik]);
            $this->assertEquals($baslik, $mulk->getDisplayName());
        }
    }

    /** @test */
    public function it_handles_null_values_in_formatted_attributes()
    {
        $mulk = Fabrika::factory()->create([
            'fiyat' => null,
            'metrekare' => null,
            'para_birimi' => null,
        ]);

        $this->assertEquals('Belirtilmemiş', $mulk->formatted_price);
        $this->assertEquals('Belirtilmemiş', $mulk->formatted_area);
        $this->assertNull($mulk->price_per_square_meter);
    }

    /** @test */
    public function it_handles_zero_values_correctly()
    {
        $mulk = Daire::factory()->create([
            'fiyat' => 0,
            'metrekare' => 0,
        ]);

        $this->assertEquals('Belirtilmemiş', $mulk->formatted_price);
        $this->assertEquals('Belirtilmemiş', $mulk->formatted_area);
        $this->assertNull($mulk->price_per_square_meter);
    }

    /** @test */
    public function it_formats_large_numbers_correctly()
    {
        $mulk = Hotel::factory()->create([
            'fiyat' => 25000000,
            'metrekare' => 5000,
            'para_birimi' => 'USD',
        ]);

        $this->assertEquals('25.000.000 $', $mulk->formatted_price);
        $this->assertEquals('5.000 m²', $mulk->formatted_area);
        $this->assertEquals(5000.0, $mulk->price_per_square_meter);
    }

    /** @test */
    public function it_handles_decimal_values_in_calculations()
    {
        $mulk = TicariArsa::factory()->create([
            'fiyat' => 1234567.89,
            'metrekare' => 456.78,
        ]);

        // Fiyat formatında ondalık kısım gösterilmez
        $this->assertEquals('1.234.568 ₺', $mulk->formatted_price);
        
        // Metrekare formatında ondalık kısım yuvarlanır
        $this->assertEquals('457 m²', $mulk->formatted_area);
        
        // M2 başına fiyat hesaplaması doğru olmalı
        $expectedPricePerSqm = round(1234567.89 / 456.78, 2);
        $this->assertEquals($expectedPricePerSqm, $mulk->price_per_square_meter);
    }

    /** @test */
    public function it_maintains_consistency_across_different_property_types()
    {
        $properties = [
            TicariArsa::factory()->create(['fiyat' => 1500000, 'metrekare' => 300]),
            Fabrika::factory()->create(['fiyat' => 1500000, 'metrekare' => 300]),
            Daire::factory()->create(['fiyat' => 1500000, 'metrekare' => 300]),
            Hotel::factory()->create(['fiyat' => 1500000, 'metrekare' => 300]),
        ];

        foreach ($properties as $property) {
            $this->assertEquals('1.500.000 ₺', $property->formatted_price);
            $this->assertEquals('300 m²', $property->formatted_area);
            $this->assertEquals(5000.0, $property->price_per_square_meter);
        }
    }

    /** @test */
    public function it_handles_different_currencies_correctly()
    {
        $currencies = [
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
        ];

        foreach ($currencies as $currency => $symbol) {
            $mulk = TicariArsa::factory()->create([
                'fiyat' => 1000000,
                'para_birimi' => $currency,
            ]);

            $expectedFormat = "1.000.000 {$symbol}";
            $this->assertEquals($expectedFormat, $mulk->formatted_price);
        }
    }

    /** @test */
    public function it_generates_unique_slugs_for_same_titles()
    {
        $mulk1 = TicariArsa::factory()->create(['baslik' => 'Aynı Başlık']);
        $mulk2 = TicariArsa::factory()->create(['baslik' => 'Aynı Başlık']);
        $mulk3 = TicariArsa::factory()->create(['baslik' => 'Aynı Başlık']);

        $slug1 = $mulk1->slug;
        $slug2 = $mulk2->slug;
        $slug3 = $mulk3->slug;

        // Slug'lar farklı olmalı
        $this->assertNotEquals($slug1, $slug2);
        $this->assertNotEquals($slug2, $slug3);
        $this->assertNotEquals($slug1, $slug3);

        // Hepsi temel slug'ı içermeli
        $this->assertStringContainsString('ayni-baslik', $slug1);
        $this->assertStringContainsString('ayni-baslik', $slug2);
        $this->assertStringContainsString('ayni-baslik', $slug3);
    }
}