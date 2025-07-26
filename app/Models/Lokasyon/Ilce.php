<?php

namespace App\Models\Lokasyon;

use App\Models\BaseModel;
use App\Models\Lokasyon\Sehir;

class Ilce extends BaseModel
{

    protected $table = 'ilce';

    protected $fillable = [
        'sehir_id',
        'ad',
        'aktif_mi',
        'siralama',
    ];

    protected $casts = [
        'aktif_mi' => 'boolean',
    ];

/*
  ┌─────────────────────────────────────────────────────────────────────────────┐
  │     SCOPE LİSTESİ                                                           │
  └─────────────────────────────────────────────────────────────────────────────┘
 */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('ad', 'like', "%{$term}%")
                ->orWhereHas('sehir', function ($q) use ($term) {
                    $q->where('ad', 'like', "%{$term}%");
                });
        });
    }


    public function sehir()
    {
        return $this->belongsTo(Sehir::class);
    }

    public function semtler()
    {
        return $this->hasMany(Semt::class);
    }
}
