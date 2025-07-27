<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DokumanYonetimService;
use App\Enums\DokumanTipi;

/**
 * Döküman Yönetim Servisi Validation Testleri
 * 
 * Bu testler veritabanı bağlantısı gerektirmez ve
 * sadece business logic'i test eder.
 */
class DokumanYonetimServiceValidationTest extends TestCase
{
    private DokumanYonetimService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock service oluştur
        $this->service = $this->getMockBuilder(DokumanYonetimService::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods([])
                             ->getMock();
    }

    /** @test */
    public function dokuman_tipi_enum_degerleri_dogru()
    {
        // Temel döküman tipleri mevcut mu?
        $this->assertTrue(DokumanTipi::TAPU instanceof DokumanTipi);
        $this->assertTrue(DokumanTipi::AUTOCAD instanceof DokumanTipi);
        $this->assertTrue(DokumanTipi::PROJE_RESMI instanceof DokumanTipi);
        $this->assertTrue(DokumanTipi::RUHSAT instanceof DokumanTipi);
        
        // Enum değerleri doğru mu?
        $this->assertEquals('tapu', DokumanTipi::TAPU->value);
        $this->assertEquals('autocad', DokumanTipi::AUTOCAD->value);
        $this->assertEquals('proje_resmi', DokumanTipi::PROJE_RESMI->value);
    }

    /** @test */
    public function dokuman_tipi_labels_dogru()
    {
        $this->assertEquals('Tapu', DokumanTipi::TAPU->label());
        $this->assertEquals('AutoCAD Dosyası', DokumanTipi::AUTOCAD->label());
        $this->assertEquals('Proje Resmi', DokumanTipi::PROJE_RESMI->label());
        $this->assertEquals('Ruhsat', DokumanTipi::RUHSAT->label());
    }

    /** @test */
    public function dokuman_tipi_allowed_mime_types_dogru()
    {
        // TAPU için PDF ve resim formatları
        $tapuMimeTypes = DokumanTipi::TAPU->allowedMimeTypes();
        $this->assertContains('application/pdf', $tapuMimeTypes);
        $this->assertContains('image/jpeg', $tapuMimeTypes);
        $this->assertContains('image/png', $tapuMimeTypes);

        // AUTOCAD için CAD formatları
        $autocadMimeTypes = DokumanTipi::AUTOCAD->allowedMimeTypes();
        $this->assertContains('application/dwg', $autocadMimeTypes);
        $this->assertContains('application/dxf', $autocadMimeTypes);
        
        // AUTOCAD için PDF olmamalı
        $this->assertNotContains('application/pdf', $autocadMimeTypes);
    }

    /** @test */
    public function dokuman_tipi_max_file_sizes_dogru()
    {
        // TAPU için 10MB
        $this->assertEquals(10, DokumanTipi::TAPU->maxFileSize());
        
        // AUTOCAD için 50MB (daha büyük)
        $this->assertEquals(50, DokumanTipi::AUTOCAD->maxFileSize());
        
        // PROJE_RESMI için 25MB
        $this->assertEquals(25, DokumanTipi::PROJE_RESMI->maxFileSize());
    }

    /** @test */
    public function mulk_tipine_gore_dokuman_tipleri_dogru()
    {
        // Arsa için uygun tipler
        $arsaTipleri = DokumanTipi::forMulkType('arsa');
        $this->assertContains(DokumanTipi::TAPU, $arsaTipleri);
        $this->assertContains(DokumanTipi::IMAR_PLANI, $arsaTipleri);
        $this->assertNotContains(DokumanTipi::AUTOCAD, $arsaTipleri);

        // İşyeri için uygun tipler
        $isyeriTipleri = DokumanTipi::forMulkType('isyeri');
        $this->assertContains(DokumanTipi::TAPU, $isyeriTipleri);
        $this->assertContains(DokumanTipi::AUTOCAD, $isyeriTipleri);
        $this->assertContains(DokumanTipi::PROJE_RESMI, $isyeriTipleri);
        $this->assertContains(DokumanTipi::YANGIN_RAPORU, $isyeriTipleri);

        // Konut için uygun tipler
        $konutTipleri = DokumanTipi::forMulkType('konut');
        $this->assertContains(DokumanTipi::TAPU, $konutTipleri);
        $this->assertContains(DokumanTipi::PROJE_RESMI, $konutTipleri);
        $this->assertNotContains(DokumanTipi::AUTOCAD, $konutTipleri);

        // Turistik tesis için uygun tipler
        $turistikTipleri = DokumanTipi::forMulkType('turistik_tesis');
        $this->assertContains(DokumanTipi::TAPU, $turistikTipleri);
        $this->assertContains(DokumanTipi::AUTOCAD, $turistikTipleri);
        $this->assertContains(DokumanTipi::CEVRE_IZNI, $turistikTipleri);
    }

