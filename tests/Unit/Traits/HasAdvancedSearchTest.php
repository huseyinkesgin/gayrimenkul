<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Traits\HasAdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HasAdvancedSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Test için model oluştur
        $this->testModel = new class extends Model {
            use HasAdvancedSearch;
            
            protected $table = 'test_models';
            protected $fillable = ['ad', 'aciklama', 'fiyat', 'aktif_mi'];
            
            protected $searchableFields = ['ad', 'aciklama'];
            protected $sortableFields = ['ad', 'fiyat', 'created_at'];
            protected $defaultSortField = 'created_at';
            protected $defaultSortDirection = 'desc';
        };
    }

    /** @test */
    public function it_can_perform_text_search()
    {
        $query = $this->testModel->newQuery();
        $query = $this->testModel->scopeTextSearch($query, 'test', ['ad']);
        
        $sql = $query->toSql();
        $this->assertStringContainsString('ad', $sql);
        $this->assertStringContainsString('like', $sql);
    }

    /** @test */
    public function it_can_filter_by_date_range()
    {
        $query = $this->testModel->newQuery();
        $query = $this->testModel->scopeDateRange($query, 'created_at', '2024-01-01', '2024-12-31');
        
        $sql = $query->toSql();
        $this->assertStringContainsString('created_at', $sql);
        $this->assertStringContainsString('>=', $sql);
        $this->assertStringContainsString('<=', $sql);
    }

    /** @test */
    public function it_can_filter_by_numeric_range()
    {
        $query = $this->testModel->newQuery();
        $query = $this->testModel->scopeNumericRange($query, 'fiyat', 1000, 5000);
        
        $sql = $query->toSql();
        $this->assertStringContainsString('fiyat', $sql);
        $this->assertStringContainsString('>=', $sql);
        $this->assertStringContainsString('<=', $sql);
    }

    /** @test */
    public function it_can_apply_dynamic_sort()
    {
        $query = $this->testModel->newQuery();
        $query = $this->testModel->scopeDynamicSort($query, 'ad', 'asc');
        
        $sql = $query->toSql();
        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('ad', $sql);
        $this->assertStringContainsString('asc', $sql);
    }

    /** @test */
    public function it_uses_default_sort_when_invalid_field_provided()
    {
        $query = $this->testModel->newQuery();
        $query = $this->testModel->scopeDynamicSort($query, 'invalid_field', 'asc');
        
        $sql = $query->toSql();
        $this->assertStringContainsString('created_at', $sql);
    }

    /** @test */
    public function it_can_perform_advanced_search_with_multiple_filters()
    {
        $filters = [
            'ad' => 'test',
            'aktif_mi' => true,
            'fiyat' => [1000, 2000, 3000]
        ];
        
        $query = $this->testModel->newQuery();
        $query = $this->testModel->scopeAdvancedSearch($query, $filters);
        
        $sql = $query->toSql();
        $this->assertStringContainsString('ad', $sql);
        $this->assertStringContainsString('aktif_mi', $sql);
        $this->assertStringContainsString('fiyat', $sql);
    }

    /** @test */
    public function it_returns_correct_searchable_fields()
    {
        $model = new $this->testModel();
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('getSearchableFields');
        $method->setAccessible(true);
        
        $fields = $method->invoke($model);
        
        $this->assertEquals(['ad', 'aciklama'], $fields);
    }

    /** @test */
    public function it_returns_correct_sortable_fields()
    {
        $model = new $this->testModel();
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('getSortableFields');
        $method->setAccessible(true);
        
        $fields = $method->invoke($model);
        
        $this->assertEquals(['ad', 'fiyat', 'created_at'], $fields);
    }

    /** @test */
    public function it_returns_correct_default_sort_field()
    {
        $model = new $this->testModel();
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('getDefaultSortField');
        $method->setAccessible(true);
        
        $field = $method->invoke($model);
        
        $this->assertEquals('created_at', $field);
    }

    /** @test */
    public function it_returns_correct_default_sort_direction()
    {
        $model = new $this->testModel();
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('getDefaultSortDirection');
        $method->setAccessible(true);
        
        $direction = $method->invoke($model);
        
        $this->assertEquals('desc', $direction);
    }

    /** @test */
    public function it_can_filter_by_location()
    {
        $query = $this->testModel->newQuery();
        $locationFilters = [
            'sehir_id' => 'test-sehir-id',
            'ilce_id' => 'test-ilce-id',
            'adres_detay' => 'Bağdat Caddesi'
        ];
        
        $query = $this->testModel->scopeByLocation($query, $locationFilters);
        
        $sql = $query->toSql();
        $this->assertStringContainsString('exists', $sql);
        $this->assertStringContainsString('adresler', $sql);
    }
}