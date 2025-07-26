<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MulkOzellikTanimlariService;

class MulkOzellikTanimlariServiceTest extends TestCase
{
    /** @test */
    public function it_returns_property_definitions_for_valid_mulk_types()
    {
        $mulkTypes = [
            'ticari_arsa',
            'fabrika',
            'daire',
            'butik_otel'
        ];

        foreach ($mulkTypes as $mulkType) {
            $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari($mulkType);
            
            $this->assertIsArray($ozellikler);
            $this->assertNotEmpty($ozellikler);
            
            // Her özellik tanımının gerekli alanları olmalı
            foreach ($ozellikler as $key => $ozellik) {
                $this->assertArrayHasKey('label', $ozellik);
                $this->assertArrayHasKey('type', $ozellik);
                $this->assertArrayHasKey('required', $ozellik);
                $this->assertArrayHasKey('group', $ozellik);
            }
        }
    }

    /** @test */
    public function it_returns_empty_array_for_invalid_mulk_type()
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari('invalid_type');
        
        $this->assertIsArray($ozellikler);
        $this->assertEmpty($ozellikler);
    }

    /** @test */
    public function ticari_arsa_has_expected_properties()
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari('ticari_arsa');
        
        $expectedProperties = [
            'imar_durumu',
            'kaks',
            'gabari',
            'ada_no',
            'parsel_no',
            'ticari_potansiyel',
            'ana_cadde_cephesi',
            'trafik_yogunlugu'
        ];

        foreach ($expectedProperties as $property) {
            $this->assertArrayHasKey($property, $ozellikler);
        }
    }

    /** @test */
    public function fabrika_has_expected_properties()
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari('fabrika');
        
        $expectedProperties = [
            'kapali_alan',
            'uretim_alani',
            'vinc_kapasitesi',
            'atiksu_aritma_sistemi',
            'patlama_riski_sinifi'
        ];

        foreach ($expectedProperties as $property) {
            $this->assertArrayHasKey($property, $ozellikler);
        }
    }

    /** @test */
    public function daire_has_expected_properties()
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari('daire');
        
        $expectedProperties = [
            'oda_sayisi',
            'salon_sayisi',
            'banyo_sayisi',
            'site_adi',
            'aidat_miktari',
            'kat_no'
        ];

        foreach ($expectedProperties as $property) {
            $this->assertArrayHasKey($property, $ozellikler);
        }
    }

    /** @test */
    public function it_returns_property_groups_for_mulk_type()
    {
        $groups = MulkOzellikTanimlariService::getOzellikGruplari('ticari_arsa');
        
        $this->assertIsArray($groups);
        $this->assertNotEmpty($groups);
        $this->assertContains('İmar Bilgileri', $groups);
        $this->assertContains('Tapu Bilgileri', $groups);
        $this->assertContains('Ticari Özellikler', $groups);
    }

    /** @test */
    public function it_returns_properties_by_group()
    {
        $imarOzellikleri = MulkOzellikTanimlariService::getOzelliklerByGroup('ticari_arsa', 'İmar Bilgileri');
        
        $this->assertIsArray($imarOzellikleri);
        $this->assertArrayHasKey('imar_durumu', $imarOzellikleri);
        $this->assertArrayHasKey('kaks', $imarOzellikleri);
        $this->assertArrayHasKey('gabari', $imarOzellikleri);
    }

    /** @test */
    public function property_definitions_have_correct_structure()
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari('fabrika');
        
        foreach ($ozellikler as $key => $ozellik) {
            // Required fields
            $this->assertArrayHasKey('label', $ozellik);
            $this->assertArrayHasKey('type', $ozellik);
            $this->assertArrayHasKey('required', $ozellik);
            $this->assertArrayHasKey('group', $ozellik);
            
            // Type should be valid
            $validTypes = ['text', 'number', 'textarea', 'select', 'checkbox'];
            $this->assertContains($ozellik['type'], $validTypes);
            
            // Required should be boolean
            $this->assertIsBool($ozellik['required']);
            
            // Group should be string
            $this->assertIsString($ozellik['group']);
            
            // Type-specific validations
            if ($ozellik['type'] === 'select') {
                $this->assertArrayHasKey('options', $ozellik);
                $this->assertIsArray($ozellik['options']);
            }
            
            if ($ozellik['type'] === 'number') {
                if (isset($ozellik['min'])) {
                    $this->assertIsNumeric($ozellik['min']);
                }
                if (isset($ozellik['max'])) {
                    $this->assertIsNumeric($ozellik['max']);
                }
            }
        }
    }

    /** @test */
    public function select_properties_have_valid_options()
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari('ticari_arsa');
        
        $selectProperties = array_filter($ozellikler, function($ozellik) {
            return $ozellik['type'] === 'select';
        });

        foreach ($selectProperties as $key => $ozellik) {
            $this->assertArrayHasKey('options', $ozellik);
            $this->assertIsArray($ozellik['options']);
            $this->assertNotEmpty($ozellik['options']);
            
            // Options should have key-value pairs
            foreach ($ozellik['options'] as $optionKey => $optionLabel) {
                $this->assertIsString($optionKey);
                $this->assertIsString($optionLabel);
            }
        }
    }

    /** @test */
    public function number_properties_have_valid_constraints()
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari('fabrika');
        
        $numberProperties = array_filter($ozellikler, function($ozellik) {
            return $ozellik['type'] === 'number';
        });

        foreach ($numberProperties as $key => $ozellik) {
            if (isset($ozellik['min']) && isset($ozellik['max'])) {
                $this->assertLessThanOrEqual($ozellik['max'], $ozellik['min']);
            }
            
            if (isset($ozellik['step'])) {
                $this->assertGreaterThan(0, $ozellik['step']);
            }
        }
    }
}