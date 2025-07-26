<?php

namespace App\Models\Kisi;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class Pozisyon extends BaseModel
{
    protected $table = 'pozisyon';

    protected $fillable = [
        'ad',
        'aktif_mi',
        'siralama',
    ];


    protected $casts = [
        'aktif_mi' => 'boolean',
    ];

    public function personeller()
    {
        return $this->hasMany(Personel::class);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('ad', 'like', "%{$term}%")
            ->orWhere('not', 'like', "%{$term}%");
    }
}
