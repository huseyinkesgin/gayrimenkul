<?php

namespace Tests\Unit\Enums;

use Tests\TestCase;
use App\Enums\MulkKategorisi;
use App\Enums\MusteriKategorisi;
use App\Enums\ResimKategorisi;
use App\Enums\DokumanTipi;
use App\Enums\HatirlatmaTipi;
use App\Enums\NotKategorisi;

class EnumTestHelper extends TestCase
{
    /** @test */
    public function all_enums_have_required_methods()
    {
        $enums = [
            MulkKategorisi::class,
            MusteriKategorisi::class,
            ResimKategorisi::class,
            DokumanTipi::class,
            HatirlatmaTipi::class,
            NotKategorisi::class,
        ];

        foreach ($enums as $enumClass) {
            $this->assertTrue(method_exists($enumClass, 'cases'), "$enumClass should have cases method");
            
            // Her enum'un en az bir case'i olmalı
            $cases = $enumClass::cases();
            $this->assertNotEmpty($cases, "$enumClass should have at least one case");
            
            // Her case için gerekli metodları kontrol et
            foreach ($cases as $case) {
                $this->assertTrue(method_exists($case, 'label'), "$enumClass cases should have label method");
                $this->assertTrue(method_exists($case, 'description'), "$enumClass cases should have description method");
                $this->assertTrue(method_exists($case, 'color'), "$enumClass cases should have color method");
                
                // Metodların string döndürdüğünü kontrol et
                $this->assertIsString($case->label(), "$enumClass label should return string");
                $this->assertIsString($case->description(), "$enumClass description should return string");
                $this->assertIsString($case->color(), "$enumClass color should return string");
            }
            
            // toArray metodunu kontrol et
            if (method_exists($enumClass, 'toArray')) {
                $array = $enumClass::toArray();
                $this->assertIsArray($array, "$enumClass toArray should return array");
                $this->assertNotEmpty($array, "$enumClass toArray should not be empty");
                
                // Array yapısını kontrol et
                foreach ($array as $item) {
                    $this->assertArrayHasKey('value', $item, "$enumClass array items should have value key");
                    $this->assertArrayHasKey('label', $item, "$enumClass array items should have label key");
                    $this->assertArrayHasKey('description', $item, "$enumClass array items should have description key");
                    $this->assertArrayHasKey('color', $item, "$enumClass array items should have color key");
                }
            }
        }
    }

    /** @test */
    public function all_enum_values_are_unique_within_enum()
    {
        $enums = [
            MulkKategorisi::class,
            MusteriKategorisi::class,
            ResimKategorisi::class,
            DokumanTipi::class,
            HatirlatmaTipi::class,
            NotKategorisi::class,
        ];

        foreach ($enums as $enumClass) {
            $cases = $enumClass::cases();
            $values = array_map(fn($case) => $case->value, $cases);
            
            $this->assertEquals(
                count($values), 
                count(array_unique($values)), 
                "$enumClass should have unique values"
            );
        }
    }

    /** @test */
    public function all_enum_labels_are_not_empty()
    {
        $enums = [
            MulkKategorisi::class,
            MusteriKategorisi::class,
            ResimKategorisi::class,
            DokumanTipi::class,
            HatirlatmaTipi::class,
            NotKategorisi::class,
        ];

        foreach ($enums as $enumClass) {
            $cases = $enumClass::cases();
            
            foreach ($cases as $case) {
                $this->assertNotEmpty($case->label(), "$enumClass {$case->value} label should not be empty");
                $this->assertNotEmpty($case->description(), "$enumClass {$case->value} description should not be empty");
                $this->assertNotEmpty($case->color(), "$enumClass {$case->value} color should not be empty");
            }
        }
    }

    /** @test */
    public function enum_colors_are_valid_css_colors()
    {
        $validColors = [
            'red', 'green', 'blue', 'yellow', 'orange', 'purple', 'pink', 
            'gray', 'grey', 'black', 'white', 'indigo', 'cyan', 'teal'
        ];

        $enums = [
            MulkKategorisi::class,
            MusteriKategorisi::class,
            ResimKategorisi::class,
            DokumanTipi::class,
            HatirlatmaTipi::class,
            NotKategorisi::class,
        ];

        foreach ($enums as $enumClass) {
            $cases = $enumClass::cases();
            
            foreach ($cases as $case) {
                $color = $case->color();
                $this->assertContains(
                    $color, 
                    $validColors, 
                    "$enumClass {$case->value} has invalid color: $color"
                );
            }
        }
    }

    /** @test */
    public function enums_can_be_serialized_and_unserialized()
    {
        $testCases = [
            MulkKategorisi::ARSA,
            MusteriKategorisi::ALICI,
            ResimKategorisi::GALERI,
            DokumanTipi::TAPU,
            HatirlatmaTipi::ARAMA,
            NotKategorisi::GENEL,
        ];

        foreach ($testCases as $case) {
            $serialized = serialize($case);
            $unserialized = unserialize($serialized);
            
            $this->assertEquals($case, $unserialized, "Enum case should be serializable");
            $this->assertEquals($case->value, $unserialized->value, "Enum values should match after serialization");
        }
    }

    /** @test */
    public function enums_can_be_json_encoded()
    {
        $testCases = [
            MulkKategorisi::ARSA,
            MusteriKategorisi::ALICI,
            ResimKategorisi::GALERI,
            DokumanTipi::TAPU,
            HatirlatmaTipi::ARAMA,
            NotKategorisi::GENEL,
        ];

        foreach ($testCases as $case) {
            $json = json_encode($case);
            $this->assertNotFalse($json, "Enum case should be JSON encodable");
            
            $decoded = json_decode($json, true);
            $this->assertEquals($case->value, $decoded, "Enum value should match after JSON encoding/decoding");
        }
    }
}