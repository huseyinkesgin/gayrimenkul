<?php

namespace App\Models\Mulk;

use App\Models\BaseModel;
use App\Models\Musteri\Musteri;
use App\Models\MusteriMulkIliskisi;
use App\Models\MusteriHizmet;
use App\Models\MulkOzellik;
use App\Enums\MulkKategorisi;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\Rule;

abstract class BaseMulk extends BaseModel
{
    protected $table = 'mulkler';

    protected $fillable = [
        'baslik',
        'aciklama',
        'fiyat',
        'para_birimi',
        'metrekare',
        'durum',
        'yayinlanma_tarihi',
        'aktif_mi',
        'siralama',
    ];

    protected $casts = [
        'fiyat' => 'decimal:2',
        'metrekare' => 'decimal:2',
        'yayinlanma_tarihi' => 'datetime',
        'aktif_mi' => 'boolean',
        'siralama' => 'integer',
    ];

    protected $searchableFields = [
        'baslik',
        'aciklama',
    ];

    protected $sortableFields = [
        'baslik',
        'fiyat',
        'metrekare',
        'yayinlanma_tarihi',
        'olusturma_tarihi',
        'guncelleme_tarihi',
    ];

    protected $defaultSortField = 'yayinlanma_tarihi';
    protected $defaultSortDirection = 'desc';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Otomatik olarak mulk_type'ı set et
        static::creating(function ($model) {
            if (empty($model->mulk_type)) {
                $model->mulk_type = $model->getMulkType();
            }
        });
    }

    /**
     * Mülk tipini döndür (alt sınıflar tarafından implement edilmeli)
     */
    abstract public function getMulkType(): string;

    /**
     * Mülk kategorisini döndür (alt sınıflar tarafından implement edilmeli)
     */
    abstract public function getMulkKategorisi(): MulkKategorisi;

    /**
     * Bu mülk tipi için geçerli özellikleri döndür
     */
    abstract public function getValidProperties(): array;

    /**
     * Bu mülk tipi için validation kurallarını döndür
     */
    abstract public function getSpecificValidationRules(): array;

    /**
     * Mülk özellikleri ilişkisi
     */
    public function ozellikler(): HasMany
    {
        return $this->hasMany(MulkOzellik::class, 'mulk_id')
            ->where('mulk_type', $this->getMulkType());
    }

    /**
     * Aktif mülk özellikleri
     */
    public function aktifOzellikler(): HasMany
    {
        return $this->ozellikler()->where('aktif_mi', true);
    }

    /**
     * Müşteri ilişkileri
     */
    public function musteriIliskileri(): HasMany
    {
        return $this->hasMany(MusteriMulkIliskisi::class, 'mulk_id')
            ->where('mulk_type', $this->getMulkType());
    }

    /**
     * Aktif müşteri ilişkileri
     */
    public function aktifMusteriIliskileri(): HasMany
    {
        return $this->musteriIliskileri()->where('durum', 'aktif');
    }

    /**
     * İlgili müşteriler
     */
    public function musteriler(): BelongsToMany
    {
        return $this->belongsToMany(Musteri::class, 'musteri_mulk_iliskileri', 'mulk_id', 'musteri_id')
            ->withPivot(['iliski_tipi', 'durum', 'ilgi_seviyesi', 'notlar', 'baslangic_tarihi'])
            ->withTimestamps();
    }

    /**
     * Bu mülk için verilen hizmetler
     */
    public function hizmetler(): HasMany
    {
        return $this->hasMany(MusteriHizmet::class, 'mulk_id')
            ->where('mulk_type', $this->getMulkType());
    }

    /**
     * Mülk resimleri (polymorphic)
     */
    public function resimler()
    {
        return $this->morphMany(\App\Models\Resim::class, 'imageable')
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Mülk galeri resimleri
     */
    public function galeriResimleri()
    {
        return $this->morphMany(\App\Models\Resim::class, 'imageable')
            ->where('kategori', \App\Enums\ResimKategorisi::GALERI->value)
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Mülk kapak resmi
     */
    public function kapakResmi()
    {
        return $this->morphOne(\App\Models\Resim::class, 'imageable')
            ->where('kategori', \App\Enums\ResimKategorisi::KAPAK_RESMI->value)
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * İç mekan resimleri
     */
    public function icMekanResimleri()
    {
        return $this->morphMany(\App\Models\Resim::class, 'imageable')
            ->where('kategori', \App\Enums\ResimKategorisi::IC_MEKAN->value)
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Dış mekan resimleri
     */
    public function disMekanResimleri()
    {
        return $this->morphMany(\App\Models\Resim::class, 'imageable')
            ->where('kategori', \App\Enums\ResimKategorisi::DIS_MEKAN->value)
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Plan resimleri
     */
    public function planResimleri()
    {
        return $this->morphMany(\App\Models\Resim::class, 'imageable')
            ->where('kategori', \App\Enums\ResimKategorisi::PLAN->value)
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Harita resimleri (sadece arsa ve işyeri için)
     */
    public function haritaResimleri()
    {
        return $this->morphMany(\App\Models\Resim::class, 'imageable')
            ->whereIn('kategori', [
                \App\Enums\ResimKategorisi::UYDU->value,
                \App\Enums\ResimKategorisi::OZNITELIK->value,
                \App\Enums\ResimKategorisi::BUYUKSEHIR->value,
                \App\Enums\ResimKategorisi::EGIM->value,
                \App\Enums\ResimKategorisi::EIMAR->value,
            ])
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Mülk dökümanları (polymorphic)
     */
    public function dokumanlar()
    {
        return $this->morphMany(\App\Models\Dokuman::class, 'documentable')
            ->where('aktif_mi', true)
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Mülk notları (polymorphic)
     */
    public function notlar()
    {
        return $this->morphMany(\App\Models\Not::class, 'notable')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Temel validation kuralları
     */
    public static function getBaseValidationRules(): array
    {
        return [
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string|max:5000',
            'fiyat' => 'nullable|numeric|min:0|max:999999999999.99',
            'para_birimi' => 'nullable|string|size:3|in:TRY,USD,EUR',
            'metrekare' => 'nullable|numeric|min:0|max:999999.99',
            'durum' => ['required', Rule::in(['aktif', 'pasif', 'satildi', 'kiralandi'])],
            'yayinlanma_tarihi' => 'nullable|date',
            'aktif_mi' => 'boolean',
            'siralama' => 'integer|min:0',
        ];
    }

    /**
     * Tüm validation kurallarını döndür
     */
    public function getValidationRules(): array
    {
        return array_merge(
            self::getBaseValidationRules(),
            $this->getSpecificValidationRules()
        );
    }

    /**
     * Özellik ekleme
     */
    public function addProperty(string $name, $value, string $type = 'metin', ?string $unit = null): MulkOzellik
    {
        // Geçerli özellik kontrolü
        $validProperties = $this->getValidProperties();
        if (!empty($validProperties) && !in_array($name, $validProperties)) {
            throw new \InvalidArgumentException("'{$name}' bu mülk tipi için geçerli bir özellik değil.");
        }

        return $this->ozellikler()->create([
            'mulk_type' => $this->getMulkType(),
            'ozellik_adi' => $name,
            'ozellik_degeri' => is_array($value) ? $value : [$value],
            'ozellik_tipi' => $type,
            'birim' => $unit,
            'aktif_mi' => true,
        ]);
    }

    /**
     * Özellik değeri alma
     */
    public function getProperty(string $name, $default = null)
    {
        $ozellik = $this->aktifOzellikler()
            ->where('ozellik_adi', $name)
            ->first();

        if (!$ozellik) {
            return $default;
        }

        $value = $ozellik->ozellik_degeri;

        // JSON array ise ve tek eleman varsa direkt değeri döndür
        if (is_array($value) && count($value) === 1) {
            return $value[0];
        }

        return $value;
    }

    /**
     * Özellik değeri güncelleme
     */
    public function updateProperty(string $name, $value): bool
    {
        $ozellik = $this->aktifOzellikler()
            ->where('ozellik_adi', $name)
            ->first();

        if (!$ozellik) {
            return false;
        }

        return $ozellik->update([
            'ozellik_degeri' => is_array($value) ? $value : [$value]
        ]);
    }

    /**
     * Özellik silme
     */
    public function removeProperty(string $name): bool
    {
        return $this->aktifOzellikler()
            ->where('ozellik_adi', $name)
            ->delete() > 0;
    }

    /**
     * Tüm özellikleri array olarak döndür
     */
    public function getPropertiesArray(): array
    {
        $properties = [];

        foreach ($this->aktifOzellikler as $ozellik) {
            $value = $ozellik->ozellik_degeri;

            // Tek eleman varsa direkt değeri al
            if (is_array($value) && count($value) === 1) {
                $value = $value[0];
            }

            $properties[$ozellik->ozellik_adi] = [
                'value' => $value,
                'type' => $ozellik->ozellik_tipi,
                'unit' => $ozellik->birim,
            ];
        }

        return $properties;
    }

    /**
     * Fiyat formatlanmış olarak döndür
     */
    public function getFormattedPriceAttribute(): string
    {
        if (!$this->fiyat) {
            return 'Belirtilmemiş';
        }

        $currency = match ($this->para_birimi) {
            'USD' => '$',
            'EUR' => '€',
            default => '₺'
        };

        return number_format($this->fiyat, 0, ',', '.') . ' ' . $currency;
    }

    /**
     * Metrekare formatlanmış olarak döndür
     */
    public function getFormattedAreaAttribute(): string
    {
        if (!$this->metrekare) {
            return 'Belirtilmemiş';
        }

        return number_format($this->metrekare, 0, ',', '.') . ' m²';
    }

    /**
     * M2 başına fiyat hesapla
     */
    public function getPricePerSquareMeterAttribute(): ?float
    {
        if (!$this->fiyat || !$this->metrekare || $this->metrekare <= 0) {
            return null;
        }

        return round($this->fiyat / $this->metrekare, 2);
    }

    /**
     * Durum badge rengi
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->durum) {
            'aktif' => 'green',
            'pasif' => 'gray',
            'satildi' => 'red',
            'kiralandi' => 'blue',
            default => 'gray'
        };
    }

    /**
     * Durum etiketi
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->durum) {
            'aktif' => 'Aktif',
            'pasif' => 'Pasif',
            'satildi' => 'Satıldı',
            'kiralandi' => 'Kiralandı',
            default => 'Bilinmiyor'
        };
    }

    /**
     * Mülk URL'si
     */
    public function getUrlAttribute(): string
    {
        $type = strtolower($this->getMulkType());
        return route("mulk.{$type}.show", $this->id);
    }

    /**
     * SEO dostu slug
     */
    public function getSlugAttribute(): string
    {
        return $this->generateUniqueSlug($this->baslik);
    }

    /**
     * Aktif mülkler scope
     */
    public function scopeAktifMulkler($query)
    {
        return $query->where('durum', 'aktif')->where('aktif_mi', true);
    }

    /**
     * Fiyat aralığı scope
     */
    public function scopeFiyatAraliginda($query, ?float $minFiyat = null, ?float $maxFiyat = null)
    {
        if ($minFiyat !== null) {
            $query->where('fiyat', '>=', $minFiyat);
        }

        if ($maxFiyat !== null) {
            $query->where('fiyat', '<=', $maxFiyat);
        }

        return $query;
    }

    /**
     * Metrekare aralığı scope
     */
    public function scopeMetrekareAraliginda($query, ?float $minMetrekare = null, ?float $maxMetrekare = null)
    {
        if ($minMetrekare !== null) {
            $query->where('metrekare', '>=', $minMetrekare);
        }

        if ($maxMetrekare !== null) {
            $query->where('metrekare', '<=', $maxMetrekare);
        }

        return $query;
    }

    /**
     * Mülk tipine göre scope
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('mulk_type', $type);
    }

    /**
     * Yayınlanma tarihine göre scope
     */
    public function scopeYayinlanan($query)
    {
        return $query->whereNotNull('yayinlanma_tarihi')
            ->where('yayinlanma_tarihi', '<=', now());
    }

    /**
     * Display name override
     */
    public function getDisplayName(): string
    {
        return $this->baslik;
    }

    public function scopeOlusturan($query, $userId)
    {
        return $query->where('olusturan_id', $userId);
    }
}
