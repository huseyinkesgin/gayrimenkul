<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\MulkOzellik;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MulkOzellikTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_fields()
    {
        $fillable = [
            'mulk_id', 'mulk_type', 'ozellik_adi', 'ozellik_degeri',
            'ozellik_tipi', 'birim', 'aktif_mi', 'siralama'
        ];
        
        $ozellik = new MulkOzellik();
        $this->assertEquals($fillable, $ozellik->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $expectedCasts = [
            'ozellik_degeri' => 'json',
            'aktif_mi' => 'boolean',
            'siralama' => 'integer',
        ];
        
        $ozellik = new MulkOzellik();
        foreach ($expectedCasts as $field => $cast) {
            $this->assertEquals($cast, $ozellik->getCasts()[$field]);
        }
    }

    /** @test */
    public function it_formats_value_correctly()
    {
        $ozellik = new MulkOzellik();
        
        // Single value array
        $ozellik->ozellik_degeri = ['Test Value'];
        $ozellik->ozellik_tipi = 'metin';
        $this->assertEquals('Test Value', $ozellik->formatted_value);
        
        // Multiple values
        $ozellik->ozellik_degeri = ['Value 1', 'Value 2'];
        $this->assertEquals('Value 1, Value 2', $ozellik->formatted_value);
        
        // Boolean true
        $ozellik->ozellik_degeri = [true];
        $ozellik->ozellik_tipi = 'boolean';
        $this->assertEquals('Evet', $ozellik->formatted_value);
        
        // Boolean false
        $ozellik->ozellik_degeri = [false];
        $this->assertEquals('Hayır', $ozellik->formatted_value);
        
        // Number with unit
        $ozellik->ozellik_degeri = [120];
        $ozellik->ozellik_tipi = 'sayi';
        $ozellik->birim = 'm²';
        $this->assertEquals('120 m²', $ozellik->formatted_value);
    }

    /** @test */
    public function it_returns_correct_input_type()
    {
        $ozellik = new MulkOzellik();
        
        $ozellik->ozellik_tipi = 'sayi';
        $this->assertEquals('number', $ozellik->input_type);
        
        $ozellik->ozellik_tipi = 'boolean';
        $this->assertEquals('checkbox', $ozellik->input_type);
        
        $ozellik->ozellik_tipi = 'liste';
        $this->assertEquals('select', $ozellik->input_type);
        
        $ozellik->ozellik_tipi = 'metin';
        $this->assertEquals('text', $ozellik->input_type);
    }

    /** @test */
    public function it_formats_name_correctly()
    {
        $ozellik = new MulkOzellik();
        $ozellik->ozellik_adi = 'oda_sayisi';
        
        $this->assertEquals('Oda Sayisi', $ozellik->formatted_name);
    }

    /** @test */
    public function it_validates_value_correctly()
    {
        $ozellik = new MulkOzellik();
        
        // Number validation
        $ozellik->ozellik_tipi = 'sayi';
        $this->assertTrue($ozellik->validateValue(123));
        $this->assertTrue($ozellik->validateValue('123.45'));
        $this->assertFalse($ozellik->validateValue('not a number'));
        
        // Boolean validation
        $ozellik->ozellik_tipi = 'boolean';
        $this->assertTrue($ozellik->validateValue(true));
        $this->assertTrue($ozellik->validateValue(false));
        $this->assertTrue($ozellik->validateValue(1));
        $this->assertTrue($ozellik->validateValue('true'));
        
        // Text validation
        $ozellik->ozellik_tipi = 'metin';
        $this->assertTrue($ozellik->validateValue('test string'));
        $this->assertTrue($ozellik->validateValue(123));
        
        // List validation
        $ozellik->ozellik_tipi = 'liste';
        $this->assertTrue($ozellik->validateValue(['item1', 'item2']));
        $this->assertTrue($ozellik->validateValue('single item'));
    }

    /** @test */
    public function it_normalizes_value_correctly()
    {
        $ozellik = new MulkOzellik();
        
        // Number normalization
        $ozellik->ozellik_tipi = 'sayi';
        $this->assertEquals(123.0, $ozellik->normalizeValue('123'));
        $this->assertEquals(45.67, $ozellik->normalizeValue('45.67'));
        
        // Boolean normalization
        $ozellik->ozellik_tipi = 'boolean';
        $this->assertTrue($ozellik->normalizeValue('1'));
        $this->assertFalse($ozellik->normalizeValue('0'));
        $this->assertTrue($ozellik->normalizeValue(true));
        
        // Text normalization
        $ozellik->ozellik_tipi = 'metin';
        $this->assertEquals('test', $ozellik->normalizeValue('test'));
        $this->assertEquals('123', $ozellik->normalizeValue(123));
        
        // List normalization
        $ozellik->ozellik_tipi = 'liste';
        $this->assertEquals(['item1', 'item2'], $ozellik->normalizeValue(['item1', 'item2']));
        $this->assertEquals(['single'], $ozellik->normalizeValue('single'));
    }

    /** @test */
    public function it_returns_validation_rules()
    {
        $rules = MulkOzellik::getValidationRules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('mulk_id', $rules);
        $this->assertArrayHasKey('ozellik_adi', $rules);
        $this->assertArrayHasKey('ozellik_degeri', $rules);
        $this->assertArrayHasKey('ozellik_tipi', $rules);
    }

    /** @test */
    public function it_can_scope_active()
    {
        $ozellik = new MulkOzellik();
        $query = $ozellik->newQuery();
        $query = $ozellik->scopeActive($query);
        
        $sql = $query->toSql();
        $this->assertStringContainsString('aktif_mi', $sql);
    }

    /** @test */
    public function it_can_scope_by_mulk_type()
    {
        $ozellik = new MulkOzellik();
        $query = $ozellik->newQuery();
        $query = $ozellik->scopeByMulkType($query, 'test_type');
        
        $sql = $query->toSql();
        $this->assertStringContainsString('mulk_type', $sql);
    }

    /** @test */
    public function it_can_scope_by_name()
    {
        $ozellik = new MulkOzellik();
        $query = $ozellik->newQuery();
        $query = $ozellik->scopeByName($query, 'test_name');
        
        $sql = $query->toSql();
        $this->assertStringContainsString('ozellik_adi', $sql);
    }

    /** @test */
    public function it_can_scope_by_type()
    {
        $ozellik = new MulkOzellik();
        $query = $ozellik->newQuery();
        $query = $ozellik->scopeByType($query, 'sayi');
        
        $sql = $query->toSql();
        $this->assertStringContainsString('ozellik_tipi', $sql);
    }
}