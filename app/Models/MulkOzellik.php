<?php

namespace App\Models;

use App\Models\Mulk\BaseMulk;

class MulkOzellik extends BaseModel
{
    protected $table = 'mulk_ozellikleri';

    protected $fillable = [
        'mulk_id',
        'mulk_type',
        'ozellik_adi',
        'ozellik_degeri',
        'ozellik_tipi',
        'birim',
        'aktif_mi',
        'siralama',
    ];

    protected $casts = [
        'ozellik_degeri' => 'json',
        'aktif_mi' => 'boolean',
        'siralama' => 'integer',
    ];

    /**
     * Mülk ilişkisi
     */
    public function mulk()
    {
        return $this->belongsTo(BaseMulk::class, 'mulk_id');
    }

    /**
     * Özellik değerini formatlanmış olarak döndür
     */
    public function getFormattedValueAttribute(): string
    {
        $value = $this->ozellik_degeri;
        
        // Array ise ve tek eleman varsa direkt değeri al
        if (is_array($value)) {
            if (count($value) === 1) {
                $value = $value[0];
            } else {
                $value = implode(', ', $value);
            }
        }

        // Boolean değerler için
        if ($this->ozellik_tipi === 'boolean') {
            return $value ? 'Evet' : 'Hayır';
        }

        // Sayısal değerler için birim ekle
        if ($this->ozellik_tipi === 'sayi' && $this->birim) {
            return $value . ' ' . $this->birim;
        }

        return (string) $value;
    }

    /**
     * Özellik tipine göre input tipi döndür
     */
    public function getInputTypeAttribute(): string
    {
        return match ($this->ozellik_tipi) {
            'sayi' => 'number',
            'boolean' => 'checkbox',
            'liste' => 'select',
            default => 'text'
        };
    }

    /**
     * Özellik adını formatlanmış olarak döndür
     */
    public function getFormattedNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->ozellik_adi));
    }

    /**
     * Aktif özellikler scope
     */
    public function scopeActive($query)
    {
        return $query->where('aktif_mi', true);
    }

    /**
     * Mülk tipine göre scope
     */
    public function scopeByMulkType($query, string $type)
    {
        return $query->where('mulk_type', $type);
    }

    /**
     * Özellik adına göre scope
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('ozellik_adi', $name);
    }

    /**
     * Özellik tipine göre scope
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('ozellik_tipi', $type);
    }

    /**
     * Validation kuralları
     */
    public static function getValidationRules(): array
    {
        return [
            'mulk_id' => 'required|exists:mulkler,id',
            'mulk_type' => 'required|string|max:50',
            'ozellik_adi' => 'required|string|max:100',
            'ozellik_degeri' => 'required',
            'ozellik_tipi' => 'required|in:sayi,metin,boolean,liste',
            'birim' => 'nullable|string|max:20',
            'aktif_mi' => 'boolean',
            'siralama' => 'integer|min:0',
        ];
    }

    /**
     * Özellik değerini validate et
     */
    public function validateValue($value): bool
    {
        return match ($this->ozellik_tipi) {
            'sayi' => is_numeric($value),
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false']),
            'liste' => is_array($value) || is_string($value),
            'metin' => is_string($value) || is_numeric($value),
            default => true
        };
    }

    /**
     * Özellik değerini normalize et
     */
    public function normalizeValue($value)
    {
        return match ($this->ozellik_tipi) {
            'sayi' => (float) $value,
            'boolean' => (bool) $value,
            'liste' => is_array($value) ? $value : [$value],
            'metin' => (string) $value,
            default => $value
        };
    }
}