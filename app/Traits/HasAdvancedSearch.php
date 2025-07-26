<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasAdvancedSearch
{
    /**
     * Gelişmiş arama scope'u
     */
    public function scopeAdvancedSearch(Builder $query, array $filters = [])
    {
        foreach ($filters as $field => $value) {
            if (empty($value)) {
                continue;
            }

            $this->applyFilter($query, $field, $value);
        }

        return $query;
    }

    /**
     * Metin tabanlı arama
     */
    public function scopeTextSearch(Builder $query, string $term, array $fields = [])
    {
        if (empty($term)) {
            return $query;
        }

        $searchFields = !empty($fields) ? $fields : $this->getSearchableFields();

        return $query->where(function ($q) use ($term, $searchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'like', "%{$term}%");
            }
        });
    }

    /**
     * Tarih aralığı filtresi
     */
    public function scopeDateRange(Builder $query, string $field, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate($field, '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate($field, '<=', $endDate);
        }

        return $query;
    }

    /**
     * Sayısal aralık filtresi
     */
    public function scopeNumericRange(Builder $query, string $field, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where($field, '>=', $min);
        }

        if ($max !== null) {
            $query->where($field, '<=', $max);
        }

        return $query;
    }

    /**
     * İlişkili model filtresi
     */
    public function scopeWhereHasRelation(Builder $query, string $relation, $callback = null)
    {
        return $query->whereHas($relation, $callback);
    }

    /**
     * Lokasyon bazlı arama (adres ilişkisi üzerinden)
     */
    public function scopeByLocation(Builder $query, array $locationFilters = [])
    {
        return $query->whereHas('adresler', function ($q) use ($locationFilters) {
            if (!empty($locationFilters['sehir_id'])) {
                $q->where('sehir_id', $locationFilters['sehir_id']);
            }
            
            if (!empty($locationFilters['ilce_id'])) {
                $q->where('ilce_id', $locationFilters['ilce_id']);
            }
            
            if (!empty($locationFilters['semt_id'])) {
                $q->where('semt_id', $locationFilters['semt_id']);
            }
            
            if (!empty($locationFilters['mahalle_id'])) {
                $q->where('mahalle_id', $locationFilters['mahalle_id']);
            }
            
            if (!empty($locationFilters['adres_detay'])) {
                $q->where('adres_detay', 'like', '%' . $locationFilters['adres_detay'] . '%');
            }
        });
    }

    /**
     * Çoklu değer filtresi
     */
    public function scopeWhereIn(Builder $query, string $field, array $values)
    {
        if (empty($values)) {
            return $query;
        }

        return $query->whereIn($field, $values);
    }

    /**
     * Dinamik sıralama
     */
    public function scopeDynamicSort(Builder $query, string $sortField = null, string $sortDirection = 'asc')
    {
        if (!$sortField) {
            return $query->orderBy($this->getDefaultSortField(), $this->getDefaultSortDirection());
        }

        $allowedFields = $this->getSortableFields();
        
        if (!in_array($sortField, $allowedFields)) {
            $sortField = $this->getDefaultSortField();
        }

        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) 
            ? strtolower($sortDirection) 
            : 'asc';

        return $query->orderBy($sortField, $sortDirection);
    }

    /**
     * Filtre uygulama
     */
    protected function applyFilter(Builder $query, string $field, $value)
    {
        // Özel filtre metodları varsa kullan
        $methodName = 'filter' . Str::studly($field);
        if (method_exists($this, $methodName)) {
            return $this->$methodName($query, $value);
        }

        // Varsayılan filtre mantığı
        if (is_array($value)) {
            return $query->whereIn($field, $value);
        }

        if (is_string($value) && Str::contains($value, '%')) {
            return $query->where($field, 'like', $value);
        }

        return $query->where($field, $value);
    }

    /**
     * Aranabilir alanları döndür
     */
    protected function getSearchableFields(): array
    {
        return property_exists($this, 'searchableFields') 
            ? $this->searchableFields 
            : ['ad', 'baslik', 'aciklama'];
    }

    /**
     * Sıralanabilir alanları döndür
     */
    protected function getSortableFields(): array
    {
        return property_exists($this, 'sortableFields') 
            ? $this->sortableFields 
            : ['olusturma_tarihi', 'guncelleme_tarihi'];
    }

    /**
     * Varsayılan sıralama alanı
     */
    protected function getDefaultSortField(): string
    {
        return property_exists($this, 'defaultSortField') 
            ? $this->defaultSortField 
            : 'olusturma_tarihi';
    }

    /**
     * Varsayılan sıralama yönü
     */
    protected function getDefaultSortDirection(): string
    {
        return property_exists($this, 'defaultSortDirection') 
            ? $this->defaultSortDirection 
            : 'desc';
    }
}