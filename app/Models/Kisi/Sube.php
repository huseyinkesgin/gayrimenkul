<?php

namespace App\Models\Kisi;

use App\Models\Adres;
use App\Models\BaseModel;
use App\Models\Kisi\Personel;
use Illuminate\Database\Eloquent\Model;

class Sube extends BaseModel
{
    protected $table = 'sube';

    protected $fillable = [
        'ad',
        'kod',
        'telefon',
        'email',
        'siralama',
        'aktif_mi',
    ];
    protected $casts = [
        'aktif_mi' => 'boolean',
    ];

    /**
     * Şubenin personelleri
     */

    public function personeller()
    {
        return $this->hasMany(Personel::class);
    }

    public function adresler()
    {
        return $this->morphMany(Adres::class, 'addressable');
    }

    }


