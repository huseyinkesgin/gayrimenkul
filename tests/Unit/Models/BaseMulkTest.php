<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Mulk\BaseMulk;
use App\Models\MulkOzellik;
use App\Models\User;
use App\Enums\MulkKategorisi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BaseMulkTest extends TestCase
{
    use RefreshDatabase;

    protected $testMulk;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Test için concrete model oluştur
        $this->testMulk = new class extends BaseMulk {
            protected $mulkType = 'test_mulk';
            
            public function getMulkType(): string
            {
                return 'test_mulk';
            }
            
            public function getMulkKategorisi(): MulkKategorisi
            {
                return MulkKategorisi::KONUT;
            }
            
            public function getValidProperties(): array
            {
                return ['oda_sayisi', 'banyo_sayisi', 'asansor_var_mi'];
            }
            
            public function getSpecificValidationRules(): array
            {
                return [
                    'oda_sayisi' => 'integer|min:1|max:10',
                    'banyo_sayisi' => 'integer|min:1|max:5',
                ];
            }
        };
    }

    /** @test */
    public function it_has_correct_fillable_fields()
    {
        $fillable = [
            'baslik', 'aciklama', 'fiyat', 'para_birimi', 'metrekare',
            'durum', 'yayinlanma_tarihi', 'aktif_mi', 'siralama'
        ];
        
        $this->assertEquals($fillable, $this->testMulk->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $expectedCasts = [
            'fiyat' => 'decimal:2',
            'metrekare' => 'decimal:2',
            'yayinlanma_tarihi' => 'datetime',
            'aktif_mi' => 'boolean',
            'siralama' => 'integer',
        ];
        
        foreach ($expectedCasts as $field => $cast) {
            $this->assertEquals($cast, $this->testMulk->getCasts()[$field]);
        }
    }

    /** @test */
    public function it_returns_correct_mulk_type()
    {
        $this->assertEquals('test_mulk', $this->testMulk->getMulkType());
    }

    /** @test */
    public function it_returns_correct_mulk_kategorisi()
    {
        $this->assertEquals(MulkKategorisi::KONUT, $this->testMulk->getMulkKategorisi());
    }

    /** @test */
    public function it_returns_valid_properties()
    {
        $validProperties = $this->testMulk->getValidProperties();
        
        $this->assertIsArray($validProperties);
        $this->assertContains('oda_sayisi', $validProperties);
        $this->assertContains('banyo_sayisi', $validProperties);
        $this->assertContains('asansor_var_mi', $validProperties);
    }

    /** @test */
    public function it_returns_base_validation_rules()
    {
        $rules = BaseMulk::getBaseValidationRules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('baslik', $rules);
        $this->assertArrayHasKey('fiyat', $rules);
        $this->assertArrayHasKey('metrekare', $rules);
        $this->assertArrayHasKey('durum', $rules);
    }

    /** @test */
    public function it_merges_validation_rules()
    {
        $allRules = $this->testMulk->getValidationRules();
        
        $this->assertIsArray($allRules);
        $this->assertArrayHasKey('baslik', $allRules); // Base rule
        $this->assertArrayHasKey('oda_sayisi', $allRules); // Specific rule
    }

    /** @test */
    public function it_formats_price_correctly()
    {
        $mulk = new $this->testMulk();
        $mulk->fiyat = 500000;
        $mulk->para_birimi = 'TRY';
        
        $this->assertEquals('500.000 ₺', $mulk->formatted_price);
        
        $mulk->para_birimi = 'USD';
        $this->assertEquals('500.000 $', $mulk->formatted_price);
        
        $mulk->fiyat = null;
        $this->assertEquals('Belirtilmemiş', $mulk->formatted_price);
    }

    /** @test */
    public function it_formats_area_correctly()
    {
        $mulk = new $this->testMulk();
        $mulk->metrekare = 120.5;
        
        $this->assertEquals('121 m²', $mulk->formatted_area);
        
        $mulk->metrekare = null;
        $this->assertEquals('Belirtilmemiş', $mulk->formatted_area);
    }

    /** @test */
    public function it_calculates_price_per_square_meter()
    {
        $mulk = new $this->testMulk();
        $mulk->fiyat = 600000;
        $mulk->metrekare = 120;
        
        $this->assertEquals(5000.0, $mulk->price_per_square_meter);
        
        $mulk->metrekare = 0;
        $this->assertNull($mulk->price_per_square_meter);
        
        $mulk->fiyat = null;
        $this->assertNull($mulk->price_per_square_meter);
    }

    /** @test */
    public function it_returns_correct_status_color()
    {
        $mulk = new $this->testMulk();
        
        $mulk->durum = 'aktif';
        $this->assertEquals('green', $mulk->status_color);
        
        $mulk->durum = 'pasif';
        $this->assertEquals('gray', $mulk->status_color);
        
        $mulk->durum = 'satildi';
        $this->assertEquals('red', $mulk->status_color);
        
        $mulk->durum = 'kiralandi';
        $this->assertEquals('blue', $mulk->status_color);
    }

    /** @test */
    public function it_returns_correct_status_label()
    {
        $mulk = new $this->testMulk();
        
        $mulk->durum = 'aktif';
        $this->assertEquals('Aktif', $mulk->status_label);
        
        $mulk->durum = 'satildi';
        $this->assertEquals('Satıldı', $mulk->status_label);
    }

    /** @test */
    public function it_generates_unique_slug()
    {
        $mulk = new $this->testMulk();
        $mulk->baslik = 'Test Mülk Başlığı';
        $mulk->id = 'test-uuid';
        
        $slug = $mulk->slug;
        
        $this->assertIsString($slug);
        $this->assertStringContainsString('test-mulk-basligi', $slug);
    }

    /** @test */
    public function it_returns_display_name()
    {
        $mulk = new $this->testMulk();
        $mulk->baslik = 'Test Mülk';
        
        $this->assertEquals('Test Mülk', $mulk->getDisplayName());
    }

    /** @test */
    public function it_can_scope_aktif_mulkler()
    {
        $query = $this->testMulk->newQuery();
        $query = $this->testMulk->scopeAktifMulkler($query);
        
        $sql = $query->toSql();
        $this->assertStringContainsString('durum', $sql);
        $this->assertStringContainsString('aktif_mi', $sql);
    }

    /** @test */
    public function it_can_scope_by_price_range()
    {
        $query = $this->testMulk->newQuery();
        $query = $this->testMulk->scopeFiyatAraliginda($query, 100000, 500000);
        
        $sql = $query->toSql();
        $this->assertStringContainsString('fiyat', $sql);
        $this->assertStringContainsString('>=', $sql);
        $this->assertStringContainsString('<=', $sql);
    }

    /** @test */
    public function it_can_scope_by_area_range()
    {
        $query = $this->testMulk->newQuery();
        $query = $this->testMulk->scopeMetrekareAraliginda($query, 50, 150);
        
        $sql = $query->toSql();
        $this->assertStringContainsString('metrekare', $sql);
        $this->assertStringContainsString('>=', $sql);
        $this->assertStringContainsString('<=', $sql);
    }

    /** @test */
    public function it_can_scope_by_type()
    {
        $query = $this->testMulk->newQuery();
        $query = $this->testMulk->scopeByType($query, 'test_type');
        
        $sql = $query->toSql();
        $this->assertStringContainsString('mulk_type', $sql);
    }

    /** @test */
    public function it_can_scope_yayinlanan()
    {
        $query = $this->testMulk->newQuery();
        $query = $this->testMulk->scopeYayinlanan($query);
        
        $sql = $query->toSql();
        $this->assertStringContainsString('yayinlanma_tarihi', $sql);
        $this->assertStringContainsString('is not null', $sql);
    }
}