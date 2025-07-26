<?php

namespace App\Services;

use App\Models\MusteriHizmet;
use App\Models\Musteri\Musteri;
use App\Models\User;
use App\Enums\HizmetTipi;
use App\Enums\HizmetSonucu;
use App\Enums\DegerlendirmeTipi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class HizmetRaporlamaService
{
    /**
     * Hizmet istatistikleri
     */
    public function getHizmetIstatistikleri(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriHizmet::query(), $filters);

        $toplamHizmet = $query->count();
        $basariliHizmet = $query->clone()->successful()->count();
        $basarisizHizmet = $query->clone()->failed()->count();
        $takipGerekenHizmet = $query->clone()->requiresFollowUp()->count();

        $ortalamaSure = $query->clone()->whereNotNull('sure_dakika')->avg('sure_dakika');
        $toplamMaliyet = $query->clone()->whereNotNull('maliyet')->sum('maliyet');

        return [
            'toplam_hizmet' => $toplamHizmet,
            'basarili_hizmet' => $basariliHizmet,
            'basarisiz_hizmet' => $basarisizHizmet,
            'takip_gereken_hizmet' => $takipGerekenHizmet,
            'basari_orani' => $toplamHizmet > 0 ? round(($basariliHizmet / $toplamHizmet) * 100, 2) : 0,
            'ortalama_sure_dakika' => round($ortalamaSure ?? 0, 2),
            'ortalama_sure_formatli' => $this->formatDuration($ortalamaSure ?? 0),
            'toplam_maliyet' => $toplamMaliyet,
            'ortalama_maliyet' => $toplamHizmet > 0 ? round($toplamMaliyet / $toplamHizmet, 2) : 0,
        ];
    }

    /**
     * Hizmet tipi dağılımı
     */
    public function getHizmetTipiDagilimi(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriHizmet::query(), $filters);

        $dagilim = $query->selectRaw('hizmet_tipi, COUNT(*) as sayi')
            ->groupBy('hizmet_tipi')
            ->orderByDesc('sayi')
            ->get()
            ->map(function ($item) {
                $hizmetTipi = HizmetTipi::fromValue($item->hizmet_tipi);
                return [
                    'tip' => $item->hizmet_tipi,
                    'label' => $hizmetTipi?->label() ?? 'Bilinmiyor',
                    'sayi' => $item->sayi,
                    'renk' => $hizmetTipi?->color() ?? 'gray',
                    'ikon' => $hizmetTipi?->icon() ?? 'heroicon-o-question-mark-circle',
                ];
            });

        $toplam = $dagilim->sum('sayi');

        return $dagilim->map(function ($item) use ($toplam) {
            $item['yuzde'] = $toplam > 0 ? round(($item['sayi'] / $toplam) * 100, 2) : 0;
            return $item;
        })->toArray();
    }

    /**
     * Sonuç tipi dağılımı
     */
    public function getSonucTipiDagilimi(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriHizmet::query(), $filters);

        $dagilim = $query->whereNotNull('sonuc_tipi')
            ->selectRaw('sonuc_tipi, COUNT(*) as sayi')
            ->groupBy('sonuc_tipi')
            ->orderByDesc('sayi')
            ->get()
            ->map(function ($item) {
                $sonucTipi = HizmetSonucu::fromValue($item->sonuc_tipi);
                return [
                    'tip' => $item->sonuc_tipi,
                    'label' => $sonucTipi?->label() ?? 'Bilinmiyor',
                    'sayi' => $item->sayi,
                    'renk' => $sonucTipi?->color() ?? 'gray',
                    'ikon' => $sonucTipi?->icon() ?? 'heroicon-o-question-mark-circle',
                ];
            });

        $toplam = $dagilim->sum('sayi');

        return $dagilim->map(function ($item) use ($toplam) {
            $item['yuzde'] = $toplam > 0 ? round(($item['sayi'] / $toplam) * 100, 2) : 0;
            return $item;
        })->toArray();
    }

    /**
     * Değerlendirme dağılımı
     */
    public function getDegerlendirmeDagilimi(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriHizmet::query(), $filters);

        $dagilim = $query->whereJsonContains('degerlendirme->tip', function ($q) {
                return $q->whereNotNull('degerlendirme');
            })
            ->get()
            ->groupBy(function ($hizmet) {
                return $hizmet->degerlendirme['tip'] ?? 'degerlendirilmemis';
            })
            ->map(function ($group, $tip) {
                $degerlendirmeTipi = DegerlendirmeTipi::fromValue($tip);
                return [
                    'tip' => $tip,
                    'label' => $degerlendirmeTipi?->label() ?? 'Değerlendirilmemiş',
                    'sayi' => $group->count(),
                    'renk' => $degerlendirmeTipi?->color() ?? 'gray',
                    'ikon' => $degerlendirmeTipi?->icon() ?? 'heroicon-o-minus',
                    'ortalama_puan' => round($group->avg(function ($hizmet) {
                        return $hizmet->degerlendirme['puan'] ?? 0;
                    }), 2),
                ];
            });

        $toplam = $dagilim->sum('sayi');

        return $dagilim->map(function ($item) use ($toplam) {
            $item['yuzde'] = $toplam > 0 ? round(($item['sayi'] / $toplam) * 100, 2) : 0;
            return $item;
        })->values()->toArray();
    }

    /**
     * Personel performansı
     */
    public function getPersonelPerformansi(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriHizmet::query(), $filters);

        return $query->with('personel')
            ->selectRaw('
                personel_id,
                COUNT(*) as toplam_hizmet,
                AVG(sure_dakika) as ortalama_sure,
                SUM(maliyet) as toplam_maliyet,
                COUNT(CASE WHEN sonuc_tipi IN (?) THEN 1 END) as basarili_hizmet
            ', [array_map(fn($r) => $r->value, HizmetSonucu::positiveResults())])
            ->groupBy('personel_id')
            ->orderByDesc('toplam_hizmet')
            ->get()
            ->map(function ($item) {
                $basariOrani = $item->toplam_hizmet > 0 
                    ? round(($item->basarili_hizmet / $item->toplam_hizmet) * 100, 2) 
                    : 0;

                return [
                    'personel_id' => $item->personel_id,
                    'personel_adi' => $item->personel?->name ?? 'Bilinmiyor',
                    'toplam_hizmet' => $item->toplam_hizmet,
                    'basarili_hizmet' => $item->basarili_hizmet,
                    'basari_orani' => $basariOrani,
                    'ortalama_sure' => round($item->ortalama_sure ?? 0, 2),
                    'ortalama_sure_formatli' => $this->formatDuration($item->ortalama_sure ?? 0),
                    'toplam_maliyet' => $item->toplam_maliyet ?? 0,
                    'ortalama_maliyet' => $item->toplam_hizmet > 0 
                        ? round(($item->toplam_maliyet ?? 0) / $item->toplam_hizmet, 2) 
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Müşteri hizmet geçmişi
     */
    public function getMusteriHizmetGecmisi(string $musteriId, array $filters = []): array
    {
        $filters['musteri_id'] = $musteriId;
        $query = $this->applyFilters(MusteriHizmet::query(), $filters);

        $hizmetler = $query->with(['personel', 'mulk'])
            ->orderByDesc('hizmet_tarihi')
            ->get()
            ->map(function ($hizmet) {
                return [
                    'id' => $hizmet->id,
                    'tip' => $hizmet->hizmet_tipi_label,
                    'tip_rengi' => $hizmet->hizmet_tipi_color,
                    'tip_ikonu' => $hizmet->hizmet_tipi_icon,
                    'tarih' => $hizmet->hizmet_tarihi->format('d.m.Y H:i'),
                    'sure' => $hizmet->formatted_duration,
                    'personel' => $hizmet->personel?->name,
                    'mulk' => $hizmet->mulk?->baslik,
                    'lokasyon' => $hizmet->lokasyon,
                    'aciklama' => $hizmet->aciklama,
                    'sonuc' => $hizmet->sonuc_tipi_label,
                    'sonuc_rengi' => $hizmet->sonuc_tipi_color,
                    'degerlendirme' => $hizmet->degerlendirme_label,
                    'degerlendirme_rengi' => $hizmet->degerlendirme_color,
                    'degerlendirme_puani' => $hizmet->degerlendirme_puani,
                    'maliyet' => $hizmet->formatted_maliyet,
                    'etiketler' => $hizmet->etiketler ?? [],
                    'katilimcilar' => $hizmet->katilimcilar ?? [],
                ];
            });

        $istatistikler = [
            'toplam_hizmet' => $hizmetler->count(),
            'son_hizmet_tarihi' => $hizmetler->first()?['tarih'],
            'en_cok_kullanilan_tip' => $this->getEnCokKullanilanTip($hizmetler),
            'ortalama_degerlendirme' => $this->getOrtalamaDegerlendirme($hizmetler),
            'toplam_sure' => $this->getTotalDuration($hizmetler),
        ];

        return [
            'hizmetler' => $hizmetler->toArray(),
            'istatistikler' => $istatistikler,
        ];
    }

    /**
     * Zaman bazlı analiz
     */
    public function getZamanBazliAnaliz(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriHizmet::query(), $filters);

        // Günlük dağılım
        $gunlukDagilim = $query->clone()
            ->selectRaw('DATE(hizmet_tarihi) as tarih, COUNT(*) as sayi')
            ->groupBy('tarih')
            ->orderBy('tarih')
            ->get()
            ->map(function ($item) {
                return [
                    'tarih' => Carbon::parse($item->tarih)->format('d.m.Y'),
                    'sayi' => $item->sayi,
                ];
            });

        // Saatlik dağılım
        $saatlikDagilim = $query->clone()
            ->selectRaw('HOUR(hizmet_tarihi) as saat, COUNT(*) as sayi')
            ->groupBy('saat')
            ->orderBy('saat')
            ->get()
            ->map(function ($item) {
                return [
                    'saat' => $item->saat . ':00',
                    'sayi' => $item->sayi,
                ];
            });

        // Haftalık dağılım
        $haftaGunleri = [
            1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe',
            5 => 'Cuma', 6 => 'Cumartesi', 0 => 'Pazar'
        ];

        $haftalikDagilim = $query->clone()
            ->selectRaw('DAYOFWEEK(hizmet_tarihi) - 1 as gun, COUNT(*) as sayi')
            ->groupBy('gun')
            ->orderBy('gun')
            ->get()
            ->map(function ($item) use ($haftaGunleri) {
                return [
                    'gun' => $haftaGunleri[$item->gun] ?? 'Bilinmiyor',
                    'sayi' => $item->sayi,
                ];
            });

        return [
            'gunluk' => $gunlukDagilim->toArray(),
            'saatlik' => $saatlikDagilim->toArray(),
            'haftalik' => $haftalikDagilim->toArray(),
        ];
    }

    /**
     * Filtreleri uygula
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['musteri_id'])) {
            $query->where('musteri_id', $filters['musteri_id']);
        }

        if (isset($filters['personel_id'])) {
            $query->where('personel_id', $filters['personel_id']);
        }

        if (isset($filters['hizmet_tipi'])) {
            $query->where('hizmet_tipi', $filters['hizmet_tipi']);
        }

        if (isset($filters['sonuc_tipi'])) {
            $query->where('sonuc_tipi', $filters['sonuc_tipi']);
        }

        if (isset($filters['degerlendirme_tipi'])) {
            $query->whereJsonContains('degerlendirme->tip', $filters['degerlendirme_tipi']);
        }

        if (isset($filters['baslangic_tarihi'])) {
            $query->where('hizmet_tarihi', '>=', $filters['baslangic_tarihi']);
        }

        if (isset($filters['bitis_tarihi'])) {
            $query->where('hizmet_tarihi', '<=', $filters['bitis_tarihi']);
        }

        if (isset($filters['min_sure'])) {
            $query->where('sure_dakika', '>=', $filters['min_sure']);
        }

        if (isset($filters['max_sure'])) {
            $query->where('sure_dakika', '<=', $filters['max_sure']);
        }

        if (isset($filters['min_maliyet'])) {
            $query->where('maliyet', '>=', $filters['min_maliyet']);
        }

        if (isset($filters['max_maliyet'])) {
            $query->where('maliyet', '<=', $filters['max_maliyet']);
        }

        if (isset($filters['etiket'])) {
            $query->whereJsonContains('etiketler', $filters['etiket']);
        }

        if (isset($filters['lokasyon'])) {
            $query->where('lokasyon', 'like', '%' . $filters['lokasyon'] . '%');
        }

        return $query;
    }

    /**
     * Süreyi formatla
     */
    private function formatDuration(float $minutes): string
    {
        if ($minutes < 60) {
            return round($minutes) . ' dakika';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = round($minutes % 60);

        if ($remainingMinutes === 0) {
            return $hours . ' saat';
        }

        return $hours . ' saat ' . $remainingMinutes . ' dakika';
    }

    /**
     * En çok kullanılan tipi bul
     */
    private function getEnCokKullanilanTip(Collection $hizmetler): ?string
    {
        return $hizmetler->groupBy('tip')
            ->sortByDesc(fn($group) => $group->count())
            ->keys()
            ->first();
    }

    /**
     * Ortalama değerlendirme hesapla
     */
    private function getOrtalamaDegerlendirme(Collection $hizmetler): float
    {
        $degerlendirmeler = $hizmetler->whereNotNull('degerlendirme_puani');
        
        if ($degerlendirmeler->isEmpty()) {
            return 0;
        }

        return round($degerlendirmeler->avg('degerlendirme_puani'), 2);
    }

    /**
     * Toplam süre hesapla
     */
    private function getTotalDuration(Collection $hizmetler): string
    {
        $toplamDakika = $hizmetler->sum(function ($hizmet) {
            // Sure string'ini dakikaya çevir
            $sure = $hizmet['sure'] ?? '';
            if (str_contains($sure, 'saat')) {
                preg_match('/(\d+)\s*saat(?:\s*(\d+)\s*dakika)?/', $sure, $matches);
                $saat = (int)($matches[1] ?? 0);
                $dakika = (int)($matches[2] ?? 0);
                return ($saat * 60) + $dakika;
            } elseif (str_contains($sure, 'dakika')) {
                preg_match('/(\d+)\s*dakika/', $sure, $matches);
                return (int)($matches[1] ?? 0);
            }
            return 0;
        });

        return $this->formatDuration($toplamDakika);
    }
}