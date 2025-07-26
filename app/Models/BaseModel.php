<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Traits\HasAuditTrail;
use App\Traits\HasAdvancedSearch;
use App\Traits\HasPolymorphicRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;

class BaseModel extends Model
{
    use SoftDeletes,
        HasUuids,
        HasAuditTrail,
        HasPolymorphicRelations,
        HasAdvancedSearch;

    const CREATED_AT = 'olusturma_tarihi';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'guncelleme_tarihi';

    /**
     * The name of the "deleted at" column.
     *
     * @var string
     */
    const DELETED_AT = 'silinme_tarihi';




    // Geri alma fonksiyonu
    public static function geriAl($id)
    {
        $model = self::withTrashed()->find($id);
        if ($model) {
            $model->restore();
            return true;
        }
        return false;
    }



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif_mi', true);
    }

    // Sadece silinenleri getirir
    public function scopeSadeceSilinen($query)
    {
        return $query->onlyTrashed();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('siralama');
    }

    public function scopeApplySort($query, $sortField, $sortDirection)
    {
        if ($sortField && $sortDirection) {
            return $query->orderBy($sortField, $sortDirection);
        }
        return $query;
    }

    /**
     * Toplu aktif/pasif yapma
     */
    public function scopeBulkToggleStatus($query, array $ids, bool $status = true)
    {
        return $query->whereIn('id', $ids)->update(['aktif_mi' => $status]);
    }

    /**
     * Son güncellenen kayıtlar
     */
    public function scopeRecentlyUpdated($query, int $days = 7)
    {
        return $query->where('guncelleme_tarihi', '>=', now()->subDays($days));
    }

    /**
     * Belirli tarih aralığındaki kayıtlar
     */
    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('olusturma_tarihi', [$startDate, $endDate]);
    }

    /**
     * Model adını döndür
     */
    public function getModelNameAttribute(): string
    {
        return class_basename($this);
    }

    /**
     * Benzersiz slug oluştur
     */
    public function generateUniqueSlug(string $title, string $field = 'slug'): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where($field, $slug)->where('id', '!=', $this->id ?? null)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Model'in JSON temsilini döndür
     */
    public function toSelectArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->getDisplayName(),
            'value' => $this->id
        ];
    }

    /**
     * Görüntüleme adını döndür (override edilebilir)
     */
    public function getDisplayName(): string
    {
        if (isset($this->attributes['ad'])) {
            return $this->attributes['ad'];
        }

        if (isset($this->attributes['baslik'])) {
            return $this->attributes['baslik'];
        }

        if (isset($this->attributes['unvan'])) {
            return $this->attributes['unvan'];
        }

        return $this->id;
    }

    /**
     * Cache key oluştur
     */
    public function getCacheKey(string $suffix = ''): string
    {
        $key = strtolower(class_basename($this)) . '_' . $this->id;
        return $suffix ? $key . '_' . $suffix : $key;
    }

    /**
     * Model'in URL'sini döndür
     */
    public function getUrlAttribute(): string
    {
        $routeName = strtolower(class_basename($this));
        return route($routeName . '.show', $this->id);
    }

    /**
     * Aktif kayıt sayısını döndür
     */
    public static function activeCount(): int
    {
        return static::where('aktif_mi', true)->count();
    }

    /**
     * Pasif kayıt sayısını döndür
     */
    public static function inactiveCount(): int
    {
        return static::where('aktif_mi', false)->count();
    }

    /**
     * Toplam kayıt sayısını döndür (silinmişler hariç)
     */
    public static function totalCount(): int
    {
        return static::count();
    }
}
