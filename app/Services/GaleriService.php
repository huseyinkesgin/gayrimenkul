<?php

namespace App\Services;

use App\Models\Resim;
use App\Enums\ResimKategorisi;
use App\Enums\MulkKategorisi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Galeri Yönetim Servisi
 * 
 * Bu servis gayrimenkul portföy sistemi için galeri yönetimi,
 * mülk tipine göre galeri kuralları ve organizasyon işlemlerini yönetir.
 * 
 * Özellikler:
 * - Mülk tipine göre galeri kuralları
 * - Resim sıralama ve organizasyon
 * - Ana resim yönetimi
 * - Galeri istatistikleri
 * - Toplu işlemler
 */
class GaleriService
{
    private ResimUploadService $resimUploadService;
    private array $galeriKurallari;

    public function __construct(ResimUploadService $resimUploadService)
    {
        $this->resimUploadService = $resimUploadService;
        
        // Mülk tipine göre galeri kuralları
        $this->galeriKurallari = [
            MulkKategorisi::KONUT->value => [
                'galeri_aktif' => true,
                'min_resim' => 3,
                'max_resim' => 50,
                'zorunlu_kategoriler' => [
                    ResimKategorisi::GALERI_DIS_CEPHE,
                    ResimKategorisi::GALERI_SALON,
                    ResimKategorisi::GALERI_MUTFAK
                ],
                'opsiyonel_kategoriler' => [
                    ResimKategorisi::GALERI_YATAK_ODASI,
                    ResimKategorisi::GALERI_BANYO,
                    ResimKategorisi::GALERI_BALKON,
                    ResimKategorisi::GALERI_BAHCE
                ]
            ],
            MulkKategorisi::ISYERI->value => [
                'galeri_aktif' => true,
                'min_resim' => 2,
                'max_resim' => 30,
                'zorunlu_kategoriler' => [
                    ResimKategorisi::GALERI_DIS_CEPHE,
                    ResimKategorisi::GALERI_IC_MEKAN
                ],
                'opsiyonel_kategoriler' => [
                    ResimKategorisi::GALERI_OFIS,
                    ResimKategorisi::GALERI_DEPO,
                    ResimKategorisi::GALERI_OTOPARK
                ]
            ],
            MulkKategorisi::TURISTIK_TESIS->value => [
                'galeri_aktif' => true,
                'min_resim' => 5,
                'max_resim' => 100,
                'zorunlu_kategoriler' => [
                    ResimKategorisi::GALERI_DIS_CEPHE,
                    ResimKategorisi::GALERI_RESEPSIYON,
                    ResimKategorisi::GALERI_ODA
                ],
                'opsiyonel_kategoriler' => [
                    ResimKategorisi::GALERI_RESTORAN,
                    ResimKategorisi::GALERI_HAVUZ,
                    ResimKategorisi::GALERI_SPA,
                    ResimKategorisi::GALERI_BAHCE
                ]
            ],
            MulkKategorisi::ARSA->value => [
                'galeri_aktif' => false,
                'min_resim' => 0,
                'max_resim' => 0,
                'zorunlu_kategoriler' => [],
                'opsiyonel_kategoriler' => []
            ]
        ];
    }

