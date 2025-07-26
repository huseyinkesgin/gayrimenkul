<?php

namespace App\Models\Kisi;

use App\Models\BaseModel;
use App\Models\Kisi\Personel;

class Departman extends BaseModel
{


    protected $table = 'departman';

    protected $fillable = [
        'ad',
        'aciklama',
        'yonetici_id',
        'aktif_mi',
        'siralama'
    ];


    protected $casts = [
        'aktif_mi' => 'boolean',
    ];

    public function personeller()
    {
        return $this->hasMany(Personel::class);
    }

    public function yonetici()
    {
        return $this->belongsTo(Personel::class, 'yonetici_id');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('ad', 'like', "%{$term}%")
            ->orWhere('aciklama', 'like', "%{$term}%")
            ->orWhereHas('yonetici.kisi', function($q) use ($term) {
                $q->where('ad', 'like', "%{$term}%")
                  ->orWhere('soyad', 'like', "%{$term}%");
            });
    }
}
