<?php

namespace App\Services;

use App\Models\MusteriTalep;
use App\Models\TalepPortfoyEslestirme;
use App\Models\User;
use App\Notifications\TalepEslestirmeBildirim;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;

/**
 * Eşleştirme Bildirim Servisi
 * 
 * Bu servis eşleştirme ile ilgili bildirimleri yönetir.
 */
class EslestirmeBildirimService
{
    /**
     * Yeni eşleştirme bildirimi gönder
     */
    public function yeniEslestirmeBildirimi(MusteriTalep $talep, Collection $eslestirmeler): void
    {
        $sorumluPersonel = $talep->sorumluPersonel;
        if (!$sorumluPersonel || !$sorumluPersonel->user) {
            return;
        }

        // Yüksek skorlu eşleştirme var mı kontrol et
        $yuksekSkorluEslestirme = $eslestirmeler->where('eslestirme_skoru', '>=', 0.8)->first();
        
        if ($yuksekSkorluEslestirme) {
            // Yüksek skorlu eşleştirme bildirimi
            $sorumluPersonel->user->notify(new TalepEslestirmeBildirim(
                'yuksek_skorlu_eslestirme',
                $talep,
                $yuksekSkorluEslestirme,
                [
                    'toplam_eslestirme_sayisi' => $eslestirmeler->count(),
                    'yuksek_skorlu_sayisi' => $eslestirmeler->where('eslestirme_skoru', '>=', 0.8)->count(),
                ]
            ));
        } else {
            // Normal yeni eşleştirme bildirimi
            $sorumluPersonel->user->notify(new TalepEslestirmeBildirim(
                'yeni_eslestirme',
                $talep,
                null,
                [
                    'eslestirme_sayisi' => $eslestirmeler->count(),
                    'en_yuksek_skor' => $eslestirmeler->max('eslestirme_skoru'),
                ]
            ));
        }

        // Yöneticilere de bildir (isteğe bağlı)
        $this->yoneticilereBildir('yeni_eslestirme', $talep, null, [
            'eslestirme_sayisi' => $eslestirmeler->count(),
            'sorumlu_personel' => $sorumluPersonel->ad . ' ' . $sorumluPersonel->soyad,
        ]);
    }

    /**
     * Eşleştirme sunuldu bildirimi
     */
    public function eslestirmeSunulduBildirimi(TalepPortfoyEslestirme $eslestirme): void
    {
        $talep = $eslestirme->talep;
        $sorumluPersonel = $talep->sorumluPersonel;
        
        if (!$sorumluPersonel || !$sorumluPersonel->user) {
            return;
        }

        $sorumluPersonel->user->notify(new TalepEslestirmeBildirim(
            'eslestirme_sunuldu',
            $talep,
            $eslestirme
        ));

        // Sunan personele de bildir (farklı personelse)
        $sunanPersonel = $eslestirme->sunanPersonel;
        if ($sunanPersonel && $sunanPersonel->user && $sunanPersonel->id !== $sorumluPersonel->id) {
            $sunanPersonel->user->notify(new TalepEslestirmeBildirim(
                'eslestirme_sunuldu',
                $talep,
                $eslestirme
            ));
        }
    }

    /**
     * Eşleştirme kabul bildirimi
     */
    public function eslestirmeKabulBildirimi(TalepPortfoyEslestirme $eslestirme): void
    {
        $talep = $eslestirme->talep;
        
        // Sorumlu personele bildir
        if ($talep->sorumluPersonel?->user) {
            $talep->sorumluPersonel->user->notify(new TalepEslestirmeBildirim(
                'eslestirme_kabul',
                $talep,
                $eslestirme
            ));
        }

        // Sunan personele bildir
        if ($eslestirme->sunanPersonel?->user) {
            $eslestirme->sunanPersonel->user->notify(new TalepEslestirmeBildirim(
                'eslestirme_kabul',
                $talep,
                $eslestirme
            ));
        }

        // Yöneticilere başarı bildirimi
        $this->yoneticilereBildir('eslestirme_kabul', $talep, $eslestirme, [
            'basari_orani_artisi' => true,
        ]);
    }

    /**
     * Eşleştirme red bildirimi
     */
    public function eslestirmeRedBildirimi(TalepPortfoyEslestirme $eslestirme): void
    {
        $talep = $eslestirme->talep;
        
        // Sorumlu personele bildir
        if ($talep->sorumluPersonel?->user) {
            $talep->sorumluPersonel->user->notify(new TalepEslestirmeBildirim(
                'eslestirme_red',
                $talep,
                $eslestirme
            ));
        }

        // Sunan personele bildir
        if ($eslestirme->sunanPersonel?->user) {
            $eslestirme->sunanPersonel->user->notify(new TalepEslestirmeBildirim(
                'eslestirme_red',
                $talep,
                $eslestirme
            ));
        }
    }

