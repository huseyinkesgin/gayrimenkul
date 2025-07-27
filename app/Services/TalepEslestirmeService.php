<?php

namespace App\Services;

use App\Models\MusteriTalep;
use App\Models\TalepPortfoyEslestirme;
use App\Models\Mulk\BaseMulk;
use App\Enums\MulkKategorisi;
use App\Enums\TalepDurumu;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\EslestirmeBildirimService;

/**
 * Talep Eşleştirme Servisi
 * 
 * Bu servis müşteri taleplerini portföy ile eşleştiren algoritmaları içerir.
 */
class TalepEslestirmeService
{
    /**
     * Minimum eşleştirme skoru (0.0 - 1.0)
     */
    const MIN_ESLESTIRME_SKORU = 0.3;

    /**
     * Maksimum eşleştirme sayısı (bir talep için)
     */
    const MAX_ESLESTIRME_SAYISI = 20;

    /**
     * Eşleştirme ağırlıkları
     */
    const AGIRLIKLAR = [
        'fiyat' => 0.30,        // %30 - En önemli kriter
        'metrekare' => 0.25,    // %25 - İkinci önemli kriter
        'lokasyon' => 0.20,     // %20 - Üçüncü önemli kriter
        'ozellikler' => 0.15,   // %15 - Özellik uyumu
        'kategori' => 0.10,     // %10 - Kategori uyumu
    ];

    public function __construct(
        protected EslestirmeBildirimService $bildirimService
    ) {}

    /**
     * Belirli bir talep için eşleştirme yap
     */
    public function talepIcinEslestirmeYap(MusteriTalep $talep, bool $otomatikKaydet = true): Collection
    {
        Log::info("Talep eşleştirme başlatıldı", ['talep_id' => $talep->id]);

        // Aktif olmayan talepler için eşleştirme yapma
        if (!$talep->durum->isAktif()) {
            Log::warning("Pasif talep için eşleştirme yapılamaz", ['talep_id' => $talep->id, 'durum' => $talep->durum->value]);
            return collect();
        }

        // Uygun mülkleri bul
        $uygunMulkler = $this->uygunMulkleriGetir($talep);
        
        if ($uygunMulkler->isEmpty()) {
            Log::info("Talep için uygun mülk bulunamadı", ['talep_id' => $talep->id]);
            return collect();
        }

        // Her mülk için eşleştirme skoru hesapla
        $eslestirmeler = collect();
        
        foreach ($uygunMulkler as $mulk) {
            $skor = $this->eslestirmeSkoruHesapla($talep, $mulk);
            
            if ($skor >= self::MIN_ESLESTIRME_SKORU) {
                $eslestirmeDetaylari = $this->eslestirmeDetaylariOlustur($talep, $mulk, $skor);
                
                $eslestirme = [
                    'talep_id' => $talep->id,
                    'mulk_id' => $mulk->id,
                    'mulk_type' => $mulk->getMulkType(),
                    'eslestirme_skoru' => $skor,
                    'eslestirme_detaylari' => $eslestirmeDetaylari,
                    'mulk' => $mulk,
                ];
                
                $eslestirmeler->push($eslestirme);
            }
        }

        // Skora göre sırala ve limit uygula
        $eslestirmeler = $eslestirmeler
            ->sortByDesc('eslestirme_skoru')
            ->take(self::MAX_ESLESTIRME_SAYISI);

        // Otomatik kaydet seçeneği aktifse veritabanına kaydet
        if ($otomatikKaydet && $eslestirmeler->isNotEmpty()) {
            $this->eslestirmeleriKaydet($eslestirmeler);
            
            // Eşleştirme bildirimi gönder
            $this->bildirimService->yeniEslestirmeBildirimi($talep, $eslestirmeler);
        }

        Log::info("Talep eşleştirme tamamlandı", [
            'talep_id' => $talep->id,
            'bulunan_eslestirme_sayisi' => $eslestirmeler->count()
        ]);

        return $eslestirmeler;
    }

