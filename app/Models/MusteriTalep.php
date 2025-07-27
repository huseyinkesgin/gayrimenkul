<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\TalepDurumu;
use App\Enums\MulkKategorisi;
use App\Models\Musteri\Musteri;
use App\Models\Kisi\Personel;
use App\Traits\HasAuditTrail;
use App\Traits\HasTalepEslestirme;

/**
 * Müşteri Talep Modeli
 * 
 * Bu model müşterilerin gayrimenkul taleplerini yönetir.
 * JSON field yapısı ile esnek talep kriterleri saklar.
 * 
 * @property int $id
 * @property int $musteri_id
 * @property int $sorumlu_personel_id
 * @property string $baslik
 * @property string|null $aciklama
 * @property MulkKategorisi $mulk_kategorisi
 * @property string|null $mulk_alt_tipi
 * @property TalepDurumu $durum
 * @property int|null $oncelik
 * @property decimal|null $min_fiyat
 * @property decimal|null $max_fiyat
 * @property int|null $min_m2
 * @property int|null $max_m2
 * @property array $lokasyon_tercihleri
 * @property array $ozellik_kriterleri
 * @property array $ozel_gereksinimler
 * @property array $notlar
 * @property array $metadata
 * @property datetime|null $son_aktivite_tarihi
 * @property datetime|null $hedef_tarih
 * @property datetime|null $tamamlanma_tarihi
 * @property int $olusturan_id
 * @property int|null $guncelleyen_id
 * @property boolean $aktif_mi
 * @property timestamps
 * @property softDeletes
 */