    /**
     * Talep güncellendi bildirimi
     */
    public function talepGuncellendiBildirimi(MusteriTalep $talep, array $guncellenenAlanlar): void
    {
        $sorumluPersonel = $talep->sorumluPersonel;
        if (!$sorumluPersonel || !$sorumluPersonel->user) {
            return;
        }

        $sorumluPersonel->user->notify(new TalepEslestirmeBildirim(
            'talep_guncellendi',
            $talep,
            null,
            [
                'guncellenen_alanlar' => $guncellenenAlanlar,
                'otomatik_eslestirme_baslatildi' => true,
            ]
        ));
    }

    /**
     * Toplu bildirim gönder
     */
    public function topluBildirimGonder(string $tip, Collection $talepler, array $ekstraBilgi = []): void
    {
        foreach ($talepler as $talep) {
            $sorumluPersonel = $talep->sorumluPersonel;
            if (!$sorumluPersonel || !$sorumluPersonel->user) {
                continue;
            }

            $sorumluPersonel->user->notify(new TalepEslestirmeBildirim(
                $tip,
                $talep,
                null,
                $ekstraBilgi
            ));
        }
    }

    /**
     * Günlük eşleştirme özeti gönder
     */
    public function gunlukEslestirmeOzeti(): void
    {
        $bugun = now()->startOfDay();
        
        // Bugün oluşturulan eşleştirmeler
        $bugunEslestirmeler = TalepPortfoyEslestirme::where('olusturma_tarihi', '>=', $bugun)->get();
        
        if ($bugunEslestirmeler->isEmpty()) {
            return;
        }

        $ozet = [
            'toplam_eslestirme' => $bugunEslestirmeler->count(),
            'yuksek_skorlu' => $bugunEslestirmeler->where('eslestirme_skoru', '>=', 0.8)->count(),
            'sunulmus' => $bugunEslestirmeler->where('durum', 'sunuldu')->count(),
            'kabul_edilmis' => $bugunEslestirmeler->where('durum', 'kabul_edildi')->count(),
            'ortalama_skor' => $bugunEslestirmeler->avg('eslestirme_skoru'),
        ];

        // Yöneticilere günlük özet gönder
        $yoneticiler = $this->getYoneticiler();
        
        Notification::send($yoneticiler, new TalepEslestirmeBildirim(
            'gunluk_ozet',
            $bugunEslestirmeler->first()->talep, // Örnek talep
            null,
            $ozet
        ));
    }

    /**
     * Yöneticilere bildir
     */
    protected function yoneticilereBildir(string $tip, MusteriTalep $talep, ?TalepPortfoyEslestirme $eslestirme = null, array $ekstraBilgi = []): void
    {
        $yoneticiler = $this->getYoneticiler();
        
        if ($yoneticiler->isEmpty()) {
            return;
        }

        Notification::send($yoneticiler, new TalepEslestirmeBildirim(
            $tip,
            $talep,
            $eslestirme,
            $ekstraBilgi
        ));
    }

    /**
     * Yöneticileri getir
     */
    protected function getYoneticiler(): Collection
    {
        // Yönetici rolündeki kullanıcıları getir
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'manager', 'supervisor']);
        })->get();
    }

    /**
     * Bildirim tercihlerini kontrol et
     */
    protected function bildirimGonderilsinMi(User $user, string $bildirimTipi): bool
    {
        $tercihler = $user->bildirim_tercihleri ?? [];
        
        // Varsayılan olarak tüm bildirimleri gönder
        return $tercihler[$bildirimTipi] ?? true;
    }

    /**
     * Acil bildirim gönder (SMS, Push vs.)
     */
    public function acilBildirimGonder(MusteriTalep $talep, string $mesaj): void
    {
        $sorumluPersonel = $talep->sorumluPersonel;
        if (!$sorumluPersonel || !$sorumluPersonel->user) {
            return;
        }

        // Acil bildirimler için farklı kanallar kullanılabilir
        // SMS, Push notification, WhatsApp vs.
        
        $sorumluPersonel->user->notify(new TalepEslestirmeBildirim(
            'acil_bildirim',
            $talep,
            null,
            [
                'mesaj' => $mesaj,
                'acil' => true,
                'gonderim_zamani' => now()->toISOString(),
            ]
        ));
    }
}