    /**
     * Tüm aktif talepler için eşleştirme yap
     */
    public function tumTaleplerIcinEslestirmeYap(): array
    {
        $aktifTalepler = MusteriTalep::aktif()->get();
        $sonuclar = [];

        foreach ($aktifTalepler as $talep) {
            try {
                $eslestirmeler = $this->talepIcinEslestirmeYap($talep);
                $sonuclar[$talep->id] = [
                    'talep' => $talep,
                    'eslestirme_sayisi' => $eslestirmeler->count(),
                    'en_yuksek_skor' => $eslestirmeler->max('eslestirme_skoru'),
                ];
            } catch (\Exception $e) {
                Log::error("Talep eşleştirme hatası", [
                    'talep_id' => $talep->id,
                    'hata' => $e->getMessage()
                ]);
                
                $sonuclar[$talep->id] = [
                    'talep' => $talep,
                    'hata' => $e->getMessage(),
                ];
            }
        }

        return $sonuclar;
    }

    /**
     * Belirli bir mülk için uygun talepleri bul
     */
    public function mulkIcinUygunTalepleri(BaseMulk $mulk): Collection
    {
        $uygunTalepler = MusteriTalep::aktif()
            ->where('mulk_kategorisi', $mulk->getMulkKategorisi())
            ->get();

        $eslestirmeler = collect();

        foreach ($uygunTalepler as $talep) {
            $skor = $this->eslestirmeSkoruHesapla($talep, $mulk);
            
            if ($skor >= self::MIN_ESLESTIRME_SKORU) {
                $eslestirmeler->push([
                    'talep' => $talep,
                    'skor' => $skor,
                    'detaylar' => $this->eslestirmeDetaylariOlustur($talep, $mulk, $skor),
                ]);
            }
        }

        return $eslestirmeler->sortByDesc('skor');
    }

    /**
     * Talep için uygun mülkleri getir
     */
    protected function uygunMulkleriGetir(MusteriTalep $talep): Collection
    {
        $query = $this->getMulkQueryByKategori($talep->mulk_kategorisi);
        
        if (!$query) {
            return collect();
        }

        // Temel filtreler
        $query->aktifMulkler();

        // Fiyat filtresi
        if ($talep->min_fiyat || $talep->max_fiyat) {
            $query->fiyatAraliginda($talep->min_fiyat, $talep->max_fiyat);
        }

        // Metrekare filtresi
        if ($talep->min_m2 || $talep->max_m2) {
            $query->metrekareAraliginda($talep->min_m2, $talep->max_m2);
        }

        // Alt tip filtresi
        if ($talep->mulk_alt_tipi) {
            $query->where('mulk_type', 'LIKE', '%' . $talep->mulk_alt_tipi . '%');
        }

        // Lokasyon filtresi
        if (!empty($talep->lokasyon_tercihleri)) {
            $this->lokasyonFiltresiUygula($query, $talep->lokasyon_tercihleri);
        }

        return $query->get();
    }

    /**
     * Mülk kategorisine göre query oluştur
     */
    protected function getMulkQueryByKategori(MulkKategorisi $kategori)
    {
        return match($kategori) {
            MulkKategorisi::ARSA => \App\Models\Mulk\Arsa\Arsa::query(),
            MulkKategorisi::ISYERI => \App\Models\Mulk\Isyeri\Isyeri::query(),
            MulkKategorisi::KONUT => \App\Models\Mulk\Konut\Konut::query(),
            MulkKategorisi::TURISTIK_TESIS => \App\Models\Mulk\TuristikTesis\TuristikTesis::query(),
            default => null,
        };
    }

    /**
     * Lokasyon filtresi uygula
     */
    protected function lokasyonFiltresiUygula($query, array $lokasyonTercihleri): void
    {
        $query->where(function ($q) use ($lokasyonTercihleri) {
            foreach ($lokasyonTercihleri as $lokasyon) {
                $q->orWhere(function ($subQ) use ($lokasyon) {
                    if (isset($lokasyon['sehir_id'])) {
                        $subQ->whereHas('adresler', function ($adresQ) use ($lokasyon) {
                            $adresQ->where('sehir_id', $lokasyon['sehir_id']);
                            
                            if (isset($lokasyon['ilce_id'])) {
                                $adresQ->where('ilce_id', $lokasyon['ilce_id']);
                            }
                            
                            if (isset($lokasyon['semt_id'])) {
                                $adresQ->where('semt_id', $lokasyon['semt_id']);
                            }
                        });
                    }
                });
            }
        });
    }

