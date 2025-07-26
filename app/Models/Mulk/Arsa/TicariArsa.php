<?php

namespace App\Models\Mulk\Arsa;

class TicariArsa extends Arsa
{
    /**
     * Mülk tipini döndür
     */
    public function getMulkType(): string
    {
        return 'ticari_arsa';
    }

    /**
     * Ticari arsa için ek geçerli özellikler
     */
    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'ticari_potansiyel',
            'ana_cadde_cephesi',
            'kose_parsel_mi',
            'otopark_zorunlulugu',
            'reklam_panosu_hakki',
            'giris_cikis_sayisi',
            'trafik_yogunlugu',
            'cevre_ticari_yogunluk',
            'toplu_tasima_mesafesi',
            'belediye_izinleri',
        ]);
    }

    /**
     * Ticari arsa için ek validation kuralları
     */
    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'ticari_potansiyel' => 'nullable|string|max:200',
            'ana_cadde_cephesi' => 'nullable|boolean',
            'kose_parsel_mi' => 'nullable|boolean',
            'otopark_zorunlulugu' => 'nullable|string|max:100',
            'reklam_panosu_hakki' => 'nullable|boolean',
            'giris_cikis_sayisi' => 'nullable|integer|min:1|max:10',
            'trafik_yogunlugu' => 'nullable|in:düşük,orta,yüksek,çok_yüksek',
            'cevre_ticari_yogunluk' => 'nullable|in:düşük,orta,yüksek',
            'toplu_tasima_mesafesi' => 'nullable|numeric|min:0',
            'belediye_izinleri' => 'nullable|string|max:500',
        ]);
    }

    /**
     * Ticari potansiyel değerlendirmesi
     */
    public function getTicariPotansiyelDegerlendirmesiAttribute(): array
    {
        $puan = 0;
        $detaylar = [];

        // Ana cadde cephesi
        if ($this->getProperty('ana_cadde_cephesi')) {
            $puan += 20;
            $detaylar[] = 'Ana cadde cephesi (+20)';
        }

        // Köşe parsel
        if ($this->getProperty('kose_parsel_mi')) {
            $puan += 15;
            $detaylar[] = 'Köşe parsel (+15)';
        }

        // Trafik yoğunluğu
        $trafikYogunlugu = $this->getProperty('trafik_yogunlugu');
        $trafikPuan = match ($trafikYogunlugu) {
            'çok_yüksek' => 25,
            'yüksek' => 20,
            'orta' => 10,
            'düşük' => 5,
            default => 0
        };
        if ($trafikPuan > 0) {
            $puan += $trafikPuan;
            $detaylar[] = "Trafik yoğunluğu: {$trafikYogunlugu} (+{$trafikPuan})";
        }

        // Çevre ticari yoğunluk
        $cevreYogunluk = $this->getProperty('cevre_ticari_yogunluk');
        $cevreYogunlukPuan = match ($cevreYogunluk) {
            'yüksek' => 20,
            'orta' => 10,
            'düşük' => 5,
            default => 0
        };
        if ($cevreYogunlukPuan > 0) {
            $puan += $cevreYogunlukPuan;
            $detaylar[] = "Çevre ticari yoğunluk: {$cevreYogunluk} (+{$cevreYogunlukPuan})";
        }

        // Toplu taşıma mesafesi
        $topluTasimaMesafe = $this->getProperty('toplu_tasima_mesafesi');
        if ($topluTasimaMesafe !== null) {
            if ($topluTasimaMesafe <= 100) {
                $puan += 15;
                $detaylar[] = 'Toplu taşımaya çok yakın (+15)';
            } elseif ($topluTasimaMesafe <= 300) {
                $puan += 10;
                $detaylar[] = 'Toplu taşımaya yakın (+10)';
            } elseif ($topluTasimaMesafe <= 500) {
                $puan += 5;
                $detaylar[] = 'Toplu taşımaya orta mesafe (+5)';
            }
        }

        return [
            'puan' => min($puan, 100), // Maksimum 100 puan
            'seviye' => match (true) {
                $puan >= 80 => 'Çok Yüksek',
                $puan >= 60 => 'Yüksek',
                $puan >= 40 => 'Orta',
                $puan >= 20 => 'Düşük',
                default => 'Çok Düşük'
            },
            'detaylar' => $detaylar
        ];
    }

    /**
     * Reklam panosu hakkı var mı
     */
    public function getReklamPanosuHakkiVarMiAttribute(): bool
    {
        return (bool) $this->getProperty('reklam_panosu_hakki', false);
    }

    /**
     * Giriş çıkış avantajı
     */
    public function getGirisCikisAvantajiAttribute(): string
    {
        $girisСikis = $this->getProperty('giris_cikis_sayisi', 1);
        
        return match (true) {
            $girisСikis >= 3 => 'Çok avantajlı (3+ giriş)',
            $girisСikis == 2 => 'Avantajlı (2 giriş)',
            $girisСikis == 1 => 'Standart (1 giriş)',
            default => 'Belirsiz'
        };
    }
}