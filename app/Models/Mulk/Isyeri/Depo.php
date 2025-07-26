<?php

namespace App\Models\Mulk\Isyeri;

class Depo extends Isyeri
{
    /**
     * Mülk tipini döndür
     */
    public function getMulkType(): string
    {
        return 'depo';
    }

    /**
     * Depo için ek geçerli özellikler
     */
    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'ellecleme_alani',
            'rampa_sayisi',
            'rampa_yuksekligi',
            'dock_sayisi',
            'dock_tipi',
            'zemin_tipi',
            'zemin_tasima_kapasitesi',
            'raf_sistemi_var_mi',
            'raf_yuksekligi',
            'forklift_gecis_genisligi',
            'sicaklik_kontrol_sistemi',
            'nem_kontrol_sistemi',
            'havalandirma_tipi',
            'aydinlatma_tipi',
            'sprinkler_sistemi',
            'duman_dedektoru',
            'alarm_sistemi',
            'kamera_sistemi',
            'giris_kontrol_sistemi',
            'arac_giris_yuksekligi',
            'arac_giris_genisligi',
            'manevra_alani',
            'bekleme_alani',
            'kantarr_var_mi',
            'yuk_asansoru_kapasitesi',
            'depolama_tipi',
            'ozel_depolama_sartlari',
        ]);
    }

    /**
     * Depo için ek validation kuralları
     */
    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'ellecleme_alani' => 'nullable|numeric|min:0',
            'rampa_sayisi' => 'nullable|integer|min:0|max:50',
            'rampa_yuksekligi' => 'nullable|numeric|min:0|max:5',
            'dock_sayisi' => 'nullable|integer|min:0|max:100',
            'dock_tipi' => 'nullable|in:sabit,ayarlanabilir,hidrolik',
            'zemin_tipi' => 'nullable|in:beton,asfalt,epoksi,poliuretan',
            'zemin_tasima_kapasitesi' => 'nullable|numeric|min:0',
            'raf_sistemi_var_mi' => 'nullable|boolean',
            'raf_yuksekligi' => 'nullable|numeric|min:0|max:30',
            'forklift_gecis_genisligi' => 'nullable|numeric|min:0|max:10',
            'sicaklik_kontrol_sistemi' => 'nullable|in:yok,isitma,sogutma,klima',
            'nem_kontrol_sistemi' => 'nullable|boolean',
            'havalandirma_tipi' => 'nullable|in:dogal,mekanik,karma',
            'aydinlatma_tipi' => 'nullable|in:dogal,led,floresan,karma',
            'sprinkler_sistemi' => 'nullable|boolean',
            'duman_dedektoru' => 'nullable|boolean',
            'alarm_sistemi' => 'nullable|boolean',
            'kamera_sistemi' => 'nullable|boolean',
            'giris_kontrol_sistemi' => 'nullable|boolean',
            'arac_giris_yuksekligi' => 'nullable|numeric|min:0|max:10',
            'arac_giris_genisligi' => 'nullable|numeric|min:0|max:20',
            'manevra_alani' => 'nullable|numeric|min:0',
            'bekleme_alani' => 'nullable|numeric|min:0',
            'kantarr_var_mi' => 'nullable|boolean',
            'yuk_asansoru_kapasitesi' => 'nullable|numeric|min:0',
            'depolama_tipi' => 'nullable|in:genel,soguk_hava,kimyasal,tehlikeli_madde,gida',
            'ozel_depolama_sartlari' => 'nullable|string|max:500',
        ]);
    }

    /**
     * Depo alan dağılımı
     */
    public function getDepoAlanDagilimiAttribute(): array
    {
        $kapaliAlan = $this->getProperty('kapali_alan', 0);
        $elleclemeAlani = $this->getProperty('ellecleme_alani', 0);
        $ofisAlani = $this->getProperty('ofis_alani', 0);
        $manevraAlani = $this->getProperty('manevra_alani', 0);
        $beklemeAlani = $this->getProperty('bekleme_alani', 0);

        $netDepoAlani = $kapaliAlan - $elleclemeAlani - $ofisAlani;
        $toplamAlan = $kapaliAlan + $manevraAlani + $beklemeAlani;

        if ($toplamAlan <= 0) {
            return [];
        }

        return [
            'net_depo' => [
                'alan' => max(0, $netDepoAlani),
                'oran' => round((max(0, $netDepoAlani) / $toplamAlan) * 100, 1)
            ],
            'ellecleme' => [
                'alan' => $elleclemeAlani,
                'oran' => round(($elleclemeAlani / $toplamAlan) * 100, 1)
            ],
            'ofis' => [
                'alan' => $ofisAlani,
                'oran' => round(($ofisAlani / $toplamAlan) * 100, 1)
            ],
            'manevra' => [
                'alan' => $manevraAlani,
                'oran' => round(($manevraAlani / $toplamAlan) * 100, 1)
            ],
            'bekleme' => [
                'alan' => $beklemeAlani,
                'oran' => round(($beklemeAlani / $toplamAlan) * 100, 1)
            ],
        ];
    }

    /**
     * Yükleme boşaltma kapasitesi
     */
    public function getYuklemeBoşaltmaKapasitesiAttribute(): array
    {
        $rampaSayisi = $this->getProperty('rampa_sayisi', 0);
        $dockSayisi = $this->getProperty('dock_sayisi', 0);
        $rampaYuksekligi = $this->getProperty('rampa_yuksekligi', 0);
        $dockTipi = $this->getProperty('dock_tipi');

        // Saatlik yükleme kapasitesi tahmini
        $rampaKapasitesi = $rampaSayisi * 2; // Rampa başına saatte 2 araç
        $dockKapasitesi = $dockSayisi * 3; // Dock başına saatte 3 araç
        $toplamKapasite = $rampaKapasitesi + $dockKapasitesi;

        return [
            'rampa' => [
                'sayisi' => $rampaSayisi,
                'yukseklik' => $rampaYuksekligi,
                'saatlik_kapasite' => $rampaKapasitesi
            ],
            'dock' => [
                'sayisi' => $dockSayisi,
                'tipi' => $dockTipi,
                'saatlik_kapasite' => $dockKapasitesi
            ],
            'toplam_saatlik_kapasite' => $toplamKapasite,
            'degerlendirme' => match (true) {
                $toplamKapasite >= 20 => 'Çok Yüksek',
                $toplamKapasite >= 15 => 'Yüksek',
                $toplamKapasite >= 10 => 'Orta',
                $toplamKapasite >= 5 => 'Düşük',
                default => 'Çok Düşük'
            }
        ];
    }

    /**
     * Depolama verimliliği
     */
    public function getDepolamaVerimliligiAttribute(): array
    {
        $rafSistemi = $this->getProperty('raf_sistemi_var_mi', false);
        $rafYuksekligi = $this->getProperty('raf_yuksekligi', 0);
        $tavanYuksekligi = $this->getProperty('tavan_yuksekligi', 0);
        $forkliftGecis = $this->getProperty('forklift_gecis_genisligi', 0);
        $kapaliAlan = $this->getProperty('kapali_alan', 0);

        $puan = 0;
        $detaylar = [];

        // Raf sistemi
        if ($rafSistemi) {
            $puan += 30;
            $detaylar[] = 'Raf sistemi mevcut';

            // Raf yüksekliği verimliliği
            if ($rafYuksekligi > 0 && $tavanYuksekligi > 0) {
                $yukseklikVerimi = ($rafYuksekligi / $tavanYuksekligi) * 100;
                if ($yukseklikVerimi >= 80) {
                    $puan += 20;
                    $detaylar[] = 'Yükseklik çok verimli kullanılıyor';
                } elseif ($yukseklikVerimi >= 60) {
                    $puan += 15;
                    $detaylar[] = 'Yükseklik verimli kullanılıyor';
                } elseif ($yukseklikVerimi >= 40) {
                    $puan += 10;
                    $detaylar[] = 'Yükseklik orta verimde kullanılıyor';
                }
            }
        }

        // Forklift geçiş genişliği
        if ($forkliftGecis >= 3.5) {
            $puan += 20;
            $detaylar[] = 'Forklift geçişi çok uygun';
        } elseif ($forkliftGecis >= 3.0) {
            $puan += 15;
            $detaylar[] = 'Forklift geçişi uygun';
        } elseif ($forkliftGecis >= 2.5) {
            $puan += 10;
            $detaylar[] = 'Forklift geçişi dar';
        }

        // Zemin taşıma kapasitesi
        $zeminKapasite = $this->getProperty('zemin_tasima_kapasitesi', 0);
        if ($zeminKapasite >= 5000) {
            $puan += 15;
            $detaylar[] = 'Zemin taşıma kapasitesi çok yüksek';
        } elseif ($zeminKapasite >= 3000) {
            $puan += 10;
            $detaylar[] = 'Zemin taşıma kapasitesi yeterli';
        } elseif ($zeminKapasite >= 1000) {
            $puan += 5;
            $detaylar[] = 'Zemin taşıma kapasitesi düşük';
        }

        // Tavan yüksekliği
        if ($tavanYuksekligi >= 8) {
            $puan += 15;
            $detaylar[] = 'Tavan yüksekliği çok uygun';
        } elseif ($tavanYuksekligi >= 6) {
            $puan += 10;
            $detaylar[] = 'Tavan yüksekliği uygun';
        } elseif ($tavanYuksekligi >= 4) {
            $puan += 5;
            $detaylar[] = 'Tavan yüksekliği düşük';
        }

        return [
            'puan' => $puan,
            'seviye' => match (true) {
                $puan >= 80 => 'Çok Verimli',
                $puan >= 60 => 'Verimli',
                $puan >= 40 => 'Orta',
                $puan >= 20 => 'Düşük Verim',
                default => 'Verimsiz'
            },
            'detaylar' => $detaylar
        ];
    }

    /**
     * Güvenlik sistemi durumu
     */
    public function getGuvenlikSistemiDurumuAttribute(): array
    {
        $sprinkler = $this->getProperty('sprinkler_sistemi', false);
        $dumanDedektoru = $this->getProperty('duman_dedektoru', false);
        $alarm = $this->getProperty('alarm_sistemi', false);
        $kamera = $this->getProperty('kamera_sistemi', false);
        $girisKontrol = $this->getProperty('giris_kontrol_sistemi', false);

        $sistemler = [
            'sprinkler' => $sprinkler,
            'duman_dedektoru' => $dumanDedektoru,
            'alarm' => $alarm,
            'kamera' => $kamera,
            'giris_kontrol' => $girisKontrol,
        ];

        $aktifSistemSayisi = count(array_filter($sistemler));
        $toplamSistem = count($sistemler);
        $tamamlanmaOrani = ($aktifSistemSayisi / $toplamSistem) * 100;

        return [
            'sistemler' => $sistemler,
            'aktif_sistem_sayisi' => $aktifSistemSayisi,
            'tamamlanma_orani' => round($tamamlanmaOrani, 1),
            'seviye' => match (true) {
                $tamamlanmaOrani >= 80 => 'Çok Güvenli',
                $tamamlanmaOrani >= 60 => 'Güvenli',
                $tamamlanmaOrani >= 40 => 'Orta',
                $tamamlanmaOrani >= 20 => 'Düşük',
                default => 'Yetersiz'
            )
        ];
    }

    /**
     * Araç erişim uygunluğu
     */
    public function getAracErisimUygunluguAttribute(): array
    {
        $girisYuksekligi = $this->getProperty('arac_giris_yuksekligi', 0);
        $girisGenisligi = $this->getProperty('arac_giris_genisligi', 0);
        $manevraAlani = $this->getProperty('manevra_alani', 0);

        $uygunlukPuani = 0;
        $detaylar = [];

        // Giriş yüksekliği (standart kamyon: 4m)
        if ($girisYuksekligi >= 4.5) {
            $uygunlukPuani += 35;
            $detaylar[] = 'Tüm araç tipleri girebilir';
        } elseif ($girisYuksekligi >= 4.0) {
            $uygunlukPuani += 30;
            $detaylar[] = 'Standart kamyonlar girebilir';
        } elseif ($girisYuksekligi >= 3.5) {
            $uygunlukPuani += 20;
            $detaylar[] = 'Küçük kamyonlar girebilir';
        } elseif ($girisYuksekligi >= 2.5) {
            $uygunlukPuani += 10;
            $detaylar[] = 'Sadece minibüs/kamyonet girebilir';
        }

        // Giriş genişliği (standart: 3.5m)
        if ($girisGenisligi >= 4.0) {
            $uygunlukPuani += 25;
            $detaylar[] = 'Giriş genişliği çok uygun';
        } elseif ($girisGenisligi >= 3.5) {
            $uygunlukPuani += 20;
            $detaylar[] = 'Giriş genişliği uygun';
        } elseif ($girisGenisligi >= 3.0) {
            $uygunlukPuani += 15;
            $detaylar[] = 'Giriş genişliği dar';
        }

        // Manevra alanı
        if ($manevraAlani >= 500) {
            $uygunlukPuani += 40;
            $detaylar[] = 'Manevra alanı çok geniş';
        } elseif ($manevraAlani >= 300) {
            $uygunlukPuani += 30;
            $detaylar[] = 'Manevra alanı yeterli';
        } elseif ($manevraAlani >= 150) {
            $uygunlukPuani += 20;
            $detaylar[] = 'Manevra alanı dar';
        } elseif ($manevraAlani > 0) {
            $uygunlukPuani += 10;
            $detaylar[] = 'Manevra alanı çok dar';
        }

        return [
            'puan' => $uygunlukPuani,
            'seviye' => match (true) {
                $uygunlukPuani >= 80 => 'Çok Uygun',
                $uygunlukPuani >= 60 => 'Uygun',
                $uygunlukPuani >= 40 => 'Orta',
                $uygunlukPuani >= 20 => 'Kısıtlı',
                default => 'Uygun Değil'
            },
            'detaylar' => $detaylar
        ];
    }

    /**
     * Depolama tipi etiketi
     */
    public function getDepolamaTipiLabelAttribute(): string
    {
        $tip = $this->getProperty('depolama_tipi');
        
        return match ($tip) {
            'genel' => 'Genel Depolama',
            'soguk_hava' => 'Soğuk Hava Deposu',
            'kimyasal' => 'Kimyasal Depo',
            'tehlikeli_madde' => 'Tehlikeli Madde Deposu',
            'gida' => 'Gıda Deposu',
            default => 'Belirtilmemiş'
        };
    }
}