    /** @test */
    public function zorunlu_dokuman_tipleri_dogru()
    {
        // TAPU zorunlu olmalı
        $this->assertTrue(DokumanTipi::TAPU->isRequired());
        
        // Diğerleri zorunlu olmamalı
        $this->assertFalse(DokumanTipi::AUTOCAD->isRequired());
        $this->assertFalse(DokumanTipi::PROJE_RESMI->isRequired());
        $this->assertFalse(DokumanTipi::RUHSAT->isRequired());
    }

    /** @test */
    public function dokuman_tipi_to_array_dogru_format()
    {
        $array = DokumanTipi::toArray();
        
        $this->assertIsArray($array);
        $this->assertNotEmpty($array);
        
        // İlk elemanı kontrol et
        $firstItem = $array[0];
        $this->assertArrayHasKey('value', $firstItem);
        $this->assertArrayHasKey('label', $firstItem);
        $this->assertArrayHasKey('description', $firstItem);
        
        // Değerlerin doğru tipte olduğunu kontrol et
        $this->assertIsString($firstItem['value']);
        $this->assertIsString($firstItem['label']);
        $this->assertIsString($firstItem['description']);
    }

    /** @test */
    public function dokuman_tipi_descriptions_bos_degil()
    {
        $allTypes = DokumanTipi::cases();
        
        foreach ($allTypes as $type) {
            $description = $type->description();
            $this->assertNotEmpty($description, "Description for {$type->value} should not be empty");
            $this->assertIsString($description);
        }
    }

    /** @test */
    public function tum_mulk_tipleri_icin_dokuman_tipleri_tanimli()
    {
        $mulkTipleri = ['arsa', 'isyeri', 'konut', 'turistik_tesis'];
        
        foreach ($mulkTipleri as $mulkTipi) {
            $dokumanTipleri = DokumanTipi::forMulkType($mulkTipi);
            $this->assertNotEmpty($dokumanTipleri, "Document types for {$mulkTipi} should not be empty");
            $this->assertIsArray($dokumanTipleri);
            
            // Her mülk tipi için TAPU olmalı
            $this->assertContains(DokumanTipi::TAPU, $dokumanTipleri, "TAPU should be available for {$mulkTipi}");
        }
    }

    /** @test */
    public function bilinmeyen_mulk_tipi_icin_default_dokuman_tipleri()
    {
        $defaultTipleri = DokumanTipi::forMulkType('bilinmeyen_tip');
        
        $this->assertNotEmpty($defaultTipleri);
        $this->assertContains(DokumanTipi::DIGER, $defaultTipleri);
    }

    /** @test */
    public function dokuman_tipi_mime_types_gecerli()
    {
        $allTypes = DokumanTipi::cases();
        $validMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/tiff',
            'application/dwg',
            'application/dxf',
            'application/acad',
            'application/x-autocad',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        foreach ($allTypes as $type) {
            $allowedTypes = $type->allowedMimeTypes();
            $this->assertNotEmpty($allowedTypes, "Allowed mime types for {$type->value} should not be empty");
            
            foreach ($allowedTypes as $mimeType) {
                $this->assertContains($mimeType, $validMimeTypes, "Invalid mime type {$mimeType} for {$type->value}");
            }
        }
    }

    /** @test */
    public function dokuman_tipi_max_file_sizes_pozitif()
    {
        $allTypes = DokumanTipi::cases();
        
        foreach ($allTypes as $type) {
            $maxSize = $type->maxFileSize();
            $this->assertGreaterThan(0, $maxSize, "Max file size for {$type->value} should be positive");
            $this->assertLessThanOrEqual(100, $maxSize, "Max file size for {$type->value} should be reasonable (<=100MB)");
        }
    }
}