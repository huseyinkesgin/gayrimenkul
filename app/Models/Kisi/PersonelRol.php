<?php

namespace App\Models\Kisi;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PersonelRol extends BaseModel
{

    protected $table = 'personel_rol';

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
        return $this->belongsToMany(Personel::class, 'personel_personel_rolu', 'personel_rol_id', 'personel_id');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('ad', 'like', "%{$term}%")
            ->orWhere('not', 'like', "%{$term}%");
    }
}
