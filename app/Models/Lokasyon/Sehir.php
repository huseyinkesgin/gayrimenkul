<?php

namespace App\Models\Lokasyon;

use App\Models\BaseModel;



class Sehir extends BaseModel
{

    protected $table = 'sehir';

    protected $fillable = [
        'ad',
        'plaka_kodu',
        'telefon_kodu',
        'aktif_mi',
        'siralama',
    ];


    protected $casts = [
        'aktif_mi' => 'boolean',
    ];

    public function ilceler()
    {
        return $this->hasMany(Ilce::class);
    }



    public function scopeSearch($query, string $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('ad', 'like', "%{$term}%")
              ->orWhere('plaka_kodu', 'like', "%{$term}%")
              ->orWhere('telefon_kodu', 'like', "%{$term}%");
        });
    }



}
