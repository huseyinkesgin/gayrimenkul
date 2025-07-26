<?php

namespace App\Models;

use App\Enums\NotKategorisi;

class Not extends BaseModel
{
    protected $table = 'notlar';

    protected $fillable = [
        'notable_id',
        'notable_type',
        'personel_id',
        'baslik',
        'icerik',
        'kategori',
        'oncelik',
        'gizli_mi',
        'aktif_mi',
    ];

    protected $casts = [
        'kategori' => NotKategorisi::class,
        'gizli_mi' => 'boolean',
        'aktif_mi' => 'boolean',
        'oncelik' => 'integer',
    ];

    /**
     * Polymorphic ilişki - Bu not hangi modele ait
     */
    public function notable()
    {
        return $this->morphTo();
    }

    /**
     * Notu yazan personel
     */
    public function personel()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'personel_id');
    }

    /**
     * Aktif notlar scope
     */
    public function scopeActive($query)
    {
        return $query->where('aktif_mi', true);
    }

    /**
     * Gizli olmayan notlar scope
     */
    public function scopePublic($query)
    {
        return $query->where('gizli_mi', false);
    }

    /**
     * Öncelik seviyesine göre scope
     */
    public function scopeByPriority($query, int $minPriority = 1)
    {
        return $query->where('oncelik', '>=', $minPriority);
    }

    /**
     * Yüksek öncelikli notlar scope
     */
    public function scopeHighPriority($query)
    {
        return $query->where('oncelik', '>=', 8);
    }

    /**
     * Kategoriye göre scope
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('kategori', $category);
    }

    /**
     * Personele göre scope
     */
    public function scopeByPersonel($query, $personelId)
    {
        return $query->where('personel_id', $personelId);
    }

    /**
     * Öncelik rengini döndür
     */
    public function getPriorityColorAttribute(): string
    {
        return match (true) {
            $this->oncelik >= 9 => 'red',
            $this->oncelik >= 7 => 'orange',
            $this->oncelik >= 5 => 'yellow',
            $this->oncelik >= 3 => 'blue',
            default => 'gray'
        };
    }

    /**
     * Öncelik etiketini döndür
     */
    public function getPriorityLabelAttribute(): string
    {
        return match (true) {
            $this->oncelik >= 9 => 'Çok Yüksek',
            $this->oncelik >= 7 => 'Yüksek',
            $this->oncelik >= 5 => 'Orta',
            $this->oncelik >= 3 => 'Düşük',
            default => 'Çok Düşük'
        };
    }

    /**
     * Kısa içerik döndür
     */
    public function getShortContentAttribute(): string
    {
        return \Str::limit($this->icerik, 100);
    }
}