    /**
     * Mülk için galeri oluştur
     */
    public function galeriOlustur(string $mulkType, string $mulkId): array
    {
        try {
            $mulkKategorisi = $this->mulkTipindenKategoriBelirle($mulkType);
            
            if (!$this->galeriAktifMi($mulkKategorisi)) {
                return [
                    'basarili' => false,
                    'hata' => 'Bu mülk tipi için galeri oluşturulamaz.',
                    'mulk_tipi' => $mulkKategorisi
                ];
            }

            $galeriKurali = $this->galeriKurallari[$mulkKategorisi];
            
            return [
                'basarili' => true,
                'mulk_id' => $mulkId,
                'mulk_tipi' => $mulkKategorisi,
                'kurallar' => $galeriKurali,
                'mesaj' => 'Galeri başarıyla oluşturuldu.'
            ];

        } catch (\Exception $e) {
            Log::error('Galeri oluşturma hatası: ' . $e->getMessage(), [
                'mulk_type' => $mulkType,
                'mulk_id' => $mulkId
            ]);

            return [
                'basarili' => false,
                'hata' => 'Galeri oluşturulurken hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Galeri resimlerini getir
     */
    public function galeriResimleriGetir(
        string $mulkType,
        string $mulkId,
        ResimKategorisi $kategori = null,
        string $siralama = 'sira_asc'
    ): array {
        try {
            $query = Resim::where('imagable_type', $mulkType)
                          ->where('imagable_id', $mulkId)
                          ->where('aktif_mi', true);

            // Kategori filtresi
            if ($kategori) {
                $query->where('kategori', $kategori);
            } else {
                // Sadece galeri kategorilerini getir
                $galeriKategorileri = $this->getGaleriKategorileri();
                $query->whereIn('kategori', $galeriKategorileri);
            }

            // Sıralama
            switch ($siralama) {
                case 'sira_asc':
                    $query->orderBy('sira', 'asc')->orderBy('created_at', 'asc');
                    break;
                case 'sira_desc':
                    $query->orderBy('sira', 'desc')->orderBy('created_at', 'desc');
                    break;
                case 'tarih_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'tarih_desc':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'ana_resim':
                    $query->orderBy('ana_resim_mi', 'desc')->orderBy('sira', 'asc');
                    break;
                default:
                    $query->orderBy('sira', 'asc')->orderBy('created_at', 'asc');
            }

            $resimler = $query->get();
            
            // Resim URL'lerini oluştur
            $resimlerDetay = $resimler->map(function ($resim) {
                return [
                    'id' => $resim->id,
                    'baslik' => $resim->baslik,
                    'aciklama' => $resim->aciklama,
                    'kategori' => $resim->kategori,
                    'sira' => $resim->sira,
                    'ana_resim_mi' => $resim->ana_resim_mi,
                    'boyutlar' => $resim->boyutlar,
                    'metadata' => $resim->metadata,
                    'created_at' => $resim->created_at,
                    'urls' => [
                        'thumbnail' => $this->resimUploadService->resimUrlOlustur($resim, 'thumbnail'),
                        'small' => $this->resimUploadService->resimUrlOlustur($resim, 'small'),
                        'medium' => $this->resimUploadService->resimUrlOlustur($resim, 'medium'),
                        'large' => $this->resimUploadService->resimUrlOlustur($resim, 'large'),
                        'original' => $this->resimUploadService->resimUrlOlustur($resim, 'original')
                    ]
                ];
            });

            return [
                'basarili' => true,
                'resimler' => $resimlerDetay,
                'toplam' => $resimler->count(),
                'siralama' => $siralama,
                'kategori' => $kategori?->value
            ];

        } catch (\Exception $e) {
            Log::error('Galeri resimleri getirme hatası: ' . $e->getMessage(), [
                'mulk_type' => $mulkType,
                'mulk_id' => $mulkId
            ]);

            return [
                'basarili' => false,
                'hata' => 'Galeri resimleri getirilirken hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ana resim belirle
     */
    public function anaResimBelirle(int $resimId, string $mulkType, string $mulkId): array
    {
        try {
            DB::beginTransaction();

            // Önce tüm resimlerin ana resim durumunu kaldır
            Resim::where('imagable_type', $mulkType)
                 ->where('imagable_id', $mulkId)
                 ->update(['ana_resim_mi' => false]);

            // Seçilen resmi ana resim yap
            $resim = Resim::find($resimId);
            if (!$resim) {
                DB::rollBack();
                return [
                    'basarili' => false,
                    'hata' => 'Resim bulunamadı.'
                ];
            }

            $resim->update(['ana_resim_mi' => true, 'sira' => 1]);

            DB::commit();

            Log::info('Ana resim belirlendi', [
                'resim_id' => $resimId,
                'mulk_type' => $mulkType,
                'mulk_id' => $mulkId
            ]);

            return [
                'basarili' => true,
                'resim' => $resim,
                'mesaj' => 'Ana resim başarıyla belirlendi.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ana resim belirleme hatası: ' . $e->getMessage(), [
                'resim_id' => $resimId,
                'mulk_type' => $mulkType,
                'mulk_id' => $mulkId
            ]);

            return [
                'basarili' => false,
                'hata' => 'Ana resim belirlenirken hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Resim sıralamasını güncelle
     */
    public function resimSiralamasiGuncelle(array $resimSiralari): array
    {
        try {
            DB::beginTransaction();

            foreach ($resimSiralari as $sira => $resimId) {
                Resim::where('id', $resimId)->update(['sira' => $sira + 1]);
            }

            DB::commit();

            Log::info('Resim sıralaması güncellendi', [
                'resim_sayisi' => count($resimSiralari)
            ]);

            return [
                'basarili' => true,
                'guncellenen_resim_sayisi' => count($resimSiralari),
                'mesaj' => 'Resim sıralaması başarıyla güncellendi.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resim sıralama güncelleme hatası: ' . $e->getMessage());

            return [
                'basarili' => false,
                'hata' => 'Resim sıralaması güncellenirken hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Galeri istatistikleri
     */
    public function galeriIstatistikleri(string $mulkType, string $mulkId): array
    {
        try {
            $mulkKategorisi = $this->mulkTipindenKategoriBelirle($mulkType);
            
            if (!$this->galeriAktifMi($mulkKategorisi)) {
                return [
                    'basarili' => false,
                    'hata' => 'Bu mülk tipi için galeri mevcut değil.'
                ];
            }

            $galeriKurali = $this->galeriKurallari[$mulkKategorisi];
            
            $resimler = Resim::where('imagable_type', $mulkType)
                            ->where('imagable_id', $mulkId)
                            ->where('aktif_mi', true)
                            ->get();

            $toplamResim = $resimler->count();
            $anaResim = $resimler->where('ana_resim_mi', true)->first();
            
            // Kategori bazında dağılım
            $kategoriBazindaDagilim = $resimler->groupBy('kategori')
                                             ->map(function ($grup) {
                                                 return $grup->count();
                                             });

            // Eksik kategoriler
            $mevcutKategoriler = $kategoriBazindaDagilim->keys()->toArray();
            $zorunluKategoriler = $galeriKurali['zorunlu_kategoriler'];
            $eksikKategoriler = array_diff($zorunluKategoriler, $mevcutKategoriler);

            // Galeri durumu
            $galeriTamamMi = empty($eksikKategoriler) && $toplamResim >= $galeriKurali['min_resim'];
            $galeriDoluMu = $toplamResim >= $galeriKurali['max_resim'];

            return [
                'basarili' => true,
                'mulk_tipi' => $mulkKategorisi,
                'toplam_resim' => $toplamResim,
                'min_resim' => $galeriKurali['min_resim'],
                'max_resim' => $galeriKurali['max_resim'],
                'ana_resim' => $anaResim ? [
                    'id' => $anaResim->id,
                    'baslik' => $anaResim->baslik,
                    'url' => $this->resimUploadService->resimUrlOlustur($anaResim, 'medium')
                ] : null,
                'kategori_dagilimi' => $kategoriBazindaDagilim,
                'zorunlu_kategoriler' => $zorunluKategoriler,
                'eksik_kategoriler' => $eksikKategoriler,
                'galeri_tamamlandi' => $galeriTamamMi,
                'galeri_dolu' => $galeriDoluMu,
                'doluluk_orani' => $galeriKurali['max_resim'] > 0 ? 
                    round(($toplamResim / $galeriKurali['max_resim']) * 100, 2) : 0
            ];

        } catch (\Exception $e) {
            Log::error('Galeri istatistikleri hatası: ' . $e->getMessage(), [
                'mulk_type' => $mulkType,
                'mulk_id' => $mulkId
            ]);

            return [
                'basarili' => false,
                'hata' => 'Galeri istatistikleri alınırken hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Toplu resim silme
     */
    public function topluResimSil(array $resimIdleri): array
    {
        try {
            DB::beginTransaction();

            $silinenSayisi = 0;
            $hataliSayisi = 0;
            $hatalar = [];

            foreach ($resimIdleri as $resimId) {
                $resim = Resim::find($resimId);
                if ($resim) {
                    if ($this->resimUploadService->resimSil($resim)) {
                        $silinenSayisi++;
                    } else {
                        $hataliSayisi++;
                        $hatalar[] = "Resim ID {$resimId} silinemedi";
                    }
                } else {
                    $hataliSayisi++;
                    $hatalar[] = "Resim ID {$resimId} bulunamadı";
                }
            }

            DB::commit();

            Log::info('Toplu resim silme tamamlandı', [
                'toplam' => count($resimIdleri),
                'silinen' => $silinenSayisi,
                'hatali' => $hataliSayisi
            ]);

            return [
                'basarili' => true,
                'toplam' => count($resimIdleri),
                'silinen' => $silinenSayisi,
                'hatali' => $hataliSayisi,
                'hatalar' => $hatalar,
                'mesaj' => "{$silinenSayisi} resim başarıyla silindi."
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Toplu resim silme hatası: ' . $e->getMessage());

            return [
                'basarili' => false,
                'hata' => 'Toplu resim silme işleminde hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Galeri organizasyonu öner
     */
    public function galeriOrganizasyonuOner(string $mulkType, string $mulkId): array
    {
        try {
            $mulkKategorisi = $this->mulkTipindenKategoriBelirle($mulkType);
            
            if (!$this->galeriAktifMi($mulkKategorisi)) {
                return [
                    'basarili' => false,
                    'hata' => 'Bu mülk tipi için galeri mevcut değil.'
                ];
            }

            $resimler = Resim::where('imagable_type', $mulkType)
                            ->where('imagable_id', $mulkId)
                            ->where('aktif_mi', true)
                            ->get();

            $oneriler = [];
            $galeriKurali = $this->galeriKurallari[$mulkKategorisi];

            // Ana resim önerisi
            if (!$resimler->where('ana_resim_mi', true)->count()) {
                $disCepheResmi = $resimler->where('kategori', ResimKategorisi::GALERI_DIS_CEPHE)->first();
                if ($disCepheResmi) {
                    $oneriler[] = [
                        'tip' => 'ana_resim',
                        'mesaj' => 'Dış cephe resmi ana resim olarak belirlenebilir.',
                        'resim_id' => $disCepheResmi->id,
                        'oncelik' => 'yuksek'
                    ];
                }
            }

            // Eksik kategori önerileri
            $mevcutKategoriler = $resimler->pluck('kategori')->unique()->toArray();
            $zorunluKategoriler = $galeriKurali['zorunlu_kategoriler'];
            $eksikKategoriler = array_diff($zorunluKategoriler, $mevcutKategoriler);

            foreach ($eksikKategoriler as $kategori) {
                $oneriler[] = [
                    'tip' => 'eksik_kategori',
                    'mesaj' => "'{$kategori}' kategorisinde resim eklenmesi önerilir.",
                    'kategori' => $kategori,
                    'oncelik' => 'orta'
                ];
            }

            // Sıralama önerisi
            $siralanmamisResimler = $resimler->where('sira', 0)->count();
            if ($siralanmamisResimler > 0) {
                $oneriler[] = [
                    'tip' => 'siralama',
                    'mesaj' => "{$siralanmamisResimler} resimin sıralaması yapılmamış.",
                    'resim_sayisi' => $siralanmamisResimler,
                    'oncelik' => 'dusuk'
                ];
            }

            // Fazla resim uyarısı
            if ($resimler->count() > $galeriKurali['max_resim']) {
                $fazlaResim = $resimler->count() - $galeriKurali['max_resim'];
                $oneriler[] = [
                    'tip' => 'fazla_resim',
                    'mesaj' => "Galeri limitini {$fazlaResim} resim aşıyor.",
                    'fazla_resim_sayisi' => $fazlaResim,
                    'oncelik' => 'orta'
                ];
            }

            return [
                'basarili' => true,
                'oneriler' => $oneriler,
                'oneri_sayisi' => count($oneriler),
                'galeri_durumu' => $this->galeriIstatistikleri($mulkType, $mulkId)
            ];

        } catch (\Exception $e) {
            Log::error('Galeri organizasyon önerisi hatası: ' . $e->getMessage(), [
                'mulk_type' => $mulkType,
                'mulk_id' => $mulkId
            ]);

            return [
                'basarili' => false,
                'hata' => 'Galeri organizasyon önerisi alınırken hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mülk tipinden kategori belirle
     */
    private function mulkTipindenKategoriBelirle(string $mulkType): string
    {
        // Model adından kategori çıkar
        $modelAdi = class_basename($mulkType);
        
        // Konut kategorisi kontrolleri
        $konutTipleri = ['Daire', 'Villa', 'Rezidans', 'Yali', 'Yazlik'];
        if (in_array($modelAdi, $konutTipleri)) {
            return MulkKategorisi::KONUT->value;
        }

        // İşyeri kategorisi kontrolleri
        $isyeriTipleri = ['Depo', 'Fabrika', 'Magaza', 'Ofis', 'Dukkan'];
        if (in_array($modelAdi, $isyeriTipleri)) {
            return MulkKategorisi::ISYERI->value;
        }

        // Turistik tesis kategorisi kontrolleri
        $turistikTipleri = ['ButikOtel', 'ApartOtel', 'Hotel', 'Motel', 'TatilKoyu'];
        if (in_array($modelAdi, $turistikTipleri)) {
            return MulkKategorisi::TURISTIK_TESIS->value;
        }

        // Arsa kategorisi kontrolleri
        $arsaTipleri = ['TicariArsa', 'SanayiArsasi', 'KonutArsasi'];
        if (in_array($modelAdi, $arsaTipleri)) {
            return MulkKategorisi::ARSA->value;
        }

        // Varsayılan olarak konut
        return MulkKategorisi::KONUT->value;
    }

    /**
     * Galeri aktif mi kontrol et
     */
    private function galeriAktifMi(string $mulkKategorisi): bool
    {
        return $this->galeriKurallari[$mulkKategorisi]['galeri_aktif'] ?? false;
    }

    /**
     * Galeri kategorilerini getir
     */
    private function getGaleriKategorileri(): array
    {
        return [
            ResimKategorisi::GALERI_DIS_CEPHE,
            ResimKategorisi::GALERI_SALON,
            ResimKategorisi::GALERI_MUTFAK,
            ResimKategorisi::GALERI_YATAK_ODASI,
            ResimKategorisi::GALERI_BANYO,
            ResimKategorisi::GALERI_BALKON,
            ResimKategorisi::GALERI_BAHCE,
            ResimKategorisi::GALERI_IC_MEKAN,
            ResimKategorisi::GALERI_OFIS,
            ResimKategorisi::GALERI_DEPO,
            ResimKategorisi::GALERI_OTOPARK,
            ResimKategorisi::GALERI_RESEPSIYON,
            ResimKategorisi::GALERI_ODA,
            ResimKategorisi::GALERI_RESTORAN,
            ResimKategorisi::GALERI_HAVUZ,
            ResimKategorisi::GALERI_SPA
        ];
    }

    /**
     * Mülk tipi için uygun kategorileri getir
     */
    public function mulkTipiIcinKategorileriGetir(string $mulkType): array
    {
        $mulkKategorisi = $this->mulkTipindenKategoriBelirle($mulkType);
        
        if (!$this->galeriAktifMi($mulkKategorisi)) {
            return [
                'basarili' => false,
                'hata' => 'Bu mülk tipi için galeri mevcut değil.'
            ];
        }

        $galeriKurali = $this->galeriKurallari[$mulkKategorisi];
        
        return [
            'basarili' => true,
            'mulk_tipi' => $mulkKategorisi,
            'zorunlu_kategoriler' => $galeriKurali['zorunlu_kategoriler'],
            'opsiyonel_kategoriler' => $galeriKurali['opsiyonel_kategoriler'],
            'tum_kategoriler' => array_merge(
                $galeriKurali['zorunlu_kategoriler'],
                $galeriKurali['opsiyonel_kategoriler']
            )
        ];
    }

    /**
     * Galeri kurallarını getir
     */
    public function galeriKurallariniGetir(string $mulkType): array
    {
        $mulkKategorisi = $this->mulkTipindenKategoriBelirle($mulkType);
        
        return [
            'basarili' => true,
            'mulk_tipi' => $mulkKategorisi,
            'kurallar' => $this->galeriKurallari[$mulkKategorisi] ?? null
        ];
    }
}