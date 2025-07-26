<?php

namespace Tests\Unit\Enums;

use Tests\TestCase;
use App\Enums\MusteriKategorisi;

class MusteriKategorisiTest extends TestCase
{
    /** @test */
    public function it_has_correct_values()
    {
        $this->assertEquals('satici', MusteriKategorisi::SATICI->value);
        $this->assertEquals('alici', MusteriKategorisi::ALICI->value);
        $this->assertEquals('mal_sahibi', MusteriKategorisi::MAL_SAHIBI->value);
        $this->assertEquals('partner', MusteriKategorisi::PARTNER->value);
        $this->assertEquals('tedarikci', MusteriKategorisi::TEDARIKCI->value);
        $this->assertEquals('diger', MusteriKategorisi::DIGER->value);
    }

    /** @test */
    public function it_returns_correct_labels()
    {
        $this->assertEquals('Satıcı', MusteriKategorisi::SATICI->label());
        $this->assertEquals('Alıcı', MusteriKategorisi::ALICI->label());
        $this->assertEquals('Mal Sahibi', MusteriKategorisi::MAL_SAHIBI->label());
        $this->assertEquals('Partner', MusteriKategorisi::PARTNER->label());
        $this->assertEquals('Tedarikçi', MusteriKategorisi::TEDARIKCI->label());
        $this->assertEquals('Diğer', MusteriKategorisi::DIGER->label());
    }

    /** @test */
    public function it_returns_correct_descriptions()
    {
        $this->assertStringContainsString('satan', MusteriKategorisi::SATICI->description());
        $this->assertStringContainsString('alan', MusteriKategorisi::ALICI->description());
        $this->assertStringContainsString('sahibi', MusteriKategorisi::MAL_SAHIBI->description());
        $this->assertStringContainsString('ortağı', MusteriKategorisi::PARTNER->description());
    }

    /** @test */
    public function it_returns_correct_priorities()
    {
        $this->assertEquals(10, MusteriKategorisi::ALICI->priority());
        $this->assertEquals(9, MusteriKategorisi::SATICI->priority());
        $this->assertEquals(8, MusteriKategorisi::MAL_SAHIBI->priority());
        $this->assertEquals(7, MusteriKategorisi::PARTNER->priority());
        $this->assertEquals(6, MusteriKategorisi::TEDARIKCI->priority());
        $this->assertEquals(5, MusteriKategorisi::DIGER->priority());
    }

    /** @test */
    public function it_returns_correct_colors()
    {
        $this->assertEquals('green', MusteriKategorisi::SATICI->color());
        $this->assertEquals('blue', MusteriKategorisi::ALICI->color());
        $this->assertEquals('purple', MusteriKategorisi::MAL_SAHIBI->color());
        $this->assertEquals('orange', MusteriKategorisi::PARTNER->color());
        $this->assertEquals('yellow', MusteriKategorisi::TEDARIKCI->color());
        $this->assertEquals('gray', MusteriKategorisi::DIGER->color());
    }

    /** @test */
    public function it_returns_correct_icons()
    {
        $this->assertEquals('currency-dollar', MusteriKategorisi::SATICI->icon());
        $this->assertEquals('shopping-cart', MusteriKategorisi::ALICI->icon());
        $this->assertEquals('key', MusteriKategorisi::MAL_SAHIBI->icon());
        $this->assertEquals('handshake', MusteriKategorisi::PARTNER->icon());
        $this->assertEquals('truck', MusteriKategorisi::TEDARIKCI->icon());
        $this->assertEquals('user', MusteriKategorisi::DIGER->icon());
    }

    /** @test */
    public function it_returns_required_fields()
    {
        $saticiFields = MusteriKategorisi::SATICI->requiredFields();
        $this->assertContains('telefon', $saticiFields);
        $this->assertContains('email', $saticiFields);
        $this->assertContains('adres', $saticiFields);

        $aliciFields = MusteriKategorisi::ALICI->requiredFields();
        $this->assertContains('butce_araligi', $aliciFields);

        $partnerFields = MusteriKategorisi::PARTNER->requiredFields();
        $this->assertContains('firma_bilgileri', $partnerFields);
    }

    /** @test */
    public function it_returns_default_service_types()
    {
        $saticiServices = MusteriKategorisi::SATICI->defaultServiceTypes();
        $this->assertContains('degerlendirme', $saticiServices);
        $this->assertContains('pazarlama', $saticiServices);

        $aliciServices = MusteriKategorisi::ALICI->defaultServiceTypes();
        $this->assertContains('portfoy_sunumu', $aliciServices);
        $this->assertContains('gezdir', $aliciServices);
    }

    /** @test */
    public function it_correctly_identifies_active_categories()
    {
        $this->assertTrue(MusteriKategorisi::SATICI->isActive());
        $this->assertTrue(MusteriKategorisi::ALICI->isActive());
        $this->assertTrue(MusteriKategorisi::MAL_SAHIBI->isActive());
        $this->assertTrue(MusteriKategorisi::PARTNER->isActive());
        $this->assertTrue(MusteriKategorisi::TEDARIKCI->isActive());
        $this->assertFalse(MusteriKategorisi::DIGER->isActive());
    }

    /** @test */
    public function it_correctly_identifies_special_reporting_requirement()
    {
        $this->assertTrue(MusteriKategorisi::SATICI->requiresSpecialReporting());
        $this->assertTrue(MusteriKategorisi::ALICI->requiresSpecialReporting());
        $this->assertTrue(MusteriKategorisi::PARTNER->requiresSpecialReporting());
        $this->assertFalse(MusteriKategorisi::MAL_SAHIBI->requiresSpecialReporting());
        $this->assertFalse(MusteriKategorisi::TEDARIKCI->requiresSpecialReporting());
        $this->assertFalse(MusteriKategorisi::DIGER->requiresSpecialReporting());
    }

    /** @test */
    public function it_converts_to_array()
    {
        $array = MusteriKategorisi::toArray();
        
        $this->assertIsArray($array);
        $this->assertCount(6, $array);
        
        $firstItem = $array[0];
        $this->assertArrayHasKey('value', $firstItem);
        $this->assertArrayHasKey('label', $firstItem);
        $this->assertArrayHasKey('description', $firstItem);
        $this->assertArrayHasKey('color', $firstItem);
        $this->assertArrayHasKey('priority', $firstItem);
        $this->assertArrayHasKey('requiredFields', $firstItem);
    }

    /** @test */
    public function it_returns_categories_by_priority()
    {
        $byPriority = MusteriKategorisi::byPriority();
        
        $this->assertIsArray($byPriority);
        $this->assertEquals(MusteriKategorisi::ALICI, $byPriority[0]); // En yüksek öncelik
        $this->assertEquals(MusteriKategorisi::SATICI, $byPriority[1]);
        $this->assertEquals(MusteriKategorisi::DIGER, end($byPriority)); // En düşük öncelik
    }

    /** @test */
    public function it_returns_active_categories()
    {
        $activeCategories = MusteriKategorisi::activeCategories();
        
        $this->assertIsArray($activeCategories);
        $this->assertCount(5, $activeCategories); // DIGER hariç
        
        $activeValues = array_map(fn($cat) => $cat->value, $activeCategories);
        $this->assertNotContains('diger', $activeValues);
    }

    /** @test */
    public function it_allows_multiple_selection()
    {
        $this->assertTrue(MusteriKategorisi::allowsMultipleSelection());
    }
}