    /**
     * Eşleştirme skoru hesapla
     */
    protected function eslestirmeSkoruHesapla(MusteriTalep $talep, BaseMulk $mulk): float
    {
        $skorlar = [];

        // Kategori skoru
        $skorlar['kategori'] = $this->kategoriSkoruHesapla($talep, $mulk);

        // Fiyat skoru
        $skorlar['fiyat'] = $this->fiyatSkoruHesapla($talep, $mulk);

        // Metrekare skoru
        $skorlar['metrekare'] = $this->metrekareSkoruHesapla($talep, $mulk);

        // Lokasyon skoru
        $skorlar['lokasyon'] = $this->lokasyonSkoruHesapla($talep, $mulk);

        // Özellik skoru
        $skorlar['ozellikler'] = $this->ozellikSkoruHesapla($talep, $mulk);

        // Ağırlıklı toplam hesapla
        $toplamSkor = 0;
        foreach ($skorlar as $kriter => $skor) {
            $toplamSkor += $skor * self::AGIRLIKLAR[$kriter];
        }

        return round($toplamSkor, 3);
    }

    /**
     * Kategori skoru hesapla
     */
    protected function kategoriSkoruHesapla(MusteriTalep $talep, BaseMulk $mulk): float
    {
        // Ana kategori uyumu
        if ($talep->mulk_kategorisi !== $mulk->getMulkKategorisi()) {
            return 0.0;
        }

        // Alt tip uyumu
        if ($talep->mulk_alt_tipi) {
            $mulkType = strtolower($mulk->getMulkType());
            $talepAltTip = strtolower($talep->mulk_alt_tipi);
            
            if (str_contains($mulkType, $talepAltTip) || str_contains($talepAltTip, $mulkType)) {
                return 1.0;
            } else {
                return 0.7; // Ana kategori uyuyor ama alt tip uymuyor
            }
        }

        return 1.0; // Ana kategori uyuyor, alt tip belirtilmemiş
    }

    /**
     * Fiyat skoru hesapla
     */
    protected function fiyatSkoruHesapla(MusteriTalep $talep, BaseMulk $mulk): float
    {
        if (!$mulk->fiyat) {
            return 0.5; // Fiyat belirtilmemiş
        }

        $mulkFiyat = $mulk->fiyat;
        
        // Talep fiyat aralığı yoksa orta skor ver
        if (!$talep->min_fiyat && !$talep->max_fiyat) {
            return 0.7;
        }

        // Tam aralık içindeyse maksimum skor
        if (($talep->min_fiyat === null || $mulkFiyat >= $talep->min_fiyat) &&
            ($talep->max_fiyat === null || $mulkFiyat <= $talep->max_fiyat)) {
            return 1.0;
        }

        // Aralık dışındaysa mesafeye göre skor hesapla
        $tolerans = 0.2; // %20 tolerans
        
        if ($talep->min_fiyat && $mulkFiyat < $talep->min_fiyat) {
            $fark = ($talep->min_fiyat - $mulkFiyat) / $talep->min_fiyat;
            return max(0, 1 - ($fark / $tolerans));
        }
        
        if ($talep->max_fiyat && $mulkFiyat > $talep->max_fiyat) {
            $fark = ($mulkFiyat - $talep->max_fiyat) / $talep->max_fiyat;
            return max(0, 1 - ($fark / $tolerans));
        }

        return 0.0;
    }

    /**
     * Metrekare skoru hesapla
     */
    protected function metrekareSkoruHesapla(MusteriTalep $talep, BaseMulk $mulk): float
    {
        if (!$mulk->metrekare) {
            return 0.5; // Metrekare belirtilmemiş
        }

        $mulkMetrekare = $mulk->metrekare;
        
        // Talep metrekare aralığı yoksa orta skor ver
        if (!$talep->min_m2 && !$talep->max_m2) {
            return 0.7;
        }

        // Tam aralık içindeyse maksimum skor
        if (($talep->min_m2 === null || $mulkMetrekare >= $talep->min_m2) &&
            ($talep->max_m2 === null || $mulkMetrekare <= $talep->max_m2)) {
            return 1.0;
        }

        // Aralık dışındaysa mesafeye göre skor hesapla
        $tolerans = 0.3; // %30 tolerans
        
        if ($talep->min_m2 && $mulkMetrekare < $talep->min_m2) {
            $fark = ($talep->min_m2 - $mulkMetrekare) / $talep->min_m2;
            return max(0, 1 - ($fark / $tolerans));
        }
        
        if ($talep->max_m2 && $mulkMetrekare > $talep->max_m2) {
            $fark = ($mulkMetrekare - $talep->max_m2) / $talep->max_m2;
            return max(0, 1 - ($fark / $tolerans));
        }

        return 0.0;
    }

