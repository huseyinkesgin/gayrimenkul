<?php

namespace App\Models\Mulk\Isyeri;

use App\Models\Mulk\BaseMulk;
use App\Enums\MulkKategorisi;

abstract class Isyeri extends BaseMulk
{
    /**
     * Mülk kategorisini döndür
     */
    public function getMulkKategorisi(): MulkKategorisi
    {
        return MulkKategorisi::ISYERI;
    }

    /**
     * İşyeri için ortak geçerli özellikler
     */
    public function getValidProperties(): array
    {
        return [
            'kapali_alan',
            'acik_alan',
            'ofis_alani',
            'kat_no',
            'kat_sayisi',
            'tavan_yuksekligi',
            'asansor_var_mi',
            'yuk_asansoru_var_mi',
            'otopark_kapasitesi',
            'elektrik_guc_kapasitesi',
            'isitma_sistemi',
            'sogutma_sistemi',
            'havalandirma_sistemi',
            'yangin_sistemi',
            'guvenlik_sistemi',
            'internet_altyapisi',
            'telefon_santrali',
            'jenerator_var_mi',
            'ups_sistemi_var_mi',
            'ruhsat_durumu',
            'faaliyet_ruhsati',
            'cevre_izni',
            'yapı_kullanim_izni',
        ];
    }

    /**
     * İşyeri için ortak validation kuralları
     */
    public function getSpecificValidationRules(): array
    {
        return [
            'kapali_alan' => 'nullable|numeric|min:0',
            'acik_alan' => 'nullable|numeric|min:0',
            'ofis_alani' => 'nullable|numeric|min:0',
            'kat_no' => 'nullable|integer|min:-5|max:100',
            'kat_sayisi' => 'nullable|integer|min:1|max:100',
            'tavan_yuksekligi' => 'nullable|numeric|min:2|max:50',
            'asansor_var_mi' => 'nullable|boolean',
            'yuk_asansoru_var_mi' => 'nullable|boolean',
            'otopark_kapasitesi' => 'nullable|integer|min:0|max:1000',
            'elektrik_guc_kapasitesi' => 'nullable|numeric|min:0',
            'isitma_sistemi' => 'nullable|string|max:100',
            'sogutma_sistemi' => 'nullable|string|max:100',
            'havalandirma_sistemi' => 'nullable|string|max:100',
            'yangin_sistemi' => 'nullable|string|max:100',
            'guvenlik_sistemi' => 'nullable|string|max:100',
            'internet_altyapisi' => 'nullable|string|max:100',
            'telefon_santrali' => 'nullable|boolean',
            'jenerator_var_mi' => 'nullable|boolean',
            'ups_sistemi_var_mi' => 'nullable|boolean',
            'ruhsat_durumu' => 'nullable|string|max:200',
            'faaliyet_ruhsati' => 'nullable|string|max:200',
            'cevre_izni' => 'nullable|string|max:200',
            'yapı_kullanim_izni' => 'nullable|string|max:200',
        ];
    }

    /**
     * Toplam alan hesapla
     */
    public function getToplamAlanAttribute(): float
    {
        $kapaliAlan = $this->getProperty('kapali_alan', 0);
        $acikAlan = $this->getProperty('acik_alan', 0);
        
        return $kapaliAlan + $acikAlan;
    }

    /**
     * Toplam alan formatlanmış
     */
    public function getFormattedToplamAlanAttribute(): string
    {
        return number_format($this->toplam_alan, 0, ',', '.') . ' m²';
    }

    /**
     * Kapalı alan oranı
     */
    public function getKapaliAlanOraniAttribute(): float
    {
        $toplamAlan = $this->toplam_alan;
        $kapaliAlan = $this->getProperty('kapali_alan', 0);
        
        if ($toplamAlan <= 0) {
            return 0;
        }
        
        return round(($kapaliAlan / $toplamAlan) * 100, 1);
    }

    /**
     * Teknik altyapı durumu
     */
    public function getTeknikAltyapiDurumuAttribute(): array
    {
        return [
            'elektrik_guc' => $this->getProperty('elektrik_guc_kapasitesi'),
            'isitma' => $this->getProperty('isitma_sistemi'),
            'sogutma' => $this->getProperty('sogutma_sistemi'),
            'havalandirma' => $this->getProperty('havalandirma_sistemi'),
            'yangin' => $this->getProperty('yangin_sistemi'),
            'guvenlik' => $this->getProperty('guvenlik_sistemi'),
            'internet' => $this->getProperty('internet_altyapisi'),
            'jenerator' => $this->getProperty('jenerator_var_mi', false),
            'ups' => $this->getProperty('ups_sistemi_var_mi', false),
        ];
    }

