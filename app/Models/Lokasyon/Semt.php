<?php

namespace App\Models\Lokasyon;

use App\Models\BaseModel;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Sehir;


class Semt extends BaseModel
{

    protected $table = 'semt';

    protected $fillable = [
        'ilce_id',
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
                ->orWhereHas('ilce', function ($q) use ($term) {
                    $q->where('ad', 'like', "%{$term}%");
                });
        });
    }



    public function ilce()
    {
        return $this->belongsTo(Ilce::class, 'ilce_id');
    }

    /**
     * Doğrudan şehir ilişkisi - ilçe üzerinden
     */
    public function sehir()
    {
        return $this->hasOneThrough(Sehir::class, Ilce::class, 'id', 'id', 'ilce_id', 'sehir_id');
    }

    public function mahalleler()
    {
        return $this->hasMany(Mahalle::class, 'semt_id');
    }

  
}
