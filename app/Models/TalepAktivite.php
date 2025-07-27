<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Talep Aktivite Modeli
 * 
 * Bu model müşteri taleplerinin aktivite geçmişini tutar.
 * 
 * @property int $id
 * @property int $talep_id
 * @property string $tip
 * @property array $detaylar
 * @property int $olusturan_id
 * @property timestamps
 */
class TalepAktivite extends Model
{
    use HasFactory;

    protected $table = 'talep_aktiviteleri';

    protected $fillable = [
        'talep_id',
        'tip',
        'detaylar',
        'olusturan_id',
    ];

    protected $casts = [
        'detaylar' => 'array',
    ];

    /**
     * Talep ilişkisi
     */
    public function talep(): BelongsTo
    {
        return $this->belongsTo(MusteriTalep::class, 'talep_id');
    }

    /**
     * Oluşturan kullanıcı ilişkisi
     */
    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    /**
     * Aktivite tipi etiketini al
     */
    public function getTipLabelAttribute(): string
    {
        return match($this->tip) {
            'olusturuldu' => 'Talep Oluşturuldu',
            'durum_degisiklik' => 'Durum Değişikliği',
            'not_eklendi' => 'Not Eklendi',
            'gereksinim_eklendi' => 'Gereksinim Eklendi',
            'eslestirme_bulundu' => 'Eşleştirme Bulundu',
            'eslestirme_iptal' => 'Eşleştirme İptal',
            'musteri_iletisim' => 'Müşteri İletişim',
            'portfoy_gosterim' => 'Portföy Gösterim',
            'teklif_verildi' => 'Teklif Verildi',
            'sozlesme_imzalandi' => 'Sözleşme İmzalandı',
            default => ucfirst(str_replace('_', ' ', $this->tip))
        };
    }

    /**
     * Aktivite ikonunu al
     */
    public function getIkonAttribute(): string
    {
        return match($this->tip) {
            'olusturuldu' => 'fas fa-plus-circle',
            'durum_degisiklik' => 'fas fa-exchange-alt',
            'not_eklendi' => 'fas fa-sticky-note',
            'gereksinim_eklendi' => 'fas fa-list-ul',
            'eslestirme_bulundu' => 'fas fa-handshake',
            'eslestirme_iptal' => 'fas fa-times-circle',
            'musteri_iletisim' => 'fas fa-phone',
            'portfoy_gosterim' => 'fas fa-eye',
            'teklif_verildi' => 'fas fa-file-contract',
            'sozlesme_imzalandi' => 'fas fa-signature',
            default => 'fas fa-info-circle'
        };
    }

    /**
     * Aktivite rengini al
     */
    public function getRenkAttribute(): string
    {
        return match($this->tip) {
            'olusturuldu' => 'green',
            'durum_degisiklik' => 'blue',
            'not_eklendi' => 'yellow',
            'gereksinim_eklendi' => 'purple',
            'eslestirme_bulundu' => 'green',
            'eslestirme_iptal' => 'red',
            'musteri_iletisim' => 'blue',
            'portfoy_gosterim' => 'indigo',
            'teklif_verildi' => 'orange',
            'sozlesme_imzalandi' => 'green',
            default => 'gray'
        };
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
        });
    }
}