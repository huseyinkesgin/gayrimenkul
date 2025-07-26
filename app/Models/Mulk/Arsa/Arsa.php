<?php

namespace App\Models\Mulk\Arsa;

use App\Models\Mulk\BaseMulk;
use App\Enums\MulkKategorisi;

abstract class Arsa extends BaseMulk
{
    /**
     * Mülk kategorisini döndür
     */
    public function getMulkKategorisi(): MulkKategorisi
    {
        return MulkKategorisi::ARSA;
    }

    /**
     * Arsa için ortak geçerli özellikler
     */
    public function getValidProperties(): array
    {
        return [
            'imar_durumu',
            'kaks',
            'gabari',
            'ada_no',
            'parsel_no',
            'tapu_durumu',
            'cephe_uzunlugu',
            'derinlik',
            'kot_farki',
            'yol_genisligi',
            'elektrik_var_mi',
            'su_var_mi',
            'dogalgaz_var_mi',
            'kanalizasyon_var_mi',
            'telefon_var_mi',
            'internet_var_mi',
        ];
    }

    /**
     * Arsa için ortak validation kuralları
     */
    public function getSpecificValidationRules(): array
    {
        return [
            'imar_durumu' => 'nullable|string|max:100',
            'kaks' => 'nullable|numeric|min:0|max:10',
            'gabari' => 'nullable|numeric|min:0|max:100',
            'ada_no' => 'nullable|string|max:50',
            'parsel_no' => 'nullable|string|max:50',
            'tapu_durumu' => 'nullable|string|max:100',
            'cephe_uzunlugu' => 'nullable|numeric|min:0',
            'derinlik' => 'nullable|numeric|min:0',
            'kot_farki' => 'nullable|numeric',
            'yol_genisligi' => 'nullable|numeric|min:0|max:100',
            'elektrik_var_mi' => 'nullable|boolean',
            'su_var_mi' => 'nullable|boolean',
            'dogalgaz_var_mi' => 'nullable|boolean',
            'kanalizasyon_var_mi' => 'nullable|boolean',
            'telefon_var_mi' => 'nullable|boolean',
            'internet_var_mi' => 'nullable|boolean',
        ];
    }

    /**
     * İmar durumu etiketi
     */
    public function getImarDurumuLabelAttribute(): string
    {
        $imarDurumu = $this->getProperty('imar_durumu');
        
        return match ($imarDurumu) {
            'imarlı' => 'İmarlı',
            'imarli_konut' => 'İmarlı Konut',
            'imarli_ticari' => 'İmarlı Ticari',
            'imarli_sanayi' => 'İmarlı Sanayi',
            'imarsiz' => 'İmarsız',
            'tarla' => 'Tarla',
            'bahce' => 'Bahçe',
            default => $imarDurumu ?: 'Belirtilmemiş'
        };
    }

    /**
     * KAKS değeri formatlanmış
     */
    public function getFormattedKaksAttribute(): string
    {
        $kaks = $this->getProperty('kaks');
        return $kaks ? number_format($kaks, 2) : 'Belirtilmemiş';
    }

    /**
     * Gabari değeri formatlanmış
     */
    public function getFormattedGabariAttribute(): string
    {
        $gabari = $this->getProperty('gabari');
        return $gabari ? $gabari . ' m' : 'Belirtilmemiş';
    }

    /**
     * Ada parsel bilgisi
     */
    public function getAdaParselAttribute(): string
    {
        $ada = $this->getProperty('ada_no');
        $parsel = $this->getProperty('parsel_no');
        
        if ($ada && $parsel) {
            return "Ada: {$ada}, Parsel: {$parsel}";
        }
        
        return 'Belirtilmemiş';
    }

    /**
     * Altyapı durumu
     */
    public function getAltyapiDurumuAttribute(): array
    {
        return [
            'elektrik' => $this->getProperty('elektrik_var_mi', false),
            'su' => $this->getProperty('su_var_mi', false),
            'dogalgaz' => $this->getProperty('dogalgaz_var_mi', false),
            'kanalizasyon' => $this->getProperty('kanalizasyon_var_mi', false),
            'telefon' => $this->getProperty('telefon_var_mi', false),
            'internet' => $this->getProperty('internet_var_mi', false),
        ];
    }

    /**
     * Altyapı tamamlanma yüzdesi
     */
    public function getAltyapiTamamlanmaYuzdesiAttribute(): int
    {
        $altyapi = $this->altyapi_durumu;
        $toplam = count($altyapi);
        $tamamlanan = count(array_filter($altyapi));
        
        return $toplam > 0 ? round(($tamamlanan / $toplam) * 100) : 0;
    }

    /**
     * İnşaat alanı hesapla (KAKS * Arsa Alanı)
     */
    public function getInsaatAlaniAttribute(): ?float
    {
        $kaks = $this->getProperty('kaks');
        $arsaAlani = $this->metrekare;
        
        if ($kaks && $arsaAlani) {
            return round($kaks * $arsaAlani, 2);
        }
        
        return null;
    }

    /**
     * İnşaat alanı formatlanmış
     */
    public function getFormattedInsaatAlaniAttribute(): string
    {
        $insaatAlani = $this->insaat_alani;
        return $insaatAlani ? number_format($insaatAlani, 0, ',', '.') . ' m²' : 'Hesaplanamadı';
    }
}