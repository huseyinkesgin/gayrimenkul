<?php

namespace App\Models\Musteri;

use App\Models\BaseModel;
use App\Models\Adres;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Firma extends BaseModel
{
    protected $table = 'firma';

    protected $fillable = [
        'unvan',
        'ticaret_unvani',
        'vergi_no',
        'vergi_dairesi',
        'mersis_no',
        'faaliyet_kodu',
        'telefon',
        'email',
        'website',
        'kuruluş_tarihi',
        'çalışan_sayisi',
        'sermaye',
        'para_birimi',
        'sektor',
        'aktif_mi',
        'notlar',
    ];

    protected $casts = [
        'kuruluş_tarihi' => 'date',
        'çalışan_sayisi' => 'integer',
        'sermaye' => 'decimal:2',
        'aktif_mi' => 'boolean',
    ];

    protected $searchableFields = [
        'unvan',
        'ticaret_unvani',
        'vergi_no',
        'email',
    ];

    protected $sortableFields = [
        'unvan',
        'ticaret_unvani',
        'kuruluş_tarihi',
        'çalışan_sayisi',
        'olusturma_tarihi',
    ];

    /**
     * Firmaya bağlı müşteriler
     */
    public function musteriler(): BelongsToMany
    {
        return $this->belongsToMany(Musteri::class, 'musteri_firma', 'firma_id', 'musteri_id')
            ->withPivot(['pozisyon', 'yetki_seviyesi', 'baslangic_tarihi', 'bitis_tarihi', 'aktif_mi'])
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
     * Firma adresleri
     */
    public function adresler(): MorphMany
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
     * Merkez adres
     */
    public function merkezAdres()
    {
        return $this->morphOne(Adres::class, 'addressable')
            ->where('adres_tipi', 'merkez')
            ->latest('olusturma_tarihi');
    }

    /**
     * Şube adresleri
     */
    public function subeAdresleri(): MorphMany
    {
        return $this->adresler()->where('adres_tipi', 'sube');
    }

    /**
     * Firma logosu
     */
    public function logo()
    {
        return $this->morphOne(\App\Models\Resim::class, 'imageable')
            ->where('kategori', \App\Enums\ResimKategorisi::LOGO->value)
            ->where('aktif_mi', true)
            ->latest('olusturma_tarihi');
    }

    /**
     * Firma dökümanları
     */
    public function dokumanlar(): MorphMany
    {
        return $this->morphMany(\App\Models\Dokuman::class, 'documentable');
    }

    /**
     * Aktif dökümanlar
     */
    public function aktifDokumanlar(): MorphMany
    {
        return $this->dokumanlar()->where('aktif_mi', true);
    }

    /**
     * Firma notları
     */
    public function notlar(): MorphMany
    {
        return $this->morphMany(\App\Models\Not::class, 'notable');
    }

    /**
     * Tam unvan (ticaret unvanı varsa onu, yoksa unvanı döndür)
     */
    public function getTamUnvanAttribute(): string
    {
        return $this->ticaret_unvani ?: $this->unvan;
    }

    /**
     * Formatlanmış vergi numarası
     */
    public function getFormattedVergiNoAttribute(): string
    {
        if (!$this->vergi_no) {
            return '';
        }

        // 10 haneli vergi numarasını formatla
        if (strlen($this->vergi_no) === 10) {
            return substr($this->vergi_no, 0, 3) . ' ' . 
                   substr($this->vergi_no, 3, 3) . ' ' . 
                   substr($this->vergi_no, 6, 4);
        }

        return $this->vergi_no;
    }

    /**
     * Formatlanmış sermaye
     */
    public function getFormattedSermayeAttribute(): string
    {
        if (!$this->sermaye) {
            return 'Belirtilmemiş';
        }

        $currency = match ($this->para_birimi) {
            'USD' => '$',
            'EUR' => '€',
            default => '₺'
        };

        return number_format($this->sermaye, 0, ',', '.') . ' ' . $currency;
    }

    /**
     * Çalışan sayısı kategorisi
     */
    public function getCalisanKategorisiAttribute(): string
    {
        if (!$this->çalışan_sayisi) {
            return 'Belirtilmemiş';
        }

        return match (true) {
            $this->çalışan_sayisi >= 250 => 'Büyük İşletme',
            $this->çalışan_sayisi >= 50 => 'Orta Ölçekli İşletme',
            $this->çalışan_sayisi >= 10 => 'Küçük İşletme',
            default => 'Mikro İşletme'
        };
    }

    /**
     * Firma yaşı
     */
    public function getFirmaYasiAttribute(): ?int
    {
        if (!$this->kuruluş_tarihi) {
            return null;
        }

        return $this->kuruluş_tarihi->diffInYears(now());
    }

    /**
     * Firma durumu
     */
    public function getFirmaDurumuAttribute(): string
    {
        if (!$this->aktif_mi) {
            return 'Pasif';
        }

        $yas = $this->firma_yasi;
        if ($yas === null) {
            return 'Aktif';
        }

        return match (true) {
            $yas >= 20 => 'Köklü Firma',
            $yas >= 10 => 'Deneyimli Firma',
            $yas >= 5 => 'Orta Vadeli Firma',
            default => 'Yeni Firma'
        };
    }

    /**
     * Website URL'si düzelt
     */
    public function getWebsiteUrlAttribute(): ?string
    {
        if (!$this->website) {
            return null;
        }

        if (!str_starts_with($this->website, 'http')) {
            return 'https://' . $this->website;
        }

        return $this->website;
    }

    /**
     * Display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->tam_unvan;
    }

    /**
     * Aktif firmalar scope
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif_mi', true);
    }

    /**
     * Sektöre göre scope
     */
    public function scopeBySector($query, string $sector)
    {
        return $query->where('sektor', $sector);
    }

    /**
     * Çalışan sayısına göre scope
     */
    public function scopeByEmployeeCount($query, int $minCount = null, int $maxCount = null)
    {
        if ($minCount !== null) {
            $query->where('çalışan_sayisi', '>=', $minCount);
        }

        if ($maxCount !== null) {
            $query->where('çalışan_sayisi', '<=', $maxCount);
        }

        return $query;
    }

    /**
     * Kuruluş tarihine göre scope
     */
    public function scopeByFoundationYear($query, int $minYear = null, int $maxYear = null)
    {
        if ($minYear !== null) {
            $query->whereYear('kuruluş_tarihi', '>=', $minYear);
        }

        if ($maxYear !== null) {
            $query->whereYear('kuruluş_tarihi', '<=', $maxYear);
        }

        return $query;
    }

    /**
     * Büyük işletmeler scope
     */
    public function scopeBuyukIsletme($query)
    {
        return $query->where('çalışan_sayisi', '>=', 250);
    }

    /**
     * KOBİ scope
     */
    public function scopeKobi($query)
    {
        return $query->where('çalışan_sayisi', '<', 250);
    }

    /**
     * Validation kuralları
     */
    public static function getValidationRules(): array
    {
        return [
            'unvan' => 'required|string|max:255',
            'ticaret_unvani' => 'nullable|string|max:255',
            'vergi_no' => 'required|string|size:10|unique:firma,vergi_no',
            'vergi_dairesi' => 'required|string|max:100',
            'mersis_no' => 'nullable|string|size:16',
            'faaliyet_kodu' => 'nullable|string|max:10',
            'telefon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'kuruluş_tarihi' => 'nullable|date|before_or_equal:today',
            'çalışan_sayisi' => 'nullable|integer|min:1|max:100000',
            'sermaye' => 'nullable|numeric|min:0',
            'para_birimi' => 'nullable|string|size:3|in:TRY,USD,EUR',
            'sektor' => 'nullable|string|max:100',
            'aktif_mi' => 'boolean',
            'notlar' => 'nullable|string|max:5000',
        ];
    }

    /**
     * Güncelleme validation kuralları
     */
    public function getUpdateValidationRules(): array
    {
        $rules = self::getValidationRules();
        $rules['vergi_no'] = 'required|string|size:10|unique:firma,vergi_no,' . $this->id;
        return $rules;
    }
}