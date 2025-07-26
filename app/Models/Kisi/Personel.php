<?php

namespace App\Models\Kisi;

use App\Models\Adres;
use App\Models\BaseModel;
use App\Models\Resim;
use App\Models\Kisi\Departman;
use App\Models\Kisi\Sube;
use App\Models\Kisi\Pozisyon;
use App\Enums\ResimKategorisi;

class Personel extends BaseModel
{
    protected $table = 'personel';

    protected $fillable = [
        'kisi_id',
        'sube_id',
        'departman_id',
        'pozisyon_id',
        'ise_baslama_tarihi',
        'isten_ayrilma_tarihi',
        'calisma_durumu',
        'calisma_sekli',
        'personel_no',
        'siralama',
        'aktif_mi',
    ];

    protected $casts = [
        'aktif_mi' => 'boolean',
        'ise_baslama_tarihi' => 'date',
        'isten_ayrilma_tarihi' => 'date',
    ];

    /**
     * Personelin kişi bilgileri
     */
    public function kisi()
    {
        return $this->belongsTo(Kisi::class);
    }

    /**
     * Personelin departmanı
     */
    public function departman()
    {
        return $this->belongsTo(Departman::class);
    }

    /**
     * Personelin şubesi
     */
    public function sube()
    {
        return $this->belongsTo(Sube::class);
    }

    /**
     * Personelin pozisyonu
     */
    public function pozisyon()
    {
        return $this->belongsTo(Pozisyon::class);
    }

    /**
     * Personelin adresleri (morph ilişkisi)
     */
    public function adresler()
    {
        return $this->morphMany(Adres::class, 'addressable');
    }

    /**
     * Personelin varsayılan adresi
     */
    public function varsayilanAdres()
    {
        return $this->morphOne(Adres::class, 'addressable')
            ->where('varsayilan_mi', true)
            ->latest('olusturma_tarihi');
    }

    /**
     * Personelin resimleri (morph ilişkisi)
     */
    public function resimler()
    {
        return $this->morphMany(Resim::class, 'imageable')
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Personelin avatarı
     */
    public function avatar()
    {
        return $this->morphOne(Resim::class, 'imageable')
            ->where('kategori', ResimKategorisi::AVATAR->value)
            ->where('aktif_mi', true)
            ->latest('olusturma_tarihi');
    }

    /**
     * Tam ad accessor (kişi bilgilerinden)
     */
    public function getFullNameAttribute()
    {
        return $this->kisi ? $this->kisi->full_name : '';
    }

    /**
     * Personelin rolleri (many-to-many)
     */
    public function roller()
    {
        return $this->belongsToMany(PersonelRol::class, 'personel_personel_rolu', 'personel_id', 'personel_rol_id');
    }

    /**
     * Aktif personel scope
     */
    public function scopeAktifPersonel($query)
    {
        return $query->where('calisma_durumu', 'Aktif');
    }

    /**
     * Arama scope
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('personel_no', 'like', "%{$term}%")
              ->orWhere('calisma_durumu', 'like', "%{$term}%")
              ->orWhere('calisma_sekli', 'like', "%{$term}%")
              ->orWhereHas('kisi', function($subQ) use ($term) {
                  $subQ->where('ad', 'like', "%{$term}%")
                       ->orWhere('soyad', 'like', "%{$term}%")
                       ->orWhere('tc_kimlik_no', 'like', "%{$term}%");
              });
        });
    }
}
