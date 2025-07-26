<?php

namespace App\Models\Lokasyon;

use App\Models\BaseModel;
use App\Models\Lokasyon\Semt;


class Mahalle extends BaseModel
{
    protected $table = 'mahalle';

   
    protected $fillable = [
        'semt_id',
        'ad',
        'posta_kodu',
        'aktif_mi',
        'siralama',
    ];

    protected $casts = [
        'aktif_mi' => 'boolean',
    ];


    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('ad', 'like', "%{$term}%")
                ->orWhereHas('semt', function ($q) use ($term) {
                    $q->where('ad', 'like', "%{$term}%");
                });
        });
    }

    public function semt()
    {
        return $this->belongsTo(Semt::class, 'semt_id');
    }
}
