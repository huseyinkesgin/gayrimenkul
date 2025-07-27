<?php

namespace App\Traits;

use App\Services\TalepEslestirmeService;
use App\Jobs\TalepEslestirmeJob;
use Illuminate\Database\Eloquent\Collection;

/**
 * Talep Eşleştirme Trait'i
 * 
 * Bu trait MusteriTalep ve BaseMulk modellerine eşleştirme yetenekleri ekler.
 */
trait HasTalepEslestirme
{
    /**
     * Bu talep için eşleştirme yap
     */
    public function eslestirmeYap(bool $otomatikKaydet = true): Collection
    {
        $eslestirmeService = app(TalepEslestirmeService::class);
        return $eslestirmeService->talepIcinEslestirmeYap($this, $otomatikKaydet);
    }

    /**
     * Bu talep için asenkron eşleştirme başlat
     */
    public function asenkronEslestirmeBaslat(): void
    {
        TalepEslestirmeJob::dispatch($this->id);
    }

    /**
     * Bu mülk için uygun talepleri bul
     */
    public function uygunTalepleri(): Collection
    {
        $eslestirmeService = app(TalepEslestirmeService::class);
        return $eslestirmeService->mulkIcinUygunTalepleri($this);
    }

    /**
     * Eşleştirme skorunu hesapla
     */
    public function eslestirmeSkoruHesapla($diger): float
    {
        $eslestirmeService = app(TalepEslestirmeService::class);
        
        if ($this instanceof \App\Models\MusteriTalep) {
            return $eslestirmeService->eslestirmeSkoruHesapla($this, $diger);
        } else {
            return $eslestirmeService->eslestirmeSkoruHesapla($diger, $this);
        }
    }

    /**
     * En iyi eşleştirmeleri al
     */
    public function enIyiEslestirmeleri(int $limit = 5): Collection
    {
        if ($this instanceof \App\Models\MusteriTalep) {
            return $this->aktifEslestirmeler()
                ->orderByDesc('eslestirme_skoru')
                ->limit($limit)
                ->get();
        } else {
            return $this->talepEslestirmeleri()
                ->where('aktif_mi', true)
                ->orderByDesc('eslestirme_skoru')
                ->limit($limit)
                ->get();
        }
    }

    /**
     * Yüksek skorlu eşleştirmeleri al
     */
    public function yuksekSkorluEslestirmeleri(float $minSkor = 0.7): Collection
    {
        if ($this instanceof \App\Models\MusteriTalep) {
            return $this->aktifEslestirmeler()
                ->where('eslestirme_skoru', '>=', $minSkor)
                ->orderByDesc('eslestirme_skoru')
                ->get();
        } else {
            return $this->talepEslestirmeleri()
                ->where('aktif_mi', true)
                ->where('eslestirme_skoru', '>=', $minSkor)
                ->orderByDesc('eslestirme_skoru')
                ->get();
        }
    }

    /**
     * Eşleştirme istatistikleri
     */
    public function eslestirmeIstatistikleri(): array
    {
        if ($this instanceof \App\Models\MusteriTalep) {
            $eslestirmeler = $this->aktifEslestirmeler;
            
            return [
                'toplam_eslestirme' => $eslestirmeler->count(),
                'yuksek_skorlu' => $eslestirmeler->where('eslestirme_skoru', '>=', 0.8)->count(),
                'orta_skorlu' => $eslestirmeler->whereBetween('eslestirme_skoru', [0.5, 0.79])->count(),
                'dusuk_skorlu' => $eslestirmeler->where('eslestirme_skoru', '<', 0.5)->count(),
                'sunulmus' => $eslestirmeler->whereIn('durum', ['sunuldu', 'kabul_edildi', 'reddedildi'])->count(),
                'bekleyen' => $eslestirmeler->whereIn('durum', ['yeni', 'incelendi'])->count(),
                'en_yuksek_skor' => $eslestirmeler->max('eslestirme_skoru'),
                'ortalama_skor' => $eslestirmeler->avg('eslestirme_skoru'),
            ];
        } else {
            $eslestirmeler = $this->talepEslestirmeleri()->where('aktif_mi', true)->get();
            
            return [
                'ilgilenen_talep_sayisi' => $eslestirmeler->count(),
                'yuksek_skorlu' => $eslestirmeler->where('eslestirme_skoru', '>=', 0.8)->count(),
                'sunulmus' => $eslestirmeler->whereIn('durum', ['sunuldu', 'kabul_edildi', 'reddedildi'])->count(),
                'bekleyen' => $eslestirmeler->whereIn('durum', ['yeni', 'incelendi'])->count(),
                'en_yuksek_skor' => $eslestirmeler->max('eslestirme_skoru'),
                'ortalama_skor' => $eslestirmeler->avg('eslestirme_skoru'),
            ];
        }
    }

    /**
     * Eşleştirme önerisi var mı?
     */
    public function eslestirmeOnerisiVarMi(float $minSkor = 0.7): bool
    {
        if ($this instanceof \App\Models\MusteriTalep) {
            return $this->aktifEslestirmeler()
                ->where('eslestirme_skoru', '>=', $minSkor)
                ->exists();
        } else {
            return $this->talepEslestirmeleri()
                ->where('aktif_mi', true)
                ->where('eslestirme_skoru', '>=', $minSkor)
                ->exists();
        }
    }

    /**
     * Son eşleştirme tarihi
     */
    public function sonEslestirmeTarihi(): ?string
    {
        if ($this instanceof \App\Models\MusteriTalep) {
            $sonEslestirme = $this->eslestirmeler()
                ->orderByDesc('olusturma_tarihi')
                ->first();
        } else {
            $sonEslestirme = $this->talepEslestirmeleri()
                ->orderByDesc('olusturma_tarihi')
                ->first();
        }

        return $sonEslestirme?->olusturma_tarihi?->format('d.m.Y H:i');
    }

    /**
     * Eşleştirme gerekli mi kontrol et
     */
    public function eslestirmeGerekliMi(): bool
    {
        if (!($this instanceof \App\Models\MusteriTalep)) {
            return false;
        }

        // Aktif olmayan talepler için eşleştirme gerekmez
        if (!$this->durum->isAktif()) {
            return false;
        }

        // Son 7 gün içinde eşleştirme yapılmışsa gerekli değil
        $sonEslestirme = $this->eslestirmeler()
            ->where('olusturma_tarihi', '>=', now()->subDays(7))
            ->exists();

        if ($sonEslestirme) {
            return false;
        }

        // Son aktivite 24 saatten eskiyse eşleştirme gerekli
        $sonAktivite = $this->son_aktivite_tarihi ?? $this->created_at;
        return $sonAktivite->diffInHours(now()) >= 24;
    }

    /**
     * Otomatik eşleştirme tetikleyicileri
     */
    public function otomatikEslestirmeTetikle(): void
    {
        if (!($this instanceof \App\Models\MusteriTalep)) {
            return;
        }

        // Sadece aktif talepler için otomatik eşleştirme
        if (!$this->durum->isAktif()) {
            return;
        }

        // Eşleştirme gerekli değilse çık
        if (!$this->eslestirmeGerekliMi()) {
            return;
        }

        // Asenkron eşleştirme başlat
        $this->asenkronEslestirmeBaslat();

        // Talep aktivitesi ekle
        $this->aktiviteEkle('otomatik_eslestirme_tetiklendi', [
            'tetiklenme_nedeni' => 'Talep güncellendi',
            'tetiklenme_tarihi' => now()->toISOString(),
        ]);
    }
}