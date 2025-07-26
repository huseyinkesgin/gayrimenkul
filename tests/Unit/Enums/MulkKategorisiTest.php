<?php

namespace Tests\Unit\Enums;

use Tests\TestCase;
use App\Enums\MulkKategorisi;
use App\Enums\ResimKategorisi;
use App\Enums\DokumanTipi;

class MulkKategorisiTest extends TestCase
{
    /** @test */
    public function it_has_correct_values()
    {
        $this->assertEquals('arsa', MulkKategorisi::ARSA->value);
        $this->assertEquals('isyeri', MulkKategorisi::ISYERI->value);
        $this->assertEquals('konut', MulkKategorisi::KONUT->value);
        $this->assertEquals('turistik_tesis', MulkKategorisi::TURISTIK_TESIS->value);
    }

    /** @test */
    public function it_returns_correct_labels()
    {
        $this->assertEquals('Arsa', MulkKategorisi::ARSA->label());
        $this->assertEquals('İşyeri', MulkKategorisi::ISYERI->label());
        $this->assertEquals('Konut', MulkKategorisi::KONUT->label());
        $this->assertEquals('Turistik Tesis', MulkKategorisi::TURISTIK_TESIS->label());
    }

    /** @test */
    public function it_returns_correct_descriptions()
    {
        $this->assertStringContainsString('arazi', MulkKategorisi::ARSA->description());
        $this->assertStringContainsString('ticari', MulkKategorisi::ISYERI->description());
        $this->assertStringContainsString('yaşam', MulkKategorisi::KONUT->description());
        $this->assertStringContainsString('turizm', MulkKategorisi::TURISTIK_TESIS->description());
    }

    /** @test */
    public function it_returns_correct_colors()
    {
        $this->assertEquals('green', MulkKategorisi::ARSA->color());
        $this->assertEquals('blue', MulkKategorisi::ISYERI->color());
        $this->assertEquals('orange', MulkKategorisi::KONUT->color());
        $this->assertEquals('purple', MulkKategorisi::TURISTIK_TESIS->color());
    }

    /** @test */
    public function it_returns_correct_sub_categories()
    {
        $arsaSubCategories = MulkKategorisi::ARSA->subCategories();
        $this->assertArrayHasKey('ticari_arsa', $arsaSubCategories);
        $this->assertArrayHasKey('sanayi_arsasi', $arsaSubCategories);
        $this->assertArrayHasKey('konut_arsasi', $arsaSubCategories);

        $isyeriSubCategories = MulkKategorisi::ISYERI->subCategories();
        $this->assertArrayHasKey('depo', $isyeriSubCategories);
        $this->assertArrayHasKey('fabrika', $isyeriSubCategories);
        $this->assertArrayHasKey('magaza', $isyeriSubCategories);
    }

    /** @test */
    public function it_correctly_identifies_gallery_requirement()
    {
        $this->assertFalse(MulkKategorisi::ARSA->requiresGallery());
        $this->assertTrue(MulkKategorisi::ISYERI->requiresGallery());
        $this->assertTrue(MulkKategorisi::KONUT->requiresGallery());
        $this->assertTrue(MulkKategorisi::TURISTIK_TESIS->requiresGallery());
    }

    /** @test */
    public function it_returns_supported_image_categories()
    {
        $arsaCategories = MulkKategorisi::ARSA->supportedImageCategories();
        $this->assertContains(ResimKategorisi::UYDU, $arsaCategories);
        $this->assertContains(ResimKategorisi::EIMAR, $arsaCategories);
        $this->assertNotContains(ResimKategorisi::GALERI, $arsaCategories);

        $konutCategories = MulkKategorisi::KONUT->supportedImageCategories();
        $this->assertContains(ResimKategorisi::GALERI, $konutCategories);
        $this->assertNotContains(ResimKategorisi::UYDU, $konutCategories);
    }

    /** @test */
    public function it_returns_supported_document_types()
    {
        $arsaDocuments = MulkKategorisi::ARSA->supportedDocumentTypes();
        $this->assertContains(DokumanTipi::TAPU, $arsaDocuments);
        $this->assertNotContains(DokumanTipi::AUTOCAD, $arsaDocuments);

        $isyeriDocuments = MulkKategorisi::ISYERI->supportedDocumentTypes();
        $this->assertContains(DokumanTipi::TAPU, $isyeriDocuments);
        $this->assertContains(DokumanTipi::AUTOCAD, $isyeriDocuments);
        $this->assertContains(DokumanTipi::PROJE_RESMI, $isyeriDocuments);
    }

    /** @test */
    public function it_returns_correct_icons()
    {
        $this->assertEquals('map', MulkKategorisi::ARSA->icon());
        $this->assertEquals('building-office', MulkKategorisi::ISYERI->icon());
        $this->assertEquals('home', MulkKategorisi::KONUT->icon());
        $this->assertEquals('building-storefront', MulkKategorisi::TURISTIK_TESIS->icon());
    }

    /** @test */
    public function it_converts_to_array()
    {
        $array = MulkKategorisi::toArray();
        
        $this->assertIsArray($array);
        $this->assertCount(4, $array);
        
        $firstItem = $array[0];
        $this->assertArrayHasKey('value', $firstItem);
        $this->assertArrayHasKey('label', $firstItem);
        $this->assertArrayHasKey('description', $firstItem);
        $this->assertArrayHasKey('color', $firstItem);
        $this->assertArrayHasKey('subCategories', $firstItem);
    }

    /** @test */
    public function it_gets_sub_category_label()
    {
        $label = MulkKategorisi::getSubCategoryLabel('arsa', 'ticari_arsa');
        $this->assertEquals('Ticari Arsa', $label);

        $label = MulkKategorisi::getSubCategoryLabel('isyeri', 'fabrika');
        $this->assertEquals('Fabrika', $label);

        // Geçersiz alt kategori için orijinal değeri döndürmeli
        $label = MulkKategorisi::getSubCategoryLabel('arsa', 'gecersiz_kategori');
        $this->assertEquals('gecersiz_kategori', $label);
    }
}