class MusteriTalep extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail, HasTalepEslestirme;

    protected $table = 'musteri_talepleri';

    protected $fillable = [
        'musteri_id',
        'sorumlu_personel_id',
        'baslik',
        'aciklama',
        'mulk_kategorisi',
        'mulk_alt_tipi',
        'durum',
        'oncelik',
        'min_fiyat',
        'max_fiyat',
        'min_m2',
        'max_m2',
        'lokasyon_tercihleri',
        'ozellik_kriterleri',
        'ozel_gereksinimler',
        'notlar',
        'metadata',
        'son_aktivite_tarihi',
        'hedef_tarih',
        'tamamlanma_tarihi',
        'olusturan_id',
        'guncelleyen_id',
        'aktif_mi',
    ];

    protected $casts = [
        'mulk_kategorisi' => MulkKategorisi::class,
        'durum' => TalepDurumu::class,
        'min_fiyat' => 'decimal:2',
        'max_fiyat' => 'decimal:2',
        'lokasyon_tercihleri' => 'array',
        'ozellik_kriterleri' => 'array',
        'ozel_gereksinimler' => 'array',
        'notlar' => 'array',
        'metadata' => 'array',
        'son_aktivite_tarihi' => 'datetime',
        'hedef_tarih' => 'datetime',
        'tamamlanma_tarihi' => 'datetime',
        'aktif_mi' => 'boolean',
    ];

    protected $dates = [
        'son_aktivite_tarihi',
        'hedef_tarih',
        'tamamlanma_tarihi',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Müşteri ilişkisi
     */
    public function musteri(): BelongsTo
    {
        return $this->belongsTo(Musteri::class);
    }

    /**
     * Sorumlu personel ilişkisi
     */
    public function sorumluPersonel(): BelongsTo
    {
        return $this->belongsTo(Personel::class, 'sorumlu_personel_id');
    }

    /**
     * Oluşturan kullanıcı ilişkisi
     */
    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    /**
     * Güncelleyen kullanıcı ilişkisi
     */
    public function guncelleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guncelleyen_id');
    }

    /**
     * Talep eşleştirmeleri ilişkisi
     */
    public function eslestirmeler(): HasMany
    {
        return $this->hasMany(TalepPortfoyEslestirme::class, 'talep_id');
    }

    /**
     * Aktif eşleştirmeler
     */
    public function aktifEslestirmeler(): HasMany
    {
        return $this->hasMany(TalepPortfoyEslestirme::class, 'talep_id')
                    ->where('aktif_mi', true);
    }

    /**
     * Talep geçmişi (aktiviteler)
     */
    public function aktiviteler(): HasMany
    {
        return $this->hasMany(TalepAktivite::class, 'talep_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Aktif talepler
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif_mi', true)
                    ->whereIn('durum', TalepDurumu::aktifDurumlar());
    }

    /**
     * Scope: Belirli durumda olanlar
     */
    public function scopeDurum($query, TalepDurumu $durum)
    {
        return $query->where('durum', $durum);
    }

    /**
     * Scope: Belirli mülk kategorisinde olanlar
     */
    public function scopeMulkKategorisi($query, MulkKategorisi $kategori)
    {
        return $query->where('mulk_kategorisi', $kategori);
    }

    /**
     * Scope: Fiyat aralığında olanlar
     */
    public function scopeFiyatAraliginda($query, $minFiyat = null, $maxFiyat = null)
    {
        if ($minFiyat !== null) {
            $query->where(function ($q) use ($minFiyat) {
                $q->whereNull('max_fiyat')
                  ->orWhere('max_fiyat', '>=', $minFiyat);
            });
        }

        if ($maxFiyat !== null) {
            $query->where(function ($q) use ($maxFiyat) {
                $q->whereNull('min_fiyat')
                  ->orWhere('min_fiyat', '<=', $maxFiyat);
            });
        }

        return $query;
    }

    /**
     * Scope: M2 aralığında olanlar
     */
    public function scopeM2Araliginda($query, $minM2 = null, $maxM2 = null)
    {
        if ($minM2 !== null) {
            $query->where(function ($q) use ($minM2) {
                $q->whereNull('max_m2')
                  ->orWhere('max_m2', '>=', $minM2);
            });
        }

        if ($maxM2 !== null) {
            $query->where(function ($q) use ($maxM2) {
                $q->whereNull('min_m2')
                  ->orWhere('min_m2', '<=', $maxM2);
            });
        }

        return $query;
    }

    /**
     * Scope: Belirli lokasyonda olanlar
     */
    public function scopeLokasyonda($query, array $lokasyonlar)
    {
        return $query->where(function ($q) use ($lokasyonlar) {
            foreach ($lokasyonlar as $lokasyon) {
                $q->orWhereJsonContains('lokasyon_tercihleri', $lokasyon);
            }
        });
    }

    /**
     * Scope: Öncelik sırasına göre
     */
    public function scopeOncelikSirasi($query)
    {
        return $query->orderByRaw('COALESCE(oncelik, 999) ASC')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Scope: Son aktivite tarihine göre
     */
    public function scopeSonAktivite($query)
    {
        return $query->orderByRaw('COALESCE(son_aktivite_tarihi, created_at) DESC');
    }

    /**
     * Talep durumunu güncelle
     */
    public function durumGuncelle(TalepDurumu $yeniDurum, string $aciklama = null, int $guncelleyenId = null): void
    {
        $eskiDurum = $this->durum;
        
        $this->update([
            'durum' => $yeniDurum,
            'son_aktivite_tarihi' => now(),
            'guncelleyen_id' => $guncelleyenId,
            'tamamlanma_tarihi' => $yeniDurum->isPasif() ? now() : null,
        ]);

        // Aktivite kaydı oluştur
        $this->aktiviteEkle('durum_degisiklik', [
            'eski_durum' => $eskiDurum->value,
            'yeni_durum' => $yeniDurum->value,
            'aciklama' => $aciklama,
        ], $guncelleyenId);
    }

    /**
     * Talep aktivitesi ekle
     */
    public function aktiviteEkle(string $tip, array $detaylar = [], int $kullaniciId = null): void
    {
        $this->aktiviteler()->create([
            'tip' => $tip,
            'detaylar' => $detaylar,
            'olusturan_id' => $kullaniciId ?? auth()->id(),
        ]);

        // Son aktivite tarihini güncelle
        $this->update(['son_aktivite_tarihi' => now()]);
    }

    /**
     * Not ekle
     */
    public function notEkle(string $not, int $kullaniciId = null): void
    {
        $mevcutNotlar = $this->notlar ?? [];
        $mevcutNotlar[] = [
            'not' => $not,
            'tarih' => now()->toISOString(),
            'kullanici_id' => $kullaniciId ?? auth()->id(),
        ];

        $this->update(['notlar' => $mevcutNotlar]);
        
        $this->aktiviteEkle('not_eklendi', ['not' => $not], $kullaniciId);
    }

    /**
     * Özel gereksinim ekle
     */
    public function ozelGereksinimEkle(string $gereksinim, int $kullaniciId = null): void
    {
        $mevcutGereksinimler = $this->ozel_gereksinimler ?? [];
        $mevcutGereksinimler[] = [
            'gereksinim' => $gereksinim,
            'tarih' => now()->toISOString(),
            'kullanici_id' => $kullaniciId ?? auth()->id(),
        ];

        $this->update(['ozel_gereksinimler' => $mevcutGereksinimler]);
        
        $this->aktiviteEkle('gereksinim_eklendi', ['gereksinim' => $gereksinim], $kullaniciId);
    }

    /**
     * Lokasyon tercihi ekle
     */
    public function lokasyonTercihiEkle(array $lokasyon): void
    {
        $mevcutTercihler = $this->lokasyon_tercihleri ?? [];
        
        // Aynı lokasyon zaten var mı kontrol et
        $mevcutMu = collect($mevcutTercihler)->contains(function ($tercih) use ($lokasyon) {
            return $tercih['sehir_id'] === $lokasyon['sehir_id'] &&
                   ($tercih['ilce_id'] ?? null) === ($lokasyon['ilce_id'] ?? null) &&
                   ($tercih['semt_id'] ?? null) === ($lokasyon['semt_id'] ?? null);
        });

        if (!$mevcutMu) {
            $mevcutTercihler[] = $lokasyon;
            $this->update(['lokasyon_tercihleri' => $mevcutTercihler]);
        }
    }

    /**
     * Özellik kriteri ekle
     */
    public function ozellikKriteriEkle(string $ozellik, $deger): void
    {
        $mevcutKriterler = $this->ozellik_kriterleri ?? [];
        $mevcutKriterler[$ozellik] = $deger;
        
        $this->update(['ozellik_kriterleri' => $mevcutKriterler]);
    }

    /**
     * Talep özeti
     */
    public function getOzetAttribute(): string
    {
        $ozet = $this->mulk_kategorisi->label();
        
        if ($this->mulk_alt_tipi) {
            $ozet .= " ({$this->mulk_alt_tipi})";
        }

        if ($this->min_m2 || $this->max_m2) {
            $ozet .= " - ";
            if ($this->min_m2 && $this->max_m2) {
                $ozet .= "{$this->min_m2}-{$this->max_m2} m²";
            } elseif ($this->min_m2) {
                $ozet .= "Min {$this->min_m2} m²";
            } else {
                $ozet .= "Max {$this->max_m2} m²";
            }
        }

        if ($this->min_fiyat || $this->max_fiyat) {
            $ozet .= " - ";
            if ($this->min_fiyat && $this->max_fiyat) {
                $ozet .= number_format($this->min_fiyat) . "-" . number_format($this->max_fiyat) . " ₺";
            } elseif ($this->min_fiyat) {
                $ozet .= "Min " . number_format($this->min_fiyat) . " ₺";
            } else {
                $ozet .= "Max " . number_format($this->max_fiyat) . " ₺";
            }
        }

        return $ozet;
    }

    /**
     * Talep yaşı (gün)
     */
    public function getYasiAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Son aktiviteden bu yana geçen gün
     */
    public function getSonAktivitedenberiAttribute(): int
    {
        $sonAktivite = $this->son_aktivite_tarihi ?? $this->created_at;
        return $sonAktivite->diffInDays(now());
    }

    /**
     * Eşleştirme sayısı
     */
    public function getEslestirmeSayisiAttribute(): int
    {
        return $this->eslestirmeler()->count();
    }

    /**
     * Aktif eşleştirme sayısı
     */
    public function getAktifEslestirmeSayisiAttribute(): int
    {
        return $this->aktifEslestirmeler()->count();
    }

    /**
     * Talep tamamlanma oranı (0-100)
     */
    public function getTamamlanmaOraniAttribute(): int
    {
        if ($this->durum->isPasif()) {
            return 100;
        }

        $puan = 0;
        
        // Temel bilgiler (40 puan)
        if ($this->baslik) $puan += 10;
        if ($this->aciklama) $puan += 10;
        if ($this->mulk_kategorisi) $puan += 20;

        // Fiyat bilgisi (20 puan)
        if ($this->min_fiyat || $this->max_fiyat) $puan += 20;

        // M2 bilgisi (20 puan)
        if ($this->min_m2 || $this->max_m2) $puan += 20;

        // Lokasyon tercihleri (10 puan)
        if (!empty($this->lokasyon_tercihleri)) $puan += 10;

        // Özellik kriterleri (10 puan)
        if (!empty($this->ozellik_kriterleri)) $puan += 10;

        return min($puan, 100);
    }

    /**
     * Talep acil mi?
     */
    public function getAcilMiAttribute(): bool
    {
        // Hedef tarih 7 gün içindeyse acil
        if ($this->hedef_tarih && $this->hedef_tarih->diffInDays(now()) <= 7) {
            return true;
        }

        // Öncelik 1 ise acil
        if ($this->oncelik === 1) {
            return true;
        }

        // 30 günden fazla bekleyen talepler acil
        if ($this->yasi > 30) {
            return true;
        }

        return false;
    }

    /**
     * Model boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->olusturan_id) {
                $model->olusturan_id = auth()->id();
            }
            if (!isset($model->aktif_mi)) {
                $model->aktif_mi = true;
            }
            if (!$model->durum) {
                $model->durum = TalepDurumu::AKTIF;
            }
        });

        static::updating(function ($model) {
            $model->guncelleyen_id = auth()->id();
        });

        // Talep oluşturulduğunda otomatik eşleştirme tetikle
        static::created(function ($model) {
            $model->otomatikEslestirmeTetikle();
        });

        // Talep güncellendiğinde otomatik eşleştirme tetikle
        static::updated(function ($model) {
            // Sadece önemli alanlar değiştiyse eşleştirme tetikle
            $onemliAlanlar = [
                'mulk_kategorisi', 'mulk_alt_tipi', 'min_fiyat', 'max_fiyat',
                'min_m2', 'max_m2', 'lokasyon_tercihleri', 'ozellik_kriterleri'
            ];

            $degisiklikVar = false;
            foreach ($onemliAlanlar as $alan) {
                if ($model->isDirty($alan)) {
                    $degisiklikVar = true;
                    break;
                }
            }

            if ($degisiklikVar) {
                $model->otomatikEslestirmeTetikle();
                
                // Talep güncellendi bildirimi gönder
                $bildirimService = app(\App\Services\EslestirmeBildirimService::class);
                $bildirimService->talepGuncellendiBildirimi($model, $onemliAlanlar);
            }
        });
    }
}