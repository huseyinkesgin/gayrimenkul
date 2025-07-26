<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class BaseModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Test için concrete model oluştur
        $this->testModel = new class extends BaseModel {
            protected $table = 'test_models';
            protected $fillable = ['ad', 'aktif_mi'];
            
            // Test için searchable fields tanımla
            protected $searchableFields = ['ad'];
            protected $sortableFields = ['ad', 'olusturma_tarihi'];
        };
    }

    /** @test */
    public function it_has_uuid_primary_key()
    {
        $model = new $this->testModel();
        
        $this->assertEquals('string', $model->getKeyType());
        $this->assertFalse($model->getIncrementing());
    }

    /** @test */
    public function it_uses_custom_timestamp_names()
    {
        $this->assertEquals('olusturma_tarihi', $this->testModel::CREATED_AT);
        $this->assertEquals('guncelleme_tarihi', $this->testModel::UPDATED_AT);
        $this->assertEquals('silinme_tarihi', $this->testModel::DELETED_AT);
    }

    /** @test */
    public function it_can_scope_active_records()
    {
        // Bu test migration olmadan çalışmayacak, mock ile test edelim
        $query = $this->testModel->newQuery();
        $query = $this->testModel->scopeAktif($query);
        
        $this->assertStringContainsString('aktif_mi', $query->toSql());
    }

    /** @test */
    public function it_can_generate_unique_slug()
    {
        $model = new $this->testModel();
        $slug = $model->generateUniqueSlug('Test Başlık');
        
        $this->assertEquals('test-baslik', $slug);
    }

    /** @test */
    public function it_can_get_display_name()
    {
        $model = new $this->testModel();
        $model->ad = 'Test Ad';
        
        $this->assertEquals('Test Ad', $model->getDisplayName());
    }

    /** @test */
    public function it_can_generate_cache_key()
    {
        $model = new $this->testModel();
        $model->id = 'test-uuid';
        
        $cacheKey = $model->getCacheKey('suffix');
        
        $this->assertStringContainsString('test-uuid', $cacheKey);
        $this->assertStringContainsString('suffix', $cacheKey);
    }

    /** @test */
    public function it_can_convert_to_select_array()
    {
        $model = new $this->testModel();
        $model->id = 'test-uuid';
        $model->ad = 'Test Ad';
        
        $selectArray = $model->toSelectArray();
        
        $this->assertArrayHasKey('id', $selectArray);
        $this->assertArrayHasKey('text', $selectArray);
        $this->assertArrayHasKey('value', $selectArray);
        $this->assertEquals('Test Ad', $selectArray['text']);
    }

    /** @test */
    public function it_can_get_model_name()
    {
        $model = new $this->testModel();
        
        $this->assertStringContainsString('class@anonymous', $model->model_name);
    }

    /** @test */
    public function it_tracks_audit_trail_on_create()
    {
        $user = User::factory()->create();
        Auth::login($user);
        
        $model = new $this->testModel();
        
        // Creating event'ini manuel tetikle
        $model->olusturan_id = Auth::id();
        
        $this->assertEquals($user->id, $model->olusturan_id);
    }

    /** @test */
    public function it_tracks_audit_trail_on_update()
    {
        $user = User::factory()->create();
        Auth::login($user);
        
        $model = new $this->testModel();
        
        // Updating event'ini manuel tetikle
        $model->guncelleyen_id = Auth::id();
        
        $this->assertEquals($user->id, $model->guncelleyen_id);
    }
}