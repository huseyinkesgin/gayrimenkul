<?php

namespace App\Models;

use App\Models\Lokasyon\Sehir;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Semt;
use App\Models\Lokasyon\Mahalle;

class Adres extends BaseModel
{
    protected $table = 'adres';

    protected $fillable = [
        'adres_adi',
        'adres_detay',
        'posta_kodu',
        'sehir_id',
        'ilce_id',
        'semt_id',
        'mahalle_id',
        'varsayilan_mi',
        'aktif_mi',
        'addressable_id',
        'addressable_type',
    ];

    protected $casts = [
        'varsayilan_mi' => 'boolean',
        'aktif_mi' => 'boolean',
    ];

    /**
     * Morph ilişkisi - Bu adres hangi modele ait
     */
    public function addressable()
    {
        return $this->morphTo();
    }

    /**
     * Şehir ilişkisi
     */
    public function sehir()
    {
        return $this->belongsTo(Sehir::class, 'sehir_id');
    }

    /**
     * İlçe ilişkisi
     */
    public function ilce()
    {
        return $this->belongsTo(Ilce::class, 'ilce_id');
    }

    /**
     * Semt ilişkisi
     */
    public function semt()
    {
        return $this->belongsTo(Semt::class, 'semt_id');
    }

    /**
     * Mahalle ilişkisi
     */
    public function mahalle()
    {
        return $this->belongsTo(Mahalle::class, 'mahalle_id');
    }

    /**
     * Varsayılan adres scope
     */
    public function scopeDefault($query)
    {
        return $query->where('varsayilan_mi', true);
    }

    /**
     * Tam adres bilgisini döndür
     */
    public function getFullAddressAttribute()
    {
        $parts = [
            $this->adres_detay,
            $this->mahalle?->ad,
            $this->semt?->ad,
            $this->ilce?->ad,
            $this->sehir?->ad,
        ];

        return implode(', ', array_filter($parts));
    }
}
