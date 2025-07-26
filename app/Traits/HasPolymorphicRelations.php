<?php

namespace App\Traits;

use App\Models\Adres;
use App\Models\Resim;
use App\Models\Dokuman;
use App\Models\Not;

trait HasPolymorphicRelations
{
    /**
     * Adresleri (polymorphic)
     */
    public function adresler()
    {
        return $this->morphMany(Adres::class, 'addressable');
    }

    /**
     * Varsayılan adres
     */
    public function varsayilanAdres()
    {
        return $this->morphOne(Adres::class, 'addressable')
            ->where('varsayilan_mi', true)
            ->latest('olusturma_tarihi');
    }

    /**
     * Resimleri (polymorphic)
     */
    public function resimler()
    {
        return $this->morphMany(Resim::class, 'imageable');
    }

    /**
     * Aktif resimleri
     */
    public function aktifResimler()
    {
        return $this->morphMany(Resim::class, 'imageable')
            ->where('aktif_mi', true);
    }

    /**
     * Galeri resimleri
     */
    public function galeriResimleri()
    {
        return $this->morphMany(Resim::class, 'imageable')
            ->where('kategori', 'galeri')
            ->where('aktif_mi', true)
            ->orderBy('olusturma_tarihi');
    }

    /**
     * Avatar/Logo resmi
     */
    public function avatarResmi()
    {
        return $this->morphOne(Resim::class, 'imageable')
            ->whereIn('kategori', ['avatar', 'logo'])
            ->where('aktif_mi', true)
            ->latest('olusturma_tarihi');
    }

    /**
     * Dökümanları (polymorphic)
     */
    public function dokumanlar()
    {
        return $this->morphMany(Dokuman::class, 'documentable');
    }

    /**
     * Aktif dökümanlar
     */
    public function aktifDokumanlar()
    {
        return $this->morphMany(Dokuman::class, 'documentable')
            ->where('aktif_mi', true);
    }

    /**
     * Döküman tipine göre dökümanlar
     */
    public function dokumanlarByTip(string $tip)
    {
        return $this->morphMany(Dokuman::class, 'documentable')
            ->where('dokuman_tipi', $tip)
            ->where('aktif_mi', true);
    }

    /**
     * Notları (polymorphic)
     */
    public function notlar()
    {
        return $this->morphMany(Not::class, 'notable');
    }

    /**
     * Aktif notlar
     */
    public function aktifNotlar()
    {
        return $this->morphMany(Not::class, 'notable')
            ->where('aktif_mi', true)
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Önemli notlar
     */
    public function onemliNotlar()
    {
        return $this->morphMany(Not::class, 'notable')
            ->where('oncelik', '>=', 8)
            ->where('aktif_mi', true)
            ->orderBy('oncelik', 'desc')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Son not
     */
    public function sonNot()
    {
        return $this->morphOne(Not::class, 'notable')
            ->where('aktif_mi', true)
            ->latest('olusturma_tarihi');
    }
}