<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Enums\TalepDurumu;

class TalepDurumuEnumTest extends TestCase
{
    public function test_talep_durumu_labels_dogru_doner()
    {
        $this->assertEquals('Aktif', TalepDurumu::AKTIF->label());
        $this->assertEquals('Beklemede', TalepDurumu::BEKLEMEDE->label());
        $this->assertEquals('Eşleşti', TalepDurumu::ESLESTI->label());
        $this->assertEquals('Tamamlandı', TalepDurumu::TAMAMLANDI->label());
        $this->assertEquals('İptal Edildi', TalepDurumu::IPTAL_EDILDI->label());
        $this->assertEquals('Arşivlendi', TalepDurumu::ARŞIVLENDI->label());
    }

    public function test_talep_durumu_colors_dogru_doner()
    {
        $this->assertEquals('green', TalepDurumu::AKTIF->color());
        $this->assertEquals('yellow', TalepDurumu::BEKLEMEDE->color());
        $this->assertEquals('blue', TalepDurumu::ESLESTI->color());
        $this->assertEquals('purple', TalepDurumu::TAMAMLANDI->color());
        $this->assertEquals('red', TalepDurumu::IPTAL_EDILDI->color());
        $this->assertEquals('gray', TalepDurumu::ARŞIVLENDI->color());
    }

    public function test_aktif_durumlar_dogru_doner()
    {
        $aktifDurumlar = TalepDurumu::aktifDurumlar();
        
        $this->assertContains(TalepDurumu::AKTIF, $aktifDurumlar);
        $this->assertContains(TalepDurumu::BEKLEMEDE, $aktifDurumlar);
        $this->assertContains(TalepDurumu::ESLESTI, $aktifDurumlar);
        $this->assertNotContains(TalepDurumu::TAMAMLANDI, $aktifDurumlar);
        $this->assertNotContains(TalepDurumu::IPTAL_EDILDI, $aktifDurumlar);
    }

    public function test_pasif_durumlar_dogru_doner()
    {
        $pasifDurumlar = TalepDurumu::pasifDurumlar();
        
        $this->assertContains(TalepDurumu::TAMAMLANDI, $pasifDurumlar);
        $this->assertContains(TalepDurumu::IPTAL_EDILDI, $pasifDurumlar);
        $this->assertContains(TalepDurumu::ARŞIVLENDI, $pasifDurumlar);
        $this->assertNotContains(TalepDurumu::AKTIF, $pasifDurumlar);
        $this->assertNotContains(TalepDurumu::BEKLEMEDE, $pasifDurumlar);
    }

    public function test_is_aktif_dogru_calisir()
    {
        $this->assertTrue(TalepDurumu::AKTIF->isAktif());
        $this->assertTrue(TalepDurumu::BEKLEMEDE->isAktif());
        $this->assertTrue(TalepDurumu::ESLESTI->isAktif());
        $this->assertFalse(TalepDurumu::TAMAMLANDI->isAktif());
        $this->assertFalse(TalepDurumu::IPTAL_EDILDI->isAktif());
    }

    public function test_is_pasif_dogru_calisir()
    {
        $this->assertFalse(TalepDurumu::AKTIF->isPasif());
        $this->assertFalse(TalepDurumu::BEKLEMEDE->isPasif());
        $this->assertFalse(TalepDurumu::ESLESTI->isPasif());
        $this->assertTrue(TalepDurumu::TAMAMLANDI->isPasif());
        $this->assertTrue(TalepDurumu::IPTAL_EDILDI->isPasif());
        $this->assertTrue(TalepDurumu::ARŞIVLENDI->isPasif());
    }
}