    /**
     * Lokasyon skoru hesapla
     */
    protected function lokasyonSkoruHesapla(MusteriTalep $talep, BaseMulk $mulk): float
    {
        if (empty($talep->lokasyon_tercihleri)) {
            return 0.7; // Lokasyon tercihi yok
        }

        $mulkAdresleri = $mulk->adresler;
        if ($mulkAdresleri->isEmpty()) {
            return 0.3; // Mülkün adresi yok
        }

        $enYuksekSkor = 0;

        foreach ($talep->lokasyon_tercihleri as $tercih) {
            foreach ($mulkAdresleri as $adres) {
                $skor = $this->adresUyumSkoruHesapla($tercih, $adres);
                $enYuksekSkor = max($enYuksekSkor, $skor);
            }
        }

        return $enYuksekSkor;
    }

    /**
     * Adres uyum skoru hesapla
     */
    protected function adresUyumSkoruHesapla(array $tercih, $adres): float
    {
        $skor = 0;

        // Şehir uyumu (en önemli)
        if (isset($tercih['sehir_id']) && $adres->sehir_id == $tercih['sehir_id']) {
            $skor += 0.4;
            
            // İlçe uyumu
            if (isset($tercih['ilce_id']) && $adres->ilce_id == $tercih['ilce_id']) {
                $skor += 0.3;
                
                // Semt uyumu
                if (isset($tercih['semt_id']) && $adres->semt_id == $tercih['semt_id']) {
                    $skor += 0.3;
                }
            }
        }

        return min($skor, 1.0);
    }

    /**
     * Özellik skoru hesapla
     */
    protected function ozellikSkoruHesapla(MusteriTalep $talep, BaseMulk $mulk): float
    {
        if (empty($talep->ozellik_kriterleri)) {
            return 0.7; // Özellik kriteri yok
        }

        $mulkOzellikleri = $mulk->getPropertiesArray();
        if (empty($mulkOzellikleri)) {
            return 0.3; // Mülkün özellikleri yok
        }

        $toplamKriter = count($talep->ozellik_kriterleri);
        $uyumluKriter = 0;

        foreach ($talep->ozellik_kriterleri as $ozellikAdi => $arananDeger) {
            if (isset($mulkOzellikleri[$ozellikAdi])) {
                $mulkDegeri = $mulkOzellikleri[$ozellikAdi]['value'];
                
                if ($this->ozellikDegeriUyumluMu($arananDeger, $mulkDegeri)) {
                    $uyumluKriter++;
                }
            }
        }

        return $toplamKriter > 0 ? ($uyumluKriter / $toplamKriter) : 0.7;
    }

    /**
     * Özellik değeri uyumlu mu kontrol et
     */
    protected function ozellikDegeriUyumluMu($arananDeger, $mulkDegeri): bool
    {
        // Tam eşleşme
        if ($arananDeger === $mulkDegeri) {
            return true;
        }

        // String karşılaştırma (case insensitive)
        if (is_string($arananDeger) && is_string($mulkDegeri)) {
            return strtolower($arananDeger) === strtolower($mulkDegeri);
        }

        // Sayısal karşılaştırma (toleranslı)
        if (is_numeric($arananDeger) && is_numeric($mulkDegeri)) {
            $tolerans = 0.1; // %10 tolerans
            $fark = abs($arananDeger - $mulkDegeri) / max($arananDeger, $mulkDegeri);
            return $fark <= $tolerans;
        }

        // Array içinde arama
        if (is_array($mulkDegeri)) {
            return in_array($arananDeger, $mulkDegeri);
        }

        return false;
    }