    /**
     * Teknik altyapı tamamlanma yüzdesi
     */
    public function getTeknikAltyapiTamamlanmaYuzdesiAttribute(): int
    {
        $altyapi = $this->teknik_altyapi_durumu;
        $toplam = 0;
        $tamamlanan = 0;

        foreach ($altyapi as $key => $value) {
            $toplam++;
            if ($key === 'jenerator' || $key === 'ups') {
                if ($value === true) $tamamlanan++;
            } else {
                if (!empty($value)) $tamamlanan++;
            }
        }

        return $toplam > 0 ? round(($tamamlanan / $toplam) * 100) : 0;
    }

    /**
     * Ruhsat durumu
     */
    public function getRuhsatDurumuAttribute(): array
    {
        return [
            'ruhsat_durumu' => $this->getProperty('ruhsat_durumu'),
            'faaliyet_ruhsati' => $this->getProperty('faaliyet_ruhsati'),
            'cevre_izni' => $this->getProperty('cevre_izni'),
            'yapi_kullanim_izni' => $this->getProperty('yapı_kullanim_izni'),
        ];
    }

    /**
     * Ruhsat tamamlanma durumu
     */
    public function getRuhsatTamamlanmaDurumuAttribute(): string
    {
        $ruhsatlar = $this->ruhsat_durumu;
        $tamamlanan = count(array_filter($ruhsatlar, fn($r) => !empty($r)));
        $toplam = count($ruhsatlar);
        
        $oran = $toplam > 0 ? ($tamamlanan / $toplam) * 100 : 0;
        
        return match (true) {
            $oran == 100 => 'Tamamlandı',
            $oran >= 75 => 'Büyük Oranda Tamamlandı',
            $oran >= 50 => 'Yarı Tamamlandı',
            $oran >= 25 => 'Kısmen Tamamlandı',
            default => 'Eksik'
        };
    }

    /**
     * Asansör durumu
     */
    public function getAsansorDurumuAttribute(): string
    {
        $asansor = $this->getProperty('asansor_var_mi', false);
        $yukAsansoru = $this->getProperty('yuk_asansoru_var_mi', false);
        
        if ($asansor && $yukAsansoru) {
            return 'Yolcu ve Yük Asansörü';
        } elseif ($asansor) {
            return 'Yolcu Asansörü';
        } elseif ($yukAsansoru) {
            return 'Yük Asansörü';
        } else {
            return 'Asansör Yok';
        }
    }

    /**
     * Otopark yeterliliği
     */
    public function getOtoparkYeterliligiAttribute(): string
    {
        $kapasite = $this->getProperty('otopark_kapasitesi', 0);
        $kapaliAlan = $this->getProperty('kapali_alan', 0);
        
        if ($kapaliAlan <= 0) {
            return 'Hesaplanamaz';
        }
        
        // Her 100m² için 1 araç standardı
        $gerekliKapasite = ceil($kapaliAlan / 100);
        
        return match (true) {
            $kapasite >= $gerekliKapasite * 1.5 => 'Çok Yeterli',
            $kapasite >= $gerekliKapasite => 'Yeterli',
            $kapasite >= $gerekliKapasite * 0.7 => 'Kısmen Yeterli',
            $kapasite > 0 => 'Yetersiz',
            default => 'Otopark Yok'
        };
    }

    /**
     * Elektrik güç yeterliliği
     */
    public function getElektrikGucYeterliligiAttribute(): string
    {
        $guc = $this->getProperty('elektrik_guc_kapasitesi', 0);
        $kapaliAlan = $this->getProperty('kapali_alan', 0);
        
        if ($kapaliAlan <= 0 || $guc <= 0) {
            return 'Belirsiz';
        }
        
        // m² başına güç yoğunluğu (kW/m²)
        $gucYogunlugu = $guc / $kapaliAlan;
        
        return match (true) {
            $gucYogunlugu >= 0.1 => 'Çok Yeterli',
            $gucYogunlugu >= 0.05 => 'Yeterli',
            $gucYogunlugu >= 0.03 => 'Orta',
            default => 'Yetersiz'
        };
    }
}