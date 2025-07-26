<?php

namespace App\Services;

use App\Models\MusteriMulkIliskisi;
use App\Models\Musteri\Musteri;
use App\Models\Mulk\BaseMulk;
use App\Enums\IliskiTipi;
use App\Enums\IliskiDurumu;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class IliskiTakipService
{
    /**
     * İlişki istatistikleri
     */
    public function getIliskiIstatistikleri(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriMulkIliskisi::query(), $filters);

        $toplamIliski = $query->count();
        $aktifIliski = $query->clone()->active()->count();
        $tamamlanmisIliski = $query->clone()->completed()->count();
        $yuksekOncelikliIliski = $query->clone()->highPriority()->count();

        $ortalamaSkor = $query->clone()->avg('ilgi_seviyesi');
        $toplamTeklifMiktari = $query->clone()->whereNotNull('teklif_miktari')->sum('teklif_miktari');

        return [
            'toplam_iliski' => $toplamIliski,
            'aktif_iliski' => $aktifIliski,
            'tamamlanmis_iliski' => $tamamlanmisIliski,
            'yuksek_oncelikli_iliski' => $yuksekOncelikliIliski,
            'aktif_oran' => $toplamIliski > 0 ? round(($aktifIliski / $toplamIliski) * 100, 2) : 0,
            'tamamlanma_oran' => $toplamIliski > 0 ? round(($tamamlanmisIliski / $toplamIliski) * 100, 2) : 0,
            'ortalama_ilgi_seviyesi' => round($ortalamaSkor ?? 0, 2),
            'toplam_teklif_miktari' => $toplamTeklifMiktari,
            'ortalama_teklif_miktari' => $aktifIliski > 0 ? round($toplamTeklifMiktari / $aktifIliski, 2) : 0,
        ];
    }

    /**
     * İlişki tipi dağılımı
     */
    public function getIliskiTipiDagilimi(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriMulkIliskisi::query(), $filters);

        $dagilim = $query->selectRaw('iliski_tipi, COUNT(*) as sayi')
            ->groupBy('iliski_tipi')
            ->orderByDesc('sayi')
            ->get()
            ->map(function ($item) {
                $iliskiTipi = IliskiTipi::fromValue($item->iliski_tipi);
                return [
                    'tip' => $item->iliski_tipi,
                    'label' => $iliskiTipi?->label() ?? 'Bilinmiyor',
                    'sayi' => $item->sayi,
                    'renk' => $iliskiTipi?->color() ?? 'gray',
                    'ikon' => $iliskiTipi?->icon() ?? 'heroicon-o-question-mark-circle',
                ];
            });

        $toplam = $dagilim->sum('sayi');

        return $dagilim->map(function ($item) use ($toplam) {
            $item['yuzde'] = $toplam > 0 ? round(($item['sayi'] / $toplam) * 100, 2) : 0;
            return $item;
        })->toArray();
    }

    /**
     * İlişki durumu dağılımı
     */
    public function getIliskiDurumuDagilimi(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriMulkIliskisi::query(), $filters);

        $dagilim = $query->selectRaw('durum, COUNT(*) as sayi')
            ->groupBy('durum')
            ->orderByDesc('sayi')
            ->get()
            ->map(function ($item) {
                $durum = IliskiDurumu::fromValue($item->durum);
                return [
                    'durum' => $item->durum,
                    'label' => $durum?->label() ?? 'Bilinmiyor',
                    'sayi' => $item->sayi,
                    'renk' => $durum?->color() ?? 'gray',
                    'ikon' => $durum?->icon() ?? 'heroicon-o-question-mark-circle',
                ];
            });

        $toplam = $dagilim->sum('sayi');

        return $dagilim->map(function ($item) use ($toplam) {
            $item['yuzde'] = $toplam > 0 ? round(($item['sayi'] / $toplam) * 100, 2) : 0;
            return $item;
        })->toArray();
    }

    /**
     * İlgi seviyesi dağılımı
     */
    public function getIlgiSeviyesiDagilimi(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriMulkIliskisi::query(), $filters);

        $dagilim = $query->selectRaw('
                CASE 
                    WHEN ilgi_seviyesi >= 9 THEN "Çok Yüksek (9-10)"
                    WHEN ilgi_seviyesi >= 7 THEN "Yüksek (7-8)"
                    WHEN ilgi_seviyesi >= 5 THEN "Orta (5-6)"
                    WHEN ilgi_seviyesi >= 3 THEN "Düşük (3-4)"
                    ELSE "Çok Düşük (1-2)"
                END as seviye,
                COUNT(*) as sayi,
                AVG(ilgi_seviyesi) as ortalama
            ')
            ->groupBy('seviye')
            ->orderByDesc('ortalama')
            ->get()
            ->map(function ($item) {
                $renk = match ($item->seviye) {
                    'Çok Yüksek (9-10)' => 'emerald',
                    'Yüksek (7-8)' => 'green',
                    'Orta (5-6)' => 'yellow',
                    'Düşük (3-4)' => 'orange',
                    'Çok Düşük (1-2)' => 'red',
                    default => 'gray'
                };

                return [
                    'seviye' => $item->seviye,
                    'sayi' => $item->sayi,
                    'ortalama' => round($item->ortalama, 2),
                    'renk' => $renk,
                ];
            });

        $toplam = $dagilim->sum('sayi');

        return $dagilim->map(function ($item) use ($toplam) {
            $item['yuzde'] = $toplam > 0 ? round(($item['sayi'] / $toplam) * 100, 2) : 0;
            return $item;
        })->toArray();
    }

    /**
     * Personel performansı
     */
    public function getPersonelPerformansi(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriMulkIliskisi::query(), $filters);

        return $query->with('sorumluPersonel')
            ->whereNotNull('sorumlu_personel_id')
            ->selectRaw('
                sorumlu_personel_id,
                COUNT(*) as toplam_iliski,
                COUNT(CASE WHEN durum IN (?) THEN 1 END) as aktif_iliski,
                COUNT(CASE WHEN durum IN (?) THEN 1 END) as tamamlanmis_iliski,
                AVG(ilgi_seviyesi) as ortalama_ilgi_seviyesi,
                SUM(teklif_miktari) as toplam_teklif_miktari
            ', [
                array_map(fn($s) => $s->value, IliskiDurumu::activeStates()),
                array_map(fn($s) => $s->value, IliskiDurumu::completedStates())
            ])
            ->groupBy('sorumlu_personel_id')
            ->orderByDesc('toplam_iliski')
            ->get()
            ->map(function ($item) {
                $basariOrani = $item->toplam_iliski > 0 
                    ? round(($item->tamamlanmis_iliski / $item->toplam_iliski) * 100, 2) 
                    : 0;

                return [
                    'personel_id' => $item->sorumlu_personel_id,
                    'personel_adi' => $item->sorumluPersonel?->name ?? 'Bilinmiyor',
                    'toplam_iliski' => $item->toplam_iliski,
                    'aktif_iliski' => $item->aktif_iliski,
                    'tamamlanmis_iliski' => $item->tamamlanmis_iliski,
                    'basari_orani' => $basariOrani,
                    'ortalama_ilgi_seviyesi' => round($item->ortalama_ilgi_seviyesi ?? 0, 2),
                    'toplam_teklif_miktari' => $item->toplam_teklif_miktari ?? 0,
                    'ortalama_teklif_miktari' => $item->aktif_iliski > 0 
                        ? round(($item->toplam_teklif_miktari ?? 0) / $item->aktif_iliski, 2) 
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Mülk bazlı ilişki analizi
     */
    public function getMulkBazliAnaliz(string $mulkId, array $filters = []): array
    {
        $filters['mulk_id'] = $mulkId;
        $query = $this->applyFilters(MusteriMulkIliskisi::query(), $filters);

        $iliskiler = $query->with(['musteri', 'sorumluPersonel'])
            ->orderByDesc('oncelik')
            ->get()
            ->map(function ($iliski) {
                return [
                    'id' => $iliski->id,
                    'musteri' => $iliski->musteri?->display_name,
                    'tip' => $iliski->iliski_tipi_label,
                    'tip_rengi' => $iliski->iliski_tipi_color,
                    'durum' => $iliski->durum_label,
                    'durum_rengi' => $iliski->durum_color,
                    'ilgi_seviyesi' => $iliski->ilgi_seviyesi,
                    'ilgi_rengi' => $iliski->ilgi_seviyesi_color,
                    'teklif' => $iliski->formatted_teklif_miktari,
                    'sure' => $iliski->formatted_iliski_suresi,
                    'son_aktivite' => $iliski->formatted_son_aktivite_suresi,
                    'sorumlu_personel' => $iliski->sorumluPersonel?->name,
                    'oncelik' => $iliski->oncelik,
                    'skor' => $iliski->iliski_skoru,
                ];
            });

        $istatistikler = [
            'toplam_iliski' => $iliskiler->count(),
            'aktif_iliski' => $iliskiler->where('durum', 'Aktif')->count(),
            'en_yuksek_teklif' => $this->getEnYuksekTeklif($iliskiler),
            'ortalama_ilgi_seviyesi' => $iliskiler->avg('ilgi_seviyesi'),
            'en_son_aktivite' => $this->getEnSonAktivite($iliskiler),
        ];

        return [
            'iliskiler' => $iliskiler->toArray(),
            'istatistikler' => $istatistikler,
        ];
    }

    /**
     * Müşteri bazlı ilişki geçmişi
     */
    public function getMusteriBazliGecmis(string $musteriId, array $filters = []): array
    {
        $filters['musteri_id'] = $musteriId;
        $query = $this->applyFilters(MusteriMulkIliskisi::query(), $filters);

        $iliskiler = $query->with(['mulk', 'sorumluPersonel'])
            ->orderByDesc('baslangic_tarihi')
            ->get()
            ->map(function ($iliski) {
                return [
                    'id' => $iliski->id,
                    'mulk' => $iliski->mulk?->baslik,
                    'mulk_tipi' => $iliski->mulk?->getMulkType(),
                    'tip' => $iliski->iliski_tipi_label,
                    'tip_rengi' => $iliski->iliski_tipi_color,
                    'durum' => $iliski->durum_label,
                    'durum_rengi' => $iliski->durum_color,
                    'baslangic_tarihi' => $iliski->baslangic_tarihi?->format('d.m.Y'),
                    'bitis_tarihi' => $iliski->bitis_tarihi?->format('d.m.Y'),
                    'ilgi_seviyesi' => $iliski->ilgi_seviyesi,
                    'teklif' => $iliski->formatted_teklif_miktari,
                    'sure' => $iliski->formatted_iliski_suresi,
                    'sorumlu_personel' => $iliski->sorumluPersonel?->name,
                    'skor' => $iliski->iliski_skoru,
                ];
            });

        $istatistikler = [
            'toplam_iliski' => $iliskiler->count(),
            'aktif_iliski' => $iliskiler->where('durum', 'Aktif')->count(),
            'tamamlanmis_iliski' => $iliskiler->where('durum', 'Tamamlandı')->count(),
            'ortalama_ilgi_seviyesi' => $iliskiler->avg('ilgi_seviyesi'),
            'toplam_teklif_sayisi' => $iliskiler->where('teklif', '!=', 'Teklif Verilmemiş')->count(),
            'en_uzun_iliski' => $this->getEnUzunIliski($iliskiler),
        ];

        return [
            'iliskiler' => $iliskiler->toArray(),
            'istatistikler' => $istatistikler,
        ];
    }

    /**
     * Takip gereken ilişkiler
     */
    public function getTakipGerekenIliskiler(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriMulkIliskisi::query(), $filters);

        // Farklı takip kategorileri
        $kategoriler = [
            'geciken_kararlar' => $query->clone()->overdue(),
            'uzun_sure_aktivite_yok' => $query->clone()->stale(14),
            'yuksek_oncelikli' => $query->clone()->highPriority()->active(),
            'karar_tarihi_yaklasan' => $query->clone()->decisionDue(7),
        ];

        $sonuclar = [];
        foreach ($kategoriler as $kategori => $categoryQuery) {
            $iliskiler = $categoryQuery->with(['musteri', 'mulk', 'sorumluPersonel'])
                ->orderByDesc('oncelik')
                ->limit(10)
                ->get()
                ->map(function ($iliski) {
                    return $iliski->getStatusSummary();
                });

            $sonuclar[$kategori] = [
                'baslik' => $this->getKategoriBasligi($kategori),
                'sayi' => $iliskiler->count(),
                'iliskiler' => $iliskiler->toArray(),
            ];
        }

        return $sonuclar;
    }

    /**
     * Zaman bazlı analiz
     */
    public function getZamanBazliAnaliz(array $filters = []): array
    {
        $query = $this->applyFilters(MusteriMulkIliskisi::query(), $filters);

        // Aylık yeni ilişkiler
        $aylikYeniIliskiler = $query->clone()
            ->selectRaw('DATE_FORMAT(baslangic_tarihi, "%Y-%m") as ay, COUNT(*) as sayi')
            ->whereNotNull('baslangic_tarihi')
            ->groupBy('ay')
            ->orderBy('ay')
            ->get()
            ->map(function ($item) {
                return [
                    'ay' => Carbon::createFromFormat('Y-m', $item->ay)->format('M Y'),
                    'sayi' => $item->sayi,
                ];
            });

        // Aylık tamamlanan ilişkiler
        $aylikTamamlananIliskiler = $query->clone()
            ->selectRaw('DATE_FORMAT(bitis_tarihi, "%Y-%m") as ay, COUNT(*) as sayi')
            ->whereNotNull('bitis_tarihi')
            ->groupBy('ay')
            ->orderBy('ay')
            ->get()
            ->map(function ($item) {
                return [
                    'ay' => Carbon::createFromFormat('Y-m', $item->ay)->format('M Y'),
                    'sayi' => $item->sayi,
                ];
            });

        // İlişki süresi analizi
        $sureDagilimi = $query->clone()
            ->whereNotNull('baslangic_tarihi')
            ->get()
            ->groupBy(function ($iliski) {
                $sure = $iliski->iliski_suresi;
                if ($sure <= 7) return '1 hafta';
                if ($sure <= 30) return '1 ay';
                if ($sure <= 90) return '3 ay';
                if ($sure <= 180) return '6 ay';
                return '6+ ay';
            })
            ->map(function ($group, $kategori) {
                return [
                    'kategori' => $kategori,
                    'sayi' => $group->count(),
                    'ortalama_skor' => round($group->avg('iliski_skoru'), 2),
                ];
            })
            ->values();

        return [
            'aylik_yeni_iliskiler' => $aylikYeniIliskiler->toArray(),
            'aylik_tamamlanan_iliskiler' => $aylikTamamlananIliskiler->toArray(),
            'sure_dagilimi' => $sureDagilimi->toArray(),
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

        if (isset($filters['mulk_id'])) {
            $query->where('mulk_id', $filters['mulk_id']);
        }

        if (isset($filters['iliski_tipi'])) {
            $query->where('iliski_tipi', $filters['iliski_tipi']);
        }

        if (isset($filters['durum'])) {
            $query->where('durum', $filters['durum']);
        }

        if (isset($filters['sorumlu_personel_id'])) {
            $query->where('sorumlu_personel_id', $filters['sorumlu_personel_id']);
        }

        if (isset($filters['min_ilgi_seviyesi'])) {
            $query->where('ilgi_seviyesi', '>=', $filters['min_ilgi_seviyesi']);
        }

        if (isset($filters['max_ilgi_seviyesi'])) {
            $query->where('ilgi_seviyesi', '<=', $filters['max_ilgi_seviyesi']);
        }

        if (isset($filters['baslangic_tarihi'])) {
            $query->where('baslangic_tarihi', '>=', $filters['baslangic_tarihi']);
        }

        if (isset($filters['bitis_tarihi'])) {
            $query->where('baslangic_tarihi', '<=', $filters['bitis_tarihi']);
        }

        if (isset($filters['etiket'])) {
            $query->whereJsonContains('etiketler', $filters['etiket']);
        }

        return $query;
    }

    /**
     * En yüksek teklifi bul
     */
    private function getEnYuksekTeklif(Collection $iliskiler): ?array
    {
        $enYuksek = $iliskiler->where('teklif', '!=', 'Teklif Verilmemiş')
            ->sortByDesc(function ($iliski) {
                // Teklif string'ini sayıya çevir
                $teklif = str_replace(['.', '₺', '$', '€', ' '], '', $iliski['teklif']);
                return is_numeric($teklif) ? (float) $teklif : 0;
            })
            ->first();

        return $enYuksek ? [
            'musteri' => $enYuksek['musteri'],
            'teklif' => $enYuksek['teklif'],
        ] : null;
    }

    /**
     * En son aktiviteyi bul
     */
    private function getEnSonAktivite(Collection $iliskiler): ?string
    {
        return $iliskiler->min('son_aktivite');
    }

    /**
     * En uzun ilişkiyi bul
     */
    private function getEnUzunIliski(Collection $iliskiler): ?array
    {
        $enUzun = $iliskiler->sortByDesc('sure')->first();

        return $enUzun ? [
            'mulk' => $enUzun['mulk'],
            'sure' => $enUzun['sure'],
        ] : null;
    }

    /**
     * Kategori başlığını döndür
     */
    private function getKategoriBasligi(string $kategori): string
    {
        return match ($kategori) {
            'geciken_kararlar' => 'Karar Tarihi Geçmiş İlişkiler',
            'uzun_sure_aktivite_yok' => 'Uzun Süredir Aktivite Olmayan İlişkiler',
            'yuksek_oncelikli' => 'Yüksek Öncelikli Aktif İlişkiler',
            'karar_tarihi_yaklasan' => 'Karar Tarihi Yaklaşan İlişkiler',
            default => 'Bilinmeyen Kategori',
        };
    }
}