<?php
namespace App\Models\Kisi;

use App\Models\Adres;
use App\Models\Resim;
use App\Models\BaseModel;
use App\Models\Kisi\Personel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;

class Kisi extends BaseModel
{
    protected $table = 'kisi';

    protected $fillable = [
        'ad',
        'soyad',
        'tc_kimlik_no',
        'dogum_tarihi',
        'cinsiyet', // 'Erkek', 'Kadın', 'Diğer'
        'dogum_yeri',
        'medeni_hali', // 'Bekar', 'Evli', 'Dul
        'email',
        'telefon',
    ];

    protected $casts = [
        'dogum_tarihi' => 'date',
    ];

    /**
     * Kişinin personel kayıtları
     */
    public function personeller()
    {
        return $this->hasMany(Personel::class);
    }



    /**
     * Kişinin adresleri (morph ilişkisi)
     */
    public function adresler()
    {
        return $this->morphMany(Adres::class, 'addressable');
    }

    /**
     * Kişinin varsayılan adresi
     */
    public function varsayilanAdres()
    {
        return $this->morphOne(Adres::class, 'addressable')
            ->where('varsayilan_mi', true)
            ->latest('olusturma_tarihi');
    }

    /**
     * Tam ad accessor
     */
    public function getFullNameAttribute()
    {
        return $this->ad . ' ' . $this->soyad;
    }

    /**
     * Arama scope
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('ad', 'like', "%{$term}%")
              ->orWhere('soyad', 'like', "%{$term}%")
              ->orWhere('tc_kimlik_no', 'like', "%{$term}%");
        });
    }
}
