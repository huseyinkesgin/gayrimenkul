<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Dokuman;
use App\Models\User;
use App\Enums\DokumanTipi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DokumanTest extends TestCase
{
    use RefreshDatabase;

    public function test_dokuman_can_be_created()
    {
        $user = User::factory()->create();
        
        $dokuman = Dokuman::factory()->create([
            'olusturan_id' => $user->id,
        ]);

        $this->assertDatabaseHas('dokumanlar', [
            'id' => $dokuman->id,
            'olusturan_id' => $user->id,
        ]);
    }

    public function test_dokuman_has_polymorphic_relationship()
    {
        $user = User::factory()->create();
        $dokuman = Dokuman::factory()->create([
            'documentable_id' => $user->id,
            'documentable_type' => User::class,
        ]);

        $this->assertInstanceOf(User::class, $dokuman->documentable);
        $this->assertEquals($user->id, $dokuman->documentable->id);
    }

    public function test_dokuman_can_have_versions()
    {
        $originalDokuman = Dokuman::factory()->create(['versiyon' => 1]);
        
        $newVersion = $originalDokuman->createNewVersion([
            'url' => 'new-path/file.pdf',
            'dosya_adi' => 'new-file.pdf',
            'dosya_boyutu' => 2048,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertEquals(2, $newVersion->versiyon);
        $this->assertEquals($originalDokuman->id, $newVersion->ana_dokuman_id);
        $this->assertEquals($originalDokuman->documentable_id, $newVersion->documentable_id);
    }

    public function test_dokuman_access_control()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        
        // Gizli döküman
        $gizliDokuman = Dokuman::factory()->gizli()->create([
            'olusturan_id' => $owner->id,
        ]);

        $this->assertTrue($gizliDokuman->hasAccess($owner->id));
        $this->assertFalse($gizliDokuman->hasAccess($otherUser->id));

        // Herkese açık döküman
        $acikDokuman = Dokuman::factory()->create([
            'gizli_mi' => false,
        ]);

        $this->assertTrue($acikDokuman->hasAccess($owner->id));
        $this->assertTrue($acikDokuman->hasAccess($otherUser->id));
    }

    public function test_dokuman_duplicate_check()
    {
        $hash = 'test-hash-123';
        $documentableType = User::class;
        $documentableId = User::factory()->create()->id;

        Dokuman::factory()->create([
            'dosya_hash' => $hash,
            'documentable_type' => $documentableType,
            'documentable_id' => $documentableId,
        ]);

        $this->assertTrue(
            Dokuman::isDuplicate($hash, $documentableType, $documentableId)
        );

        $this->assertFalse(
            Dokuman::isDuplicate('different-hash', $documentableType, $documentableId)
        );
    }

    public function test_dokuman_scopes()
    {
        // Aktif dökümanlar
        Dokuman::factory()->count(3)->create(['aktif_mi' => true]);
        Dokuman::factory()->count(2)->create(['aktif_mi' => false]);

        $this->assertEquals(3, Dokuman::active()->count());

        // Veritabanını temizle
        Dokuman::query()->delete();

        // Döküman tipine göre
        Dokuman::factory()->tapu()->count(2)->create();
        Dokuman::factory()->autocad()->count(3)->create();

        $this->assertEquals(2, Dokuman::byType(DokumanTipi::TAPU->value)->count());
        $this->assertEquals(3, Dokuman::byType(DokumanTipi::AUTOCAD->value)->count());

        // Gizli dökümanlar
        Dokuman::factory()->gizli()->count(2)->create();
        Dokuman::factory()->count(3)->create(['gizli_mi' => false]);

        $this->assertEquals(2, Dokuman::gizli()->count());
        $this->assertEquals(3, Dokuman::acik()->count());
    }

    public function test_dokuman_formatted_size_attribute()
    {
        $dokuman = Dokuman::factory()->create(['dosya_boyutu' => 1024]);
        $this->assertEquals('1.00 KB', $dokuman->formatted_size);

        $dokuman = Dokuman::factory()->create(['dosya_boyutu' => 1048576]);
        $this->assertEquals('1.00 MB', $dokuman->formatted_size);

        $dokuman = Dokuman::factory()->create(['dosya_boyutu' => 1073741824]);
        $this->assertEquals('1.00 GB', $dokuman->formatted_size);
    }

    public function test_dokuman_file_extension_attribute()
    {
        $dokuman = Dokuman::factory()->create(['dosya_adi' => 'test-file.pdf']);
        $this->assertEquals('pdf', $dokuman->file_extension);

        $dokuman = Dokuman::factory()->create(['dosya_adi' => 'image.jpg']);
        $this->assertEquals('jpg', $dokuman->file_extension);
    }

    public function test_dokuman_is_viewable_attribute()
    {
        $pdfDokuman = Dokuman::factory()->create(['mime_type' => 'application/pdf']);
        $this->assertTrue($pdfDokuman->is_viewable);

        $imageDokuman = Dokuman::factory()->create(['mime_type' => 'image/jpeg']);
        $this->assertTrue($imageDokuman->is_viewable);

        $wordDokuman = Dokuman::factory()->create(['mime_type' => 'application/msword']);
        $this->assertFalse($wordDokuman->is_viewable);
    }

    public function test_dokuman_increment_access()
    {
        $dokuman = Dokuman::factory()->create(['erisim_sayisi' => 5]);
        
        $dokuman->incrementAccess();
        
        $this->assertEquals(6, $dokuman->fresh()->erisim_sayisi);
        $this->assertNotNull($dokuman->fresh()->son_erisim_tarihi);
    }

    public function test_dokuman_validation_rules()
    {
        $dokumanTipi = DokumanTipi::TAPU;
        
        // MIME type kontrolü
        $this->assertContains('application/pdf', $dokumanTipi->allowedMimeTypes());
        $this->assertNotContains('application/msword', $dokumanTipi->allowedMimeTypes());
        
        // Dosya boyutu kontrolü
        $this->assertEquals(10, $dokumanTipi->maxFileSize()); // 10MB for TAPU
        
        // AutoCAD için farklı boyut
        $this->assertEquals(50, DokumanTipi::AUTOCAD->maxFileSize()); // 50MB for AutoCAD
    }

    public function test_dokuman_mulk_type_compatibility()
    {
        $arsaTypes = DokumanTipi::forMulkType('arsa');
        $this->assertContains(DokumanTipi::TAPU, $arsaTypes);
        $this->assertContains(DokumanTipi::IMAR_PLANI, $arsaTypes);
        $this->assertNotContains(DokumanTipi::AUTOCAD, $arsaTypes);

        $isyeriTypes = DokumanTipi::forMulkType('isyeri');
        $this->assertContains(DokumanTipi::TAPU, $isyeriTypes);
        $this->assertContains(DokumanTipi::AUTOCAD, $isyeriTypes);
        $this->assertContains(DokumanTipi::YANGIN_RAPORU, $isyeriTypes);
    }

    public function test_dokuman_search_scope()
    {
        Dokuman::factory()->create([
            'baslik' => 'Test Tapu Belgesi',
            'aciklama' => 'Bu bir test açıklamasıdır',
        ]);

        Dokuman::factory()->create([
            'baslik' => 'AutoCAD Çizimi',
            'aciklama' => 'Teknik çizim dosyası',
        ]);

        // Not: Full-text search gerçek veritabanında test edilmelidir
        // SQLite'da MATCH AGAINST desteklenmez
        if (config('database.default') !== 'sqlite') {
            $results = Dokuman::search('tapu')->get();
            $this->assertGreaterThan(0, $results->count());
        }
    }
}