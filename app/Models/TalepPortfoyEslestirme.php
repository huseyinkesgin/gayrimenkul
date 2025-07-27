<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditTrail;

/**
 * Talep Portföy Eşleştirme Modeli
 * 
 * Bu model müşteri talepleri ile portföy arasındaki eşleştirmeleri yönetir.
 * 
 * @property string $id
 * @property string $talep_id
 * @property string $mulk_id
 * @property string $mulk_type
 * @property float|null $eslestirme_skoru
 * @property array|null $eslestirme_detaylari
 * @property string $durum
 * @property string|null $personel_notu
 * @property datetime|null $sunum_tarihi
 * @property string|null $sunan_personel_id
 * @property string|null $musteri_geri_bildirimi
 * @property string|null $olusturan_id
 * @property string|null $guncelleyen_id
 * @property boolean $aktif_mi
 * @property datetime $olusturma_tarihi
 * @property datetime $guncelleme_tarihi
 */
class TalepPortfoyEslestirme extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'talep_portfoy_eslestirmeleri';
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    const CREATED_AT = 'olusturma_tarihi';
    const UPDATED_AT = 'guncelleme_tarihi';

    protected $fillable = [
        'talep_id',
        'mulk_id',
        'mulk_type',
        'eslestirme_skoru',
        'eslestirme_detaylari',
        'durum',
        'personel_notu',
        'sunum_tarihi',
        'sunan_personel_id',
        'musteri_geri_bildirimi',
        'olusturan_id',
        'guncelleyen_id',
        'aktif_mi',
    ];

    protected $casts = [
        'eslestirme_skoru' => 'float',
        'eslestirme_detaylari' => 'array',
        'sunum_tarihi' => 'datetime',
        'aktif_mi' => 'boolean',
        'olusturma_tarihi' => 'datetime',
        'guncelleme_tarihi' => 'datetime',
    ];

    protected $dates = [
        'sunum_tarihi',
        'olusturma_tarihi',
        'guncelleme_tarihi',
    ];

    /**
     * Talep ilişkisi
     */
    public function talep(): BelongsTo
    {
        return $this->belongsTo(MusteriTalep::class, 'talep_id');
    }

    /**
     * Mülk ilişkisi (polymorphic)
     */
    public function mulk()
    {
        return $this->morphTo('mulk', 'mulk_type', 'mulk_id');
    }

    /**
     * Sunan personel ilişkisi
     */
    public function sunanPersonel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Kisi\Personel::class, 'sunan_personel_id');
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
     * Scope: Aktif eşleştirmeler
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif_mi', true);
    }

    /**
     * Scope: Belirli durumda olanlar
     */
    public function scopeDurum($query, string $durum)
    {
        return $query->where('durum', $durum);
    }

    /**
     * Scope: Yüksek skorlu eşleştirmeler
     */
    public function scopeYuksekSkor($query, float $minSkor = 0.7)
    {
        return $query->where('eslestirme_skoru', '>=', $minSkor);
    }

    /**
     * Scope: Sunulmuş eşleştirmeler
     */
    public function scopeSunulmus($query)
    {
        return $query->whereIn('durum', ['sunuldu', 'kabul_edildi', 'reddedildi']);
    }

    /**
     * Scope: Bekleyen eşleştirmeler
     */
    public function scopeBekleyen($query)
    {
        return $query->whereIn('durum', ['yeni', 'incelendi']);
    }

    /**
     * Durum etiketini al
     */
    public function getDurumLabelAttribute(): string
    {
        return match($this->durum) {
            'yeni' => 'Yeni',
            'incelendi' => 'İncelendi',
            'sunuldu' => 'Sunuldu',
            'reddedildi' => 'Reddedildi',
            'kabul_edildi' => 'Kabul Edildi',
            default => ucfirst($this->durum)
        };
    }

    /**
     * Durum rengini al
     */
    public function getDurumRenkAttribute(): string
    {
        return match($this->durum) {
            'yeni' => 'blue',
            'incelendi' => 'yellow',
            'sunuldu' => 'purple',
            'reddedildi' => 'red',
            'kabul_edildi' => 'green',
            default => 'gray'
        };
    }

    /**
     * Durum ikonunu al
     */
    public function getDurumIkonAttribute(): string
    {
        return match($this->durum) {
            'yeni' => 'fas fa-star',
            'incelendi' => 'fas fa-eye',
            'sunuldu' => 'fas fa-presentation',
            'reddedildi' => 'fas fa-times-circle',
            'kabul_edildi' => 'fas fa-check-circle',
            default => 'fas fa-info-circle'
        };
    }

    /**
     * Eşleştirme skoru yüzdesi
     */
    public function getSkorYuzdeAttribute(): int
    {
        return $this->eslestirme_skoru ? (int)($this->eslestirme_skoru * 100) : 0;
    }

    /**
     * Eşleştirme kalitesi
     */
    public function getKaliteAttribute(): string
    {
        if (!$this->eslestirme_skoru) {
            return 'Bilinmiyor';
        }

        return match(true) {
            $this->eslestirme_skoru >= 0.9 => 'Mükemmel',
            $this->eslestirme_skoru >= 0.8 => 'Çok İyi',
            $this->eslestirme_skoru >= 0.7 => 'İyi',
            $this->eslestirme_skoru >= 0.6 => 'Orta',
            $this->eslestirme_skoru >= 0.5 => 'Zayıf',
            default => 'Çok Zayıf'
        };
    }

    /**
     * Eşleştirme yaşı (gün)
     */
    public function getYasiAttribute(): int
    {
        return $this->olusturma_tarihi->diffInDays(now());
    }

    /**
     * Sunumdan bu yana geçen gün
     */
    public function getSunumdanberiAttribute(): ?int
    {
        return $this->sunum_tarihi ? $this->sunum_tarihi->diffInDays(now()) : null;
    }

    /**
     * Eşleştirme durumunu güncelle
     */
    public function durumGuncelle(string $yeniDurum, string $not = null, int $guncelleyenId = null): void
    {
        $this->update([
            'durum' => $yeniDurum,
            'personel_notu' => $not ? ($this->personel_notu ? $this->personel_notu . "\n\n" . $not : $not) : $this->personel_notu,
            'guncelleyen_id' => $guncelleyenId ?? auth()->id(),
        ]);

        // Talep aktivitesi ekle
        $this->talep->aktiviteEkle('eslestirme_durum_degisiklik', [
            'eslestirme_id' => $this->id,
            'eski_durum' => $this->getOriginal('durum'),
            'yeni_durum' => $yeniDurum,
            'mulk_id' => $this->mulk_id,
            'mulk_type' => $this->mulk_type,
            'not' => $not,
        ], $guncelleyenId);

        // Durum değişikliği bildirimini gönder
        $bildirimService = app(\App\Services\EslestirmeBildirimService::class);
        if ($yeniDurum === 'kabul_edildi') {
            $bildirimService->eslestirmeKabulBildirimi($this);
        } elseif ($yeniDurum === 'reddedildi') {
            $bildirimService->eslestirmeRedBildirimi($this);
        }
    }

    /**
     * Sunum bilgilerini güncelle
     */
    public function sunumBilgileriniGuncelle(int $sunanPersonelId, string $geriBildirim = null): void
    {
        $this->update([
            'sunum_tarihi' => now(),
            'sunan_personel_id' => $sunanPersonelId,
            'musteri_geri_bildirimi' => $geriBildirim,
            'durum' => 'sunuldu',
            'guncelleyen_id' => auth()->id(),
        ]);

        // Talep aktivitesi ekle
        $this->talep->aktiviteEkle('portfoy_sunuldu', [
            'eslestirme_id' => $this->id,
            'mulk_id' => $this->mulk_id,
            'mulk_type' => $this->mulk_type,
            'sunan_personel_id' => $sunanPersonelId,
            'geri_bildirim' => $geriBildirim,
        ]);

        // Sunum bildirimi gönder
        $bildirimService = app(\App\Services\EslestirmeBildirimService::class);
        $bildirimService->eslestirmeSunulduBildirimi($this);
    }

    /**
     * Müşteri geri bildirimini kaydet
     */
    public function geriBildirimKaydet(string $geriBildirim, string $durum = null): void
    {
        $updateData = [
            'musteri_geri_bildirimi' => $geriBildirim,
            'guncelleyen_id' => auth()->id(),
        ];

        if ($durum) {
            $updateData['durum'] = $durum;
        }

        $this->update($updateData);

        // Talep aktivitesi ekle
        $this->talep->aktiviteEkle('musteri_geri_bildirim', [
            'eslestirme_id' => $this->id,
            'mulk_id' => $this->mulk_id,
            'mulk_type' => $this->mulk_type,
            'geri_bildirim' => $geriBildirim,
            'durum' => $durum,
        ]);
    }

    /**
     * Eşleştirme detaylarını güncelle
     */
    public function detaylariGuncelle(array $detaylar): void
    {
        $mevcutDetaylar = $this->eslestirme_detaylari ?? [];
        $yeniDetaylar = array_merge($mevcutDetaylar, $detaylar);

        $this->update([
            'eslestirme_detaylari' => $yeniDetaylar,
            'guncelleyen_id' => auth()->id(),
        ]);
    }

    /**
     * Model boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
            if (!$model->olusturan_id) {
                $model->olusturan_id = auth()->id();
            }
            if (!isset($model->aktif_mi)) {
                $model->aktif_mi = true;
            }
            if (!$model->durum) {
                $model->durum = 'yeni';
            }
        });

        static::updating(function ($model) {
            $model->guncelleyen_id = auth()->id();
        });
    }
}