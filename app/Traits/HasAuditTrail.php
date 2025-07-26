<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasAuditTrail
{
    /**
     * Boot the trait
     */
    protected static function bootHasAuditTrail()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->olusturan_id = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->guncelleyen_id = Auth::id();
            }
        });
    }

    /**
     * Oluşturan kullanıcı ilişkisi
     */
    public function olusturan()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'olusturan_id');
    }

    /**
     * Güncelleyen kullanıcı ilişkisi
     */
    public function guncelleyen()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'guncelleyen_id');
    }

    /**
     * Audit bilgilerini döndür
     */
    public function getAuditInfoAttribute()
    {
        return [
            'olusturan' => $this->olusturan?->name,
            'olusturma_tarihi' => $this->olusturma_tarihi?->format('d.m.Y H:i'),
            'guncelleyen' => $this->guncelleyen?->name,
            'guncelleme_tarihi' => $this->guncelleme_tarihi?->format('d.m.Y H:i'),
        ];
    }
}