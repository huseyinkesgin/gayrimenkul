<?php

namespace App\Models\Musteri;

use App\Models\Adres;
use App\Models\BaseModel;
use App\Models\Kisi\Kisi;
use App\Models\MusteriMulkIliskisi;
use App\Models\MusteriHizmet;
use App\Models\Mulk\BaseMulk;
use App\Enums\MusteriTipi;
use App\Enums\MusteriKategorisi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Musteri extends BaseModel
{
    protected $table = 'musteri';

    protected $fillable = [
        'kisi_id',
        'tip',
        'musteri_no',
        'kayit_tarihi',
        'kaynak',
        'referans_musteri_id',
        'potansiyel_deger',
        'para_birimi',
        'notlar',
        'aktif_mi',
        'siralama',
    ];

    protected $casts = [
        'tip' => MusteriTipi::class,
        'kayit_tarihi' => 'datetime',
        'potansiyel_deger' => 'decimal:2',
        'aktif_mi' => 'boolean',
        'siralama' => 'integer',
    ];

    protected $searchableFields = [
        'musteri_no',
        'kaynak',
        'notlar',
    ];

    protected $sortableFields = [
        'musteri_no',
        'kayit_tarihi',
        'potansiyel_deger',
        'olusturma_tarihi',
        'guncelleme_tarihi',
    ];

    protected $defaultSortField = 'kayit_tarihi';
    protected $defaultSortDirection = 'desc';

    /**
     * Müşterinin kişi bilgileri
     */
    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class);
    }

    /**
     * Referans müşteri
     */
    public function referansMusteri(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referans_musteri_id');
    }

    /**
     * Bu müşteriyi referans alan müşteriler
     */
    public function referansAlanMusteriler(): HasMany
    {
        return $this->hasMany(self::class, 'referans_musteri_id');
    }

    /**
     * Müşterinin kategorileri (many-to-many)
     */
    public function kategoriler(): BelongsToMany
    {
        return $this->belongsToMany(MusteriKategori::class, 'musteri_musteri_kategori', 'musteri_id', 'musteri_kategori_id')
            ->withPivot(['baslangic_tarihi', 'bitis_tarihi', 'aktif_mi', 'notlar'])
            ->withTimestamps();
    }

    /**
     * Aktif kategoriler
     */
    public function aktifKategoriler(): BelongsToMany
    {
        return $this->kategoriler()->wherePivot('aktif_mi', true);
    }

    /**
     * Müşterinin firmalarla ilişkisi
     */
    public function firmalar(): BelongsToMany
    {
        return $this->belongsToMany(Firma::class, 'musteri_firma', 'musteri_id', 'firma_id')
            ->withPivot(['pozisyon', 'yetki_seviyesi', 'baslangic_tarihi', 'bitis_tarihi', 'aktif_mi'])
            ->withTimestamps();
    }

    /**
     * Aktif firma ilişkileri
     */
    public function aktifFirmalar(): BelongsToMany
    {
        return $this->firmalar()->wherePivot('aktif_mi', true);
    }

    /**
     * Ana firma (en yüksek yetki seviyesindeki)
     */
    public function anaFirma()
    {
        return $this->firmalar()
            ->wherePivot('aktif_mi', true)
            ->orderByPivot('yetki_seviyesi', 'desc')
            ->first();
    }

    /**
     * Müşterinin mülk ilişkileri
     */
    public function mulkIliskileri(): HasMany
    {
        return $this->hasMany(MusteriMulkIliskisi::class);
    }

    /**
     * Aktif mülk ilişkileri
     */
    public function aktifMulkIliskileri(): HasMany
    {
        return $this->mulkIliskileri()->where('durum', 'aktif');
    }

    /**
     * İlgilendiği mülkler
     */
    public function ilgilenilenMulkler(): BelongsToMany
    {
        return $this->belongsToMany(BaseMulk::class, 'musteri_mulk_iliskileri', 'musteri_id', 'mulk_id')
            ->withPivot(['mulk_type', 'iliski_tipi', 'durum', 'ilgi_seviyesi', 'notlar', 'baslangic_tarihi'])
            ->withTimestamps();
    }

    /**
     * Yüksek ilgi seviyesindeki mülkler
     */
    public function yuksekIlgiMulkleri(): BelongsToMany
    {
        return $this->ilgilenilenMulkler()->wherePivot('ilgi_seviyesi', '>=', 7);
    }

    /**
     * Müşteri hizmetleri
     */
    public function hizmetler(): HasMany
    {
        return $this->hasMany(MusteriHizmet::class);
    }

    /**
     * Son hizmetler
     */
    public function sonHizmetler(): HasMany
    {
        return $this->hizmetler()->latest('hizmet_tarihi')->limit(10);
    }

    /**
     * Müşterinin adresleri (morph ilişkisi)
     */
    public function adresler(): MorphMany
    {
        return $this->morphMany(Adres::class, 'addressable');
    }

    /**
     * Müşterinin varsayılan adresi
     */
    public function varsayilanAdres(): MorphOne
    {
        return $this->morphOne(Adres::class, 'addressable')
            ->where('varsayilan_mi', true)
            ->latest('olusturma_tarihi');
    }

    /**
     * Ev adresi
     */
    public function evAdresi(): MorphOne
    {
        return $this->morphOne(Adres::class, 'addressable')
            ->where('adres_tipi', 'ev')
            ->latest('olusturma_tarihi');
    }

    /**
     * İş adresi
     */
    public function isAdresi(): MorphOne
    {
        return $this->morphOne(Adres::class, 'addressable')
            ->where('adres_tipi', 'is')
            ->latest('olusturma_tarihi');
    }

    /**
     * Müşteri profil resmi
     */
    public function profilResmi(): MorphOne
    {
        return $this->morphOne(\App\Models\Resim::class, 'imageable')
            ->where('kategori', \App\Enums\ResimKategorisi::AVATAR->value)
            ->where('aktif_mi', true)
            ->latest('olusturma_tarihi');
    }

    /**
     * Müşteri dökümanları
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
     * Müşteri notları
     */
    public function notlar(): MorphMany
    {
        return $this->morphMany(\App\Models\Not::class, 'notable');
    }

    /**
     * Hatırlatmalar
     */
    public function hatirlatmalar(): MorphMany
    {
        return $this->morphMany(\App\Models\Hatirlatma::class, 'hatirlatilacak');
    }

    /**
     * Aktif hatırlatmalar
     */
    public function aktifHatirlatmalar(): MorphMany
    {
        return $this->hatirlatmalar()->where('durum', 'beklemede');
    }

    /**
     * Tam ad accessor (kişi bilgilerinden)
     */
    public function getFullNameAttribute(): string
    {
        return $this->kisi ? $this->kisi->full_name : '';
    }

    /**
     * Display name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->tip === MusteriTipi::KURUMSAL) {
            $anaFirma = $this->anaFirma();
            if ($anaFirma) {
                return $anaFirma->tam_unvan . ' (' . $this->full_name . ')';
            }
        }
        
        return $this->full_name ?: $this->musteri_no;
    }

    /**
     * Müşteri tipi etiketi
     */
    public function getTipLabelAttribute(): string
    {
        return $this->tip->label();
    }

    /**
     * Formatlanmış müşteri numarası
     */
    public function getFormattedMusteriNoAttribute(): string
    {
        if (!$this->musteri_no) {
            return '';
        }

        // MST-2024-001 formatında
        return strtoupper($this->musteri_no);
    }

    /**
     * Formatlanmış potansiyel değer
     */
    public function getFormattedPotansiyelDegerAttribute(): string
    {
        if (!$this->potansiyel_deger) {
            return 'Belirtilmemiş';
        }

        $currency = match ($this->para_birimi) {
            'USD' => '$',
            'EUR' => '€',
            default => '₺'
        };

        return number_format($this->potansiyel_deger, 0, ',', '.') . ' ' . $currency;
    }

    /**
     * Müşteri yaşı (kayıt tarihinden itibaren)
     */
    public function getMusteriYasiAttribute(): ?int
    {
        if (!$this->kayit_tarihi) {
            return null;
        }

        return $this->kayit_tarihi->diffInDays(now());
    }

    /**
     * Müşteri segmenti
     */
    public function getMusteriSegmentiAttribute(): string
    {
        $potansiyel = $this->potansiyel_deger ?? 0;
        $hizmetSayisi = $this->hizmetler()->count();
        $mulkIlgiSayisi = $this->mulkIliskileri()->count();

        $puan = 0;
        
        // Potansiyel değer puanı
        if ($potansiyel >= 5000000) $puan += 40;
        elseif ($potansiyel >= 2000000) $puan += 30;
        elseif ($potansiyel >= 1000000) $puan += 20;
        elseif ($potansiyel >= 500000) $puan += 10;

        // Hizmet geçmişi puanı
        if ($hizmetSayisi >= 20) $puan += 30;
        elseif ($hizmetSayisi >= 10) $puan += 20;
        elseif ($hizmetSayisi >= 5) $puan += 10;

        // Mülk ilgisi puanı
        if ($mulkIlgiSayisi >= 10) $puan += 30;
        elseif ($mulkIlgiSayisi >= 5) $puan += 20;
        elseif ($mulkIlgiSayisi >= 2) $puan += 10;

        return match (true) {
            $puan >= 80 => 'VIP',
            $puan >= 60 => 'Premium',
            $puan >= 40 => 'Değerli',
            $puan >= 20 => 'Standart',
            default => 'Yeni'
        };
    }

    /**
     * Müşteri durumu
     */
    public function getMusteriDurumuAttribute(): string
    {
        if (!$this->aktif_mi) {
            return 'Pasif';
        }

        $sonHizmet = $this->hizmetler()->latest('hizmet_tarihi')->first();
        if (!$sonHizmet) {
            return 'Yeni';
        }

        $gunFarki = $sonHizmet->hizmet_tarihi->diffInDays(now());
        
        return match (true) {
            $gunFarki <= 7 => 'Aktif',
            $gunFarki <= 30 => 'Orta Aktif',
            $gunFarki <= 90 => 'Az Aktif',
            default => 'Durgun'
        };
    }

    /**
     * Kategori etiketleri
     */
    public function getKategoriEtiketleriAttribute(): array
    {
        return $this->aktifKategoriler->map(function ($kategori) {
            return [
                'value' => $kategori->value,
                'label' => $kategori->label(),
                'color' => $kategori->color(),
            ];
        })->toArray();
    }

    /**
     * Belirli bir kategoriye sahip mi?
     */
    public function hasKategori(MusteriKategorisi $kategori): bool
    {
        return $this->aktifKategoriler()
            ->where('value', $kategori->value)
            ->exists();
    }

    /**
     * Kategori ekle
     */
    public function addKategori(MusteriKategorisi $kategori, ?string $notlar = null): void
    {
        $this->kategoriler()->syncWithoutDetaching([
            $kategori->value => [
                'baslangic_tarihi' => now(),
                'aktif_mi' => true,
                'notlar' => $notlar,
            ]
        ]);
    }

    /**
     * Kategori kaldır
     */
    public function removeKategori(MusteriKategorisi $kategori): void
    {
        $this->kategoriler()->updateExistingPivot($kategori->value, [
            'bitis_tarihi' => now(),
            'aktif_mi' => false,
        ]);
    }

    /**
     * Bireysel müşteriler scope
     */
    public function scopeBireysel($query)
    {
        return $query->where('tip', MusteriTipi::BIREYSEL);
    }

    /**
     * Kurumsal müşteriler scope
     */
    public function scopeKurumsal($query)
    {
        return $query->where('tip', MusteriTipi::KURUMSAL);
    }

    /**
     * Aktif müşteriler scope
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif_mi', true);
    }

    /**
     * Kategoriye göre scope
     */
    public function scopeByKategori($query, MusteriKategorisi $kategori)
    {
        return $query->whereHas('aktifKategoriler', function ($q) use ($kategori) {
            $q->where('value', $kategori->value);
        });
    }

    /**
     * Potansiyel değer aralığına göre scope
     */
    public function scopeByPotansiyelDeger($query, ?float $minDeger = null, ?float $maxDeger = null)
    {
        if ($minDeger !== null) {
            $query->where('potansiyel_deger', '>=', $minDeger);
        }

        if ($maxDeger !== null) {
            $query->where('potansiyel_deger', '<=', $maxDeger);
        }

        return $query;
    }

    /**
     * Kaynak göre scope
     */
    public function scopeByKaynak($query, string $kaynak)
    {
        return $query->where('kaynak', $kaynak);
    }

    /**
     * Segmente göre scope
     */
    public function scopeBySegment($query, string $segment)
    {
        // Bu scope'u kullanmak için önce müşteri segmentlerini hesaplamak gerekir
        // Şimdilik basit bir implementasyon
        return match ($segment) {
            'VIP' => $query->where('potansiyel_deger', '>=', 5000000),
            'Premium' => $query->whereBetween('potansiyel_deger', [2000000, 4999999]),
            'Değerli' => $query->whereBetween('potansiyel_deger', [1000000, 1999999]),
            'Standart' => $query->whereBetween('potansiyel_deger', [500000, 999999]),
            'Yeni' => $query->where('potansiyel_deger', '<', 500000)->orWhereNull('potansiyel_deger'),
            default => $query
        };
    }

    /**
     * Son aktivite tarihine göre scope
     */
    public function scopeBySonAktivite($query, int $gunSayisi = 30)
    {
        return $query->whereHas('hizmetler', function ($q) use ($gunSayisi) {
            $q->where('hizmet_tarihi', '>=', now()->subDays($gunSayisi));
        });
    }

    /**
     * Validation kuralları
     */
    public static function getValidationRules(): array
    {
        return [
            'kisi_id' => 'required|exists:kisi,id',
            'tip' => 'required|in:bireysel,kurumsal',
            'musteri_no' => 'nullable|string|max:50|unique:musteri,musteri_no',
            'kayit_tarihi' => 'nullable|date',
            'kaynak' => 'nullable|string|max:100',
            'referans_musteri_id' => 'nullable|exists:musteri,id',
            'potansiyel_deger' => 'nullable|numeric|min:0',
            'para_birimi' => 'nullable|string|size:3|in:TRY,USD,EUR',
            'notlar' => 'nullable|string|max:5000',
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
        $rules['musteri_no'] = 'nullable|string|max:50|unique:musteri,musteri_no,' . $this->id;
        return $rules;
    }
}
