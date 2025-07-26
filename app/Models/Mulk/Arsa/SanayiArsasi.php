<?php

namespace App\Models\Mulk\Arsa;

class SanayiArsasi extends Arsa
{
    /**
     * Mülk tipini döndür
     */
    public function getMulkType(): string
    {
        return 'sanayi_arsasi';
    }

    /**
     * Sanayi arsası için ek geçerli özellikler
     */
    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'sanayi_bolgesi_tipi',
            'cevre_kirliligi_durumu',
            'agir_tasit_erisimi',
            'yuk_bosaltma_alani',
            'elektrik_guc_kapasitesi',
            'su_basinc_durumu',
            'atiksu_sistemi',
            'yangin_hidrant_mesafesi',
            'guvenlik_onlemleri',
            'genisletme_potansiyeli',
            'komsu_sanayi_tesisleri',
            'cevre_etki_degerlendirmesi',
            'ozel_izin_gereksinimleri',
        ]);
    }

    /**
     * Sanayi arsası için ek validation kuralları
     */
    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'sanayi_bolgesi_tipi' => 'nullable|in:organize_sanayi,kucuk_sanayi,serbest_bolge,teknoloji_gelistirme',
            'cevre_kirliligi_durumu' => 'nullable|in:temiz,orta,kirli',
            'agir_tasit_erisimi' => 'nullable|boolean',
            'yuk_bosaltma_alani' => 'nullable|numeric|min:0',
            'elektrik_guc_kapasitesi' => 'nullable|numeric|min:0',
            'su_basinc_durumu' => 'nullable|in:düşük,normal,yüksek',
            'atiksu_sistemi' => 'nullable|in:yok,basit,gelismis',
            'yangin_hidrant_mesafesi' => 'nullable|numeric|min:0',
            'guvenlik_onlemleri' => 'nullable|string|max:500',
            'genisletme_potansiyeli' => 'nullable|boolean',
            'komsu_sanayi_tesisleri' => 'nullable|string|max:500',
            'cevre_etki_degerlendirmesi' => 'nullable|string|max:1000',
            'ozel_izin_gereksinimleri' => 'nullable|string|max:500',
        ]);
    }

    /**
     * Sanayi bölgesi tipi etiketi
     */
    public function getSanayiBolgesiTipiLabelAttribute(): string
    {
        $tip = $this->getProperty('sanayi_bolgesi_tipi');
        
        return match ($tip) {
            'organize_sanayi' => 'Organize Sanayi Bölgesi',
            'kucuk_sanayi' => 'Küçük Sanayi Sitesi',
            'serbest_bolge' => 'Serbest Bölge',
            'teknoloji_gelistirme' => 'Teknoloji Geliştirme Bölgesi',
            default => 'Belirtilmemiş'
        };
    }

    /**
     * Sanayi uygunluk değerlendirmesi
     */
    public function getSanayiUygunlukDegerlendirmesiAttribute(): array
    {
        $puan = 0;
        $detaylar = [];

        // Sanayi bölgesi tipi
        $bolgeTipi = $this->getProperty('sanayi_bolgesi_tipi');
        $bolgePuan = match ($bolgeTipi) {
            'organize_sanayi' => 25,
            'teknoloji_gelistirme' => 20,
            'serbest_bolge' => 20,
            'kucuk_sanayi' => 15,
            default => 0
        };
        if ($bolgePuan > 0) {
            $puan += $bolgePuan;
            $detaylar[] = "Bölge tipi: {$this->sanayi_bolgesi_tipi_label} (+{$bolgePuan})";
        }

        // Ağır taşıt erişimi
        if ($this->getProperty('agir_tasit_erisimi')) {
            $puan += 20;
            $detaylar[] = 'Ağır taşıt erişimi mevcut (+20)';
        }

        // Elektrik güç kapasitesi
        $elektrikGuc = $this->getProperty('elektrik_guc_kapasitesi');
        if ($elektrikGuc !== null) {
            if ($elektrikGuc >= 1000) {
                $puan += 15;
                $detaylar[] = 'Yüksek elektrik kapasitesi (+15)';
            } elseif ($elektrikGuc >= 500) {
                $puan += 10;
                $detaylar[] = 'Orta elektrik kapasitesi (+10)';
            } elseif ($elektrikGuc >= 100) {
                $puan += 5;
                $detaylar[] = 'Düşük elektrik kapasitesi (+5)';
            }
        }

        // Su basınç durumu
        $suBasinc = $this->getProperty('su_basinc_durumu');
        $suBasincPuan = match ($suBasinc) {
            'yüksek' => 15,
            'normal' => 10,
            'düşük' => 5,
            default => 0
        };
        if ($suBasincPuan > 0) {
            $puan += $suBasincPuan;
            $detaylar[] = "Su basıncı: {$suBasinc} (+{$suBasincPuan})";
        }

        // Atıksu sistemi
        $atiksu = $this->getProperty('atiksu_sistemi');
        $atiksuPuan = match ($atiksu) {
            'gelismis' => 15,
            'basit' => 8,
            'yok' => 0,
            default => 0
        };
        if ($atiksuPuan > 0) {
            $puan += $atiksuPuan;
            $detaylar[] = "Atıksu sistemi: {$atiksu} (+{$atiksuPuan})";
        }

        // Çevre kirliliği durumu (negatif etki)
        $cevre = $this->getProperty('cevre_kirliligi_durumu');
        $cevrePuan = match ($cevre) {
            'temiz' => 10,
            'orta' => 5,
            'kirli' => -10,
            default => 0
        };
        if ($cevrePuan != 0) {
            $puan += $cevrePuan;
            $detaylar[] = "Çevre durumu: {$cevre} ({$cevrePuan})";
        }

        // Genişletme potansiyeli
        if ($this->getProperty('genisletme_potansiyeli')) {
            $puan += 10;
            $detaylar[] = 'Genişletme potansiyeli mevcut (+10)';
        }

        return [
            'puan' => max(0, min($puan, 100)), // 0-100 arası
            'seviye' => match (true) {
                $puan >= 80 => 'Çok Uygun',
                $puan >= 60 => 'Uygun',
                $puan >= 40 => 'Orta',
                $puan >= 20 => 'Düşük',
                default => 'Uygun Değil'
            },
            'detaylar' => $detaylar
        ];
    }

    /**
     * Yangın güvenlik durumu
     */
    public function getYanginGuvenlikDurumuAttribute(): string
    {
        $hidrantMesafe = $this->getProperty('yangin_hidrant_mesafesi');
        
        if ($hidrantMesafe === null) {
            return 'Belirsiz';
        }
        
        return match (true) {
            $hidrantMesafe <= 100 => 'Çok İyi (≤100m)',
            $hidrantMesafe <= 200 => 'İyi (≤200m)',
            $hidrantMesafe <= 500 => 'Orta (≤500m)',
            default => 'Yetersiz (>500m)'
        };
    }

    /**
     * Yük boşaltma alanı yeterliliği
     */
    public function getYukBosaltmaYeterliligiAttribute(): string
    {
        $alan = $this->getProperty('yuk_bosaltma_alani');
        $arsaAlani = $this->metrekare;
        
        if (!$alan || !$arsaAlani) {
            return 'Belirsiz';
        }
        
        $oran = ($alan / $arsaAlani) * 100;
        
        return match (true) {
            $oran >= 15 => 'Çok Yeterli (≥15%)',
            $oran >= 10 => 'Yeterli (≥10%)',
            $oran >= 5 => 'Orta (≥5%)',
            default => 'Yetersiz (<5%)'
        };
    }
}