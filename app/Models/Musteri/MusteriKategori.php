<?php

namespace App\Models\Musteri;

use App\Models\BaseModel;
use App\Enums\MusteriKategorisi;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MusteriKategori extends BaseModel
{
    protected $table = 'musteri_kategori';

    protected $fillable = [
        'value',
        'label',
        'description',
        'color',
        'icon',
        'aktif_mi',
        'siralama',
    ];

    protected $casts = [
        'aktif_mi' => 'boolean',
        'siralama' => 'integer',
    ];

    protected $searchableFields = [
        'label',
        'description',
    ];

    protected $sortableFields = [
        'label',
        'siralama',
        'olusturma_tarihi',
    ];

    /**
     * Kategoriye sahip müşteriler
     */
    public function musteriler(): BelongsToMany
    {
        return $this->belongsToMany(Musteri::class, 'musteri_musteri_kategori', 'musteri_kategori_id', 'musteri_id')
            ->withPivot(['baslangic_tarihi', 'bitis_tarihi', 'aktif_mi', 'notlar'])
            ->withTimestamps();
    }

    /**
     * Aktif müşteriler
     */
    public function aktifMusteriler(): BelongsToMany
    {
        return $this->musteriler()->wherePivot('aktif_mi', true);
    }

    /**
     * Enum değerini döndür
     */
    public function getEnumAttribute(): ?MusteriKategorisi
    {
        return MusteriKategorisi::fromValue($this->value);
    }

    /**
     * Kategori rengini döndür
     */
    public function getColorClassAttribute(): string
    {
        return match ($this->color) {
            'blue' => 'bg-blue-100 text-blue-800',
            'green' => 'bg-green-100 text-green-800',
            'purple' => 'bg-purple-100 text-purple-800',
            'orange' => 'bg-orange-100 text-orange-800',
            'gray' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Müşteri sayısı
     */
    public function getMusteriSayisiAttribute(): int
    {
        return $this->aktifMusteriler()->count();
    }

    /**
     * Display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->label;
    }

    /**
     * Aktif kategoriler scope
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif_mi', true);
    }

    /**
     * Sıraya göre scope
     */
    public function scopeSirali($query)
    {
        return $query->orderBy('siralama')->orderBy('label');
    }

    /**
     * Değere göre scope
     */
    public function scopeByValue($query, string $value)
    {
        return $query->where('value', $value);
    }

    /**
     * Satış ile ilgili kategoriler scope
     */
    public function scopeSalesRelated($query)
    {
        $salesValues = array_map(fn($case) => $case->value, MusteriKategorisi::salesRelated());
        return $query->whereIn('value', $salesValues);
    }

    /**
     * İş ile ilgili kategoriler scope
     */
    public function scopeBusinessRelated($query)
    {
        $businessValues = array_map(fn($case) => $case->value, MusteriKategorisi::businessRelated());
        return $query->whereIn('value', $businessValues);
    }

    /**
     * Validation kuralları
     */
    public static function getValidationRules(): array
    {
        return [
            'value' => 'required|string|max:50|unique:musteri_kategori,value',
            'label' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:100',
            'aktif_mi' => 'boolean',
            'siralama' => 'integer|min:0',
        ];
    }

    /**
     * Güncelleme validation kuralları
     */
    public function getUpdateValidationRules(): array
    {
        $rules = self::getValidationRules();
        $rules['value'] = 'required|string|max:50|unique:musteri_kategori,value,' . $this->id;
        return $rules;
    }

    /**
     * Enum değerlerinden kategorileri oluştur
     */
    public static function createFromEnum(): void
    {
        foreach (MusteriKategorisi::cases() as $kategori) {
            self::updateOrCreate(
                ['value' => $kategori->value],
                [
                    'label' => $kategori->label(),
                    'description' => $kategori->description(),
                    'color' => $kategori->color(),
                    'icon' => $kategori->icon(),
                    'aktif_mi' => true,
                    'siralama' => array_search($kategori, MusteriKategorisi::cases()) * 10,
                ]
            );
        }
    }
}