    /**
     * Eşleştirme detayları oluştur
     */
    protected function eslestirmeDetaylariOlustur(MusteriTalep $talep, BaseMulk $mulk, float $toplamSkor): array
    {
        return [
            'toplam_skor' => $toplamSkor,
            'skor_detaylari' => [
                'kategori' => [
                    'skor' => $this->kategoriSkoruHesapla($talep, $mulk),
                    'agirlik' => self::AGIRLIKLAR['kategori'],
                    'aciklama' => 'Mülk kategorisi ve alt tip uyumu'
                ],
                'fiyat' => [
                    'skor' => $this->fiyatSkoruHesapla($talep, $mulk),
                    'agirlik' => self::AGIRLIKLAR['fiyat'],
                    'aciklama' => 'Fiyat aralığı uyumu',
                    'talep_aralik' => [
                        'min' => $talep->min_fiyat,
                        'max' => $talep->max_fiyat
                    ],
                    'mulk_fiyat' => $mulk->fiyat
                ],
                'metrekare' => [
                    'skor' => $this->metrekareSkoruHesapla($talep, $mulk),
                    'agirlik' => self::AGIRLIKLAR['metrekare'],
                    'aciklama' => 'Metrekare aralığı uyumu',
                    'talep_aralik' => [
                        'min' => $talep->min_m2,
                        'max' => $talep->max_m2
                    ],
                    'mulk_metrekare' => $mulk->metrekare
                ],
                'lokasyon' => [
                    'skor' => $this->lokasyonSkoruHesapla($talep, $mulk),
                    'agirlik' => self::AGIRLIKLAR['lokasyon'],
                    'aciklama' => 'Lokasyon tercihi uyumu'
                ],
                'ozellikler' => [
                    'skor' => $this->ozellikSkoruHesapla($talep, $mulk),
                    'agirlik' => self::AGIRLIKLAR['ozellikler'],
                    'aciklama' => 'Özellik kriterleri uyumu'
                ]
            ],
            'eslestirme_tarihi' => now()->toISOString(),
            'algoritma_versiyonu' => '1.0'
        ];
    }

    /**
     * Eşleştirmeleri veritabanına kaydet
     */
    protected function eslestirmeleriKaydet(Collection $eslestirmeler): void
    {
        DB::transaction(function () use ($eslestirmeler) {
            foreach ($eslestirmeler as $eslestirme) {
                // Mevcut eşleştirme var mı kontrol et
                $mevcutEslestirme = TalepPortfoyEslestirme::where('talep_id', $eslestirme['talep_id'])
                    ->where('mulk_id', $eslestirme['mulk_id'])
                    ->first();

                if ($mevcutEslestirme) {
                    // Mevcut eşleştirmeyi güncelle
                    $mevcutEslestirme->update([
                        'eslestirme_skoru' => $eslestirme['eslestirme_skoru'],
                        'eslestirme_detaylari' => $eslestirme['eslestirme_detaylari'],
                        'guncelleyen_id' => auth()->id(),
                    ]);
                } else {
                    // Yeni eşleştirme oluştur
                    TalepPortfoyEslestirme::create([
                        'talep_id' => $eslestirme['talep_id'],
                        'mulk_id' => $eslestirme['mulk_id'],
                        'mulk_type' => $eslestirme['mulk_type'],
                        'eslestirme_skoru' => $eslestirme['eslestirme_skoru'],
                        'eslestirme_detaylari' => $eslestirme['eslestirme_detaylari'],
                        'durum' => 'yeni',
                        'olusturan_id' => auth()->id(),
                    ]);
                }
            }
        });
    }

    /**
     * Eşleştirme istatistikleri al
     */
    public function eslestirmeIstatistikleri(): array
    {
        return [
            'toplam_aktif_talep' => MusteriTalep::aktif()->count(),
            'eslestirmesi_olan_talep' => MusteriTalep::aktif()
                ->whereHas('aktifEslestirmeler')
                ->count(),
            'toplam_eslestirme' => TalepPortfoyEslestirme::aktif()->count(),
            'yuksek_skorlu_eslestirme' => TalepPortfoyEslestirme::aktif()
                ->where('eslestirme_skoru', '>=', 0.8)
                ->count(),
            'sunulmus_eslestirme' => TalepPortfoyEslestirme::aktif()
                ->sunulmus()
                ->count(),
            'bekleyen_eslestirme' => TalepPortfoyEslestirme::aktif()
                ->bekleyen()
                ->count(),
        ];
    }

    /**
     * Otomatik eşleştirme kontrolü yap
     */
    public function otomatikEslestirmeKontrolu(): void
    {
        Log::info("Otomatik eşleştirme kontrolü başlatıldı");

        // Son 24 saat içinde güncellenen aktif talepleri al
        $guncelTalepler = MusteriTalep::aktif()
            ->where('son_aktivite_tarihi', '>=', now()->subDay())
            ->get();

        foreach ($guncelTalepler as $talep) {
            try {
                $this->talepIcinEslestirmeYap($talep);
            } catch (\Exception $e) {
                Log::error("Otomatik eşleştirme hatası", [
                    'talep_id' => $talep->id,
                    'hata' => $e->getMessage()
                ]);
            }
        }

        Log::info("Otomatik eşleştirme kontrolü tamamlandı", [
            'kontrol_edilen_talep_sayisi' => $guncelTalepler->count()
        ]);
    }
}