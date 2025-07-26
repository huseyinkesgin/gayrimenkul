<?php

namespace App\Models;

use App\Models\Mulk\BaseMulk;
use App\Models\Musteri\Musteri;
use App\Models\User;
use App\Enums\IliskiTipi;
use App\Enums\IliskiDurumu;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MusteriMulkIliskisi extends BaseModel
{
    protected $table = 'musteri_mulk_iliskileri';

    protected $fillable = [
        'musteri_id',
        'mulk_id',
        'mulk_type',
        'iliski_tipi',
        'baslangic_tarihi',
        'bitis_tarihi',
        'durum',
        'ilgi_seviyesi',
        'teklif_miktari',
        'teklif_para_birimi',
        'son_teklif_tarihi',
        'beklenen_karar_tarihi',
        'karar_verme_sebebi',
        'rekabet_durumu',
        'avantajlar',
        'dezavantajlar',
        'ozel_istekler',
        'finansman_durumu',
        'aciliyet_seviyesi',
        'referans_kaynak',
        'takip_sikligi',
        'son_aktivite_tarihi',
        'sonraki_adim',
        'sorumlu_personel_id',
        'notlar',
        'etiketler',
        'ozel_alanlar',
        'aktif_mi',
        'siralama',
    ];

    protected $casts = [
        'iliski_tipi' => IliskiTipi::class,
        'durum' => IliskiDurumu::class,
        'baslangic_tarihi' => 'datetime',
        'bitis_tarihi' => 'datetime',
        'son_teklif_tarihi' => 'datetime',
        'beklenen_karar_tarihi' => 'datetime',
        'son_aktivite_tarihi' => 'datetime',
        'ilgi_seviyesi' => 'integer',
        'teklif_miktari' => 'decimal:2',
        'aciliyet_seviyesi' => 'integer',
        'takip_sikligi' => 'integer',
        'avantajlar' => 'json',
        'dezavantajlar' => 'json',
        'ozel_istekler' => 'json',
        'etiketler' => 'json',
        'ozel_alanlar' => 'json',
        'aktif_mi' => 'boolean',
        'siralama' => 'integer',
    ];

    protected $searchableFields = [
        'notlar',
        'karar_verme_sebebi',
        'rekabet_durumu',
        'referans_kaynak',
        'sonraki_adim',
    ];

    protected $sortableFields = [
        'baslangic_tarihi',
        'bitis_tarihi',
        'ilgi_seviyesi',
        'teklif_miktari',
        'aciliyet_seviyesi',
        'son_aktivite_tarihi',
        'olusturma_tarihi',
    ];

    protected $defaultSortField = 'son_aktivite_tarihi';
    protected $defaultSortDirection = 'desc';

    /**
     * Müşteri ilişkisi
     */
    public function musteri(): BelongsTo
    {
        return $this->belongsTo(Musteri::class);
    }

    /**
     * Mülk ilişkisi
     */
    public function mulk(): BelongsTo
    {
        return $this->belongsTo(BaseMulk::class, 'mulk_id');
    }

    /**
     * Sorumlu personel ilişkisi
     */
    public function sorumluPersonel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sorumlu_personel_id');
    }

    /**
     * İlişki hizmetleri
     */
    public function hizmetler(): HasMany
    {
        return $this->hasMany(MusteriHizmet::class, 'mulk_id', 'mulk_id')
            ->where('musteri_id', $this->musteri_id)
            ->where('mulk_type', $this->mulk_type);
    }

    /**
     * Son hizmetler
     */
    public function sonHizmetler(): HasMany
    {
        return $this->hizmetler()->latest('hizmet_tarihi')->limit(5);
    }

    /**
     * İlişki notları
     */
    public function iliskiNotlari(): MorphMany
    {
        return $this->morphMany(\App\Models\Not::class, 'notable');
    }

    /**
     * İlişki dökümanları
     */
    public function dokumanlar(): MorphMany
    {
        return $this->morphMany(\App\Models\Dokuman::class, 'documentable');
    }

    /**
     * İlişki resimleri
     */
    public function resimler(): MorphMany
    {
        return $this->morphMany(\App\Models\Resim::class, 'imageable')
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * İlişki hatırlatmaları
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
     * İlişki tipi etiketi
     */
    public function getIliskiTipiLabelAttribute(): string
    {
        return $this->iliski_tipi?->label() ?? 'Bilinmiyor';
    }

    /**
     * İlişki tipi rengi
     */
    public function getIliskiTipiColorAttribute(): string
    {
        return $this->iliski_tipi?->color() ?? 'gray';
    }

    /**
     * İlişki tipi ikonu
     */
    public function getIliskiTipiIconAttribute(): string
    {
        return $this->iliski_tipi?->icon() ?? 'heroicon-o-question-mark-circle';
    }

    /**
     * Durum etiketi
     */
    public function getDurumLabelAttribute(): string
    {
        return $this->durum?->label() ?? 'Bilinmiyor';
    }

    /**
     * Durum rengi
     */
    public function getDurumColorAttribute(): string
    {
        return $this->durum?->color() ?? 'gray';
    }

    /**
     * Durum ikonu
     */
    public function getDurumIconAttribute(): string
    {
        return $this->durum?->icon() ?? 'heroicon-o-question-mark-circle';
    }

    /**
     * İlgi seviyesi rengi
     */
    public function getIlgiSeviyesiColorAttribute(): string
    {
        return match (true) {
            $this->ilgi_seviyesi >= 9 => 'emerald',
            $this->ilgi_seviyesi >= 7 => 'green',
            $this->ilgi_seviyesi >= 5 => 'yellow',
            $this->ilgi_seviyesi >= 3 => 'orange',
            $this->ilgi_seviyesi >= 1 => 'red',
            default => 'gray'
        };
    }

    /**
     * İlgi seviyesi etiketi
     */
    public function getIlgiSeviyesiLabelAttribute(): string
    {
        return match (true) {
            $this->ilgi_seviyesi >= 9 => 'Çok Yüksek',
            $this->ilgi_seviyesi >= 7 => 'Yüksek',
            $this->ilgi_seviyesi >= 5 => 'Orta',
            $this->ilgi_seviyesi >= 3 => 'Düşük',
            $this->ilgi_seviyesi >= 1 => 'Çok Düşük',
            default => 'Belirtilmemiş'
        };
    }

    /**
     * Aciliyet seviyesi rengi
     */
    public function getAciliyetSeviyesiColorAttribute(): string
    {
        return match (true) {
            $this->aciliyet_seviyesi >= 9 => 'red',
            $this->aciliyet_seviyesi >= 7 => 'orange',
            $this->aciliyet_seviyesi >= 5 => 'yellow',
            $this->aciliyet_seviyesi >= 3 => 'blue',
            $this->aciliyet_seviyesi >= 1 => 'green',
            default => 'gray'
        };
    }

    /**
     * Aciliyet seviyesi etiketi
     */
    public function getAciliyetSeviyesiLabelAttribute(): string
    {
        return match (true) {
            $this->aciliyet_seviyesi >= 9 => 'Çok Acil',
            $this->aciliyet_seviyesi >= 7 => 'Acil',
            $this->aciliyet_seviyesi >= 5 => 'Orta',
            $this->aciliyet_seviyesi >= 3 => 'Düşük',
            $this->aciliyet_seviyesi >= 1 => 'Çok Düşük',
            default => 'Belirtilmemiş'
        };
    }

    /**
     * Formatlanmış teklif miktarı
     */
    public function getFormattedTeklifMiktariAttribute(): string
    {
        if (!$this->teklif_miktari) {
            return 'Teklif Verilmemiş';
        }

        $currency = match ($this->teklif_para_birimi) {
            'USD' => '$',
            'EUR' => '€',
            default => '₺'
        };

        return number_format($this->teklif_miktari, 0, ',', '.') . ' ' . $currency;
    }

    /**
     * İlişki süresi (gün)
     */
    public function getIliskiSuresiAttribute(): int
    {
        $baslangic = $this->baslangic_tarihi ?? $this->olusturma_tarihi;
        $bitis = $this->bitis_tarihi ?? now();
        
        return $baslangic->diffInDays($bitis);
    }

    /**
     * Formatlanmış ilişki süresi
     */
    public function getFormattedIliskiSuresiAttribute(): string
    {
        $gun = $this->iliski_suresi;
        
        if ($gun < 7) {
            return $gun . ' gün';
        } elseif ($gun < 30) {
            $hafta = floor($gun / 7);
            $kalanGun = $gun % 7;
            return $hafta . ' hafta' . ($kalanGun > 0 ? ' ' . $kalanGun . ' gün' : '');
        } else {
            $ay = floor($gun / 30);
            $kalanGun = $gun % 30;
            return $ay . ' ay' . ($kalanGun > 0 ? ' ' . $kalanGun . ' gün' : '');
        }
    }

    /**
     * Son aktiviteden bu yana geçen süre
     */
    public function getSonAktiviteSuresiAttribute(): ?int
    {
        if (!$this->son_aktivite_tarihi) {
            return null;
        }

        return $this->son_aktivite_tarihi->diffInDays(now());
    }

    /**
     * Formatlanmış son aktivite süresi
     */
    public function getFormattedSonAktiviteSuresiAttribute(): string
    {
        $gun = $this->son_aktivite_suresi;
        
        if ($gun === null) {
            return 'Hiç aktivite yok';
        }

        if ($gun === 0) {
            return 'Bugün';
        } elseif ($gun === 1) {
            return 'Dün';
        } elseif ($gun < 7) {
            return $gun . ' gün önce';
        } elseif ($gun < 30) {
            $hafta = floor($gun / 7);
            return $hafta . ' hafta önce';
        } else {
            $ay = floor($gun / 30);
            return $ay . ' ay önce';
        }
    }

    /**
     * Karar tarihine kalan süre
     */
    public function getKararTarihineKalanSureAttribute(): ?int
    {
        if (!$this->beklenen_karar_tarihi) {
            return null;
        }

        return now()->diffInDays($this->beklenen_karar_tarihi, false);
    }

    /**
     * Formatlanmış karar tarihine kalan süre
     */
    public function getFormattedKararTarihineKalanSureAttribute(): string
    {
        $gun = $this->karar_tarihine_kalan_sure;
        
        if ($gun === null) {
            return 'Karar tarihi belirlenmemiş';
        }

        if ($gun < 0) {
            return abs($gun) . ' gün gecikmiş';
        } elseif ($gun === 0) {
            return 'Bugün karar verilmeli';
        } elseif ($gun === 1) {
            return 'Yarın karar verilmeli';
        } else {
            return $gun . ' gün kaldı';
        }
    }

    /**
     * İlişki önceliği (çoklu faktör bazlı)
     */
    public function getOncelikAttribute(): int
    {
        $puan = 0;
        
        // İlişki tipi puanı
        $puan += $this->iliski_tipi?->priorityScore() ?? 0;
        
        // İlgi seviyesi puanı
        $puan += ($this->ilgi_seviyesi ?? 0) * 10;
        
        // Aciliyet seviyesi puanı
        $puan += ($this->aciliyet_seviyesi ?? 0) * 8;
        
        // Teklif miktarı puanı (mülk fiyatına göre)
        if ($this->teklif_miktari && $this->mulk?->fiyat) {
            $teklifOrani = ($this->teklif_miktari / $this->mulk->fiyat) * 100;
            $puan += min($teklifOrani, 50); // Maksimum 50 puan
        }
        
        // Son aktivite puanı (yakın zamanda aktivite varsa bonus)
        $sonAktivite = $this->son_aktivite_suresi;
        if ($sonAktivite !== null) {
            if ($sonAktivite <= 3) {
                $puan += 30;
            } elseif ($sonAktivite <= 7) {
                $puan += 20;
            } elseif ($sonAktivite <= 14) {
                $puan += 10;
            }
        }
        
        // Karar tarihi yaklaşıyorsa bonus
        $kararSuresi = $this->karar_tarihine_kalan_sure;
        if ($kararSuresi !== null && $kararSuresi >= 0 && $kararSuresi <= 7) {
            $puan += (7 - $kararSuresi) * 5;
        }

        return (int) $puan;
    }

    /**
     * İlişki durumu özeti
     */
    public function getIliskiDurumuOzetiAttribute(): array
    {
        return [
            'tip' => $this->iliski_tipi_label,
            'tip_rengi' => $this->iliski_tipi_color,
            'durum' => $this->durum_label,
            'durum_rengi' => $this->durum_color,
            'ilgi_seviyesi' => $this->ilgi_seviyesi_label,
            'ilgi_rengi' => $this->ilgi_seviyesi_color,
            'aciliyet' => $this->aciliyet_seviyesi_label,
            'aciliyet_rengi' => $this->aciliyet_seviyesi_color,
            'sure' => $this->formatted_iliski_suresi,
            'son_aktivite' => $this->formatted_son_aktivite_suresi,
            'oncelik' => $this->oncelik,
        ];
    }

    /**
     * Display name
     */
    public function getDisplayNameAttribute(): string
    {
        $musteri = $this->musteri?->display_name ?? 'Bilinmeyen Müşteri';
        $mulk = $this->mulk?->baslik ?? 'Bilinmeyen Mülk';
        $tip = $this->iliski_tipi_label;
        
        return "{$musteri} - {$mulk} ({$tip})";
    }

    /**
     * Aktif ilişkiler scope
     */
    public function scopeActive($query)
    {
        $activeStates = array_map(fn($state) => $state->value, IliskiDurumu::activeStates());
        return $query->whereIn('durum', $activeStates);
    }

    /**
     * Tamamlanmış ilişkiler scope
     */
    public function scopeCompleted($query)
    {
        $completedStates = array_map(fn($state) => $state->value, IliskiDurumu::completedStates());
        return $query->whereIn('durum', $completedStates);
    }

    /**
     * İnaktif ilişkiler scope
     */
    public function scopeInactive($query)
    {
        $inactiveStates = array_map(fn($state) => $state->value, IliskiDurumu::inactiveStates());
        return $query->whereIn('durum', $inactiveStates);
    }

    /**
     * İlişki tipine göre scope
     */
    public function scopeByType($query, IliskiTipi|string $type)
    {
        $value = $type instanceof IliskiTipi ? $type->value : $type;
        return $query->where('iliski_tipi', $value);
    }

    /**
     * Duruma göre scope
     */
    public function scopeByStatus($query, IliskiDurumu|string $durum)
    {
        $value = $durum instanceof IliskiDurumu ? $durum->value : $durum;
        return $query->where('durum', $value);
    }

    /**
     * İlgi seviyesine göre scope
     */
    public function scopeByInterestLevel($query, int $minLevel = 1, int $maxLevel = 10)
    {
        return $query->whereBetween('ilgi_seviyesi', [$minLevel, $maxLevel]);
    }

    /**
     * Yüksek ilgi seviyesi scope
     */
    public function scopeHighInterest($query)
    {
        return $query->where('ilgi_seviyesi', '>=', 7);
    }

    /**
     * Aciliyet seviyesine göre scope
     */
    public function scopeByUrgency($query, int $minLevel = 1, int $maxLevel = 10)
    {
        return $query->whereBetween('aciliyet_seviyesi', [$minLevel, $maxLevel]);
    }

    /**
     * Yüksek aciliyet scope
     */
    public function scopeHighUrgency($query)
    {
        return $query->where('aciliyet_seviyesi', '>=', 7);
    }

    /**
     * Müşteriye göre scope
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('musteri_id', $customerId);
    }

    /**
     * Mülke göre scope
     */
    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('mulk_id', $propertyId);
    }

    /**
     * Sorumlu personele göre scope
     */
    public function scopeByPersonel($query, $personelId)
    {
        return $query->where('sorumlu_personel_id', $personelId);
    }

    /**
     * Teklif miktarı aralığına göre scope
     */
    public function scopeByOfferAmount($query, float $minAmount = null, float $maxAmount = null)
    {
        if ($minAmount !== null) {
            $query->where('teklif_miktari', '>=', $minAmount);
        }

        if ($maxAmount !== null) {
            $query->where('teklif_miktari', '<=', $maxAmount);
        }

        return $query;
    }

    /**
     * Teklif verilmiş ilişkiler scope
     */
    public function scopeWithOffer($query)
    {
        return $query->whereNotNull('teklif_miktari')->where('teklif_miktari', '>', 0);
    }

    /**
     * Karar tarihi yaklaşan ilişkiler scope
     */
    public function scopeDecisionDue($query, int $days = 7)
    {
        return $query->whereNotNull('beklenen_karar_tarihi')
            ->whereBetween('beklenen_karar_tarihi', [now(), now()->addDays($days)]);
    }

    /**
     * Karar tarihi geçmiş ilişkiler scope
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('beklenen_karar_tarihi')
            ->where('beklenen_karar_tarihi', '<', now())
            ->active();
    }

    /**
     * Son aktivite tarihine göre scope
     */
    public function scopeByLastActivity($query, int $days = 30)
    {
        return $query->where('son_aktivite_tarihi', '>=', now()->subDays($days));
    }

    /**
     * Uzun süredir aktivite olmayan scope
     */
    public function scopeStale($query, int $days = 30)
    {
        return $query->where(function ($q) use ($days) {
            $q->where('son_aktivite_tarihi', '<', now()->subDays($days))
              ->orWhereNull('son_aktivite_tarihi');
        })->active();
    }

    /**
     * Etikete göre scope
     */
    public function scopeByTag($query, string $tag)
    {
        return $query->whereJsonContains('etiketler', $tag);
    }

    /**
     * Finansman durumuna göre scope
     */
    public function scopeByFinancing($query, string $durum)
    {
        return $query->where('finansman_durumu', $durum);
    }

    /**
     * Referans kaynağına göre scope
     */
    public function scopeByReferenceSource($query, string $kaynak)
    {
        return $query->where('referans_kaynak', 'like', "%{$kaynak}%");
    }

    /**
     * Yüksek öncelikli ilişkiler scope
     */
    public function scopeHighPriority($query)
    {
        return $query->where(function ($q) {
            $q->where('ilgi_seviyesi', '>=', 8)
              ->orWhere('aciliyet_seviyesi', '>=', 8)
              ->orWhereIn('iliski_tipi', array_map(fn($type) => $type->value, [
                  IliskiTipi::TEKLIF_VERDI,
                  IliskiTipi::MUZAKERE_EDIYOR,
                  IliskiTipi::SOZLESME_IMZALADI
              ]));
        });
    }

    /**
     * Takip gereken ilişkiler scope
     */
    public function scopeRequiresFollowUp($query)
    {
        $followUpTypes = array_map(fn($type) => $type->value, IliskiTipi::requiresFollowUp());
        $followUpStates = array_map(fn($state) => $state->value, IliskiDurumu::requiresFollowUp());
        
        return $query->whereIn('iliski_tipi', $followUpTypes)
            ->whereIn('durum', $followUpStates);
    }

    /**
     * Satış sürecindeki ilişkiler scope
     */
    public function scopeInSalesProcess($query)
    {
        $salesTypes = array_map(fn($type) => $type->value, IliskiTipi::salesTypes());
        return $query->whereIn('iliski_tipi', $salesTypes)->active();
    }

    /**
     * Kiralama sürecindeki ilişkiler scope
     */
    public function scopeInRentalProcess($query)
    {
        $rentalTypes = array_map(fn($type) => $type->value, IliskiTipi::rentalTypes());
        return $query->whereIn('iliski_tipi', $rentalTypes)->active();
    }

    /**
     * Son iletişim tarihi
     */
    public function getSonIletisimTarihiAttribute(): ?Carbon
    {
        $sonHizmet = $this->hizmetler()->latest('hizmet_tarihi')->first();
        return $sonHizmet?->hizmet_tarihi;
    }

    /**
     * İletişim sıklığı (gün)
     */
    public function getIletisimSikligiAttribute(): ?int
    {
        $sonIletisim = $this->son_iletisim_tarihi;
        if (!$sonIletisim) {
            return null;
        }

        return $sonIletisim->diffInDays(now());
    }

    /**
     * Hizmet sayısı
     */
    public function getHizmetSayisiAttribute(): int
    {
        return $this->hizmetler()->count();
    }

    /**
     * Son 30 gündeki hizmet sayısı
     */
    public function getSon30GunHizmetSayisiAttribute(): int
    {
        return $this->hizmetler()
            ->where('hizmet_tarihi', '>=', now()->subDays(30))
            ->count();
    }

    /**
     * Avantaj sayısı
     */
    public function getAvantajSayisiAttribute(): int
    {
        return is_array($this->avantajlar) ? count($this->avantajlar) : 0;
    }

    /**
     * Dezavantaj sayısı
     */
    public function getDezavantajSayisiAttribute(): int
    {
        return is_array($this->dezavantajlar) ? count($this->dezavantajlar) : 0;
    }

    /**
     * Özel istek sayısı
     */
    public function getOzelIstekSayisiAttribute(): int
    {
        return is_array($this->ozel_istekler) ? count($this->ozel_istekler) : 0;
    }

    /**
     * Etiket sayısı
     */
    public function getEtiketSayisiAttribute(): int
    {
        return is_array($this->etiketler) ? count($this->etiketler) : 0;
    }

    /**
     * İlişki skoru (0-100 arası)
     */
    public function getIliskiSkoruAttribute(): int
    {
        $skor = 0;
        
        // İlgi seviyesi (0-30 puan)
        $skor += ($this->ilgi_seviyesi ?? 0) * 3;
        
        // İlişki tipi (0-25 puan)
        $tipSkoru = match ($this->iliski_tipi) {
            IliskiTipi::SOZLESME_IMZALADI => 25,
            IliskiTipi::SATIN_ALDI, IliskiTipi::KIRAYA_ALDI => 23,
            IliskiTipi::MUZAKERE_EDIYOR => 20,
            IliskiTipi::TEKLIF_VERDI => 18,
            IliskiTipi::DEGERLENDIRIYOR => 15,
            IliskiTipi::GORUSTU => 12,
            IliskiTipi::ILGILENIYOR => 8,
            IliskiTipi::KIRAYA_VERDI => 10,
            IliskiTipi::IPTAL_ETTI => 0,
            default => 5
        };
        $skor += $tipSkoru;
        
        // Son aktivite (0-20 puan)
        $sonAktivite = $this->son_aktivite_suresi;
        if ($sonAktivite !== null) {
            if ($sonAktivite <= 1) $skor += 20;
            elseif ($sonAktivite <= 3) $skor += 15;
            elseif ($sonAktivite <= 7) $skor += 10;
            elseif ($sonAktivite <= 14) $skor += 5;
        }
        
        // Hizmet sıklığı (0-15 puan)
        $hizmetSayisi = $this->son_30_gun_hizmet_sayisi;
        if ($hizmetSayisi >= 10) $skor += 15;
        elseif ($hizmetSayisi >= 5) $skor += 10;
        elseif ($hizmetSayisi >= 2) $skor += 5;
        
        // Teklif durumu (0-10 puan)
        if ($this->teklif_miktari && $this->mulk?->fiyat) {
            $teklifOrani = ($this->teklif_miktari / $this->mulk->fiyat) * 100;
            if ($teklifOrani >= 90) $skor += 10;
            elseif ($teklifOrani >= 80) $skor += 8;
            elseif ($teklifOrani >= 70) $skor += 6;
            elseif ($teklifOrani >= 60) $skor += 4;
            elseif ($teklifOrani >= 50) $skor += 2;
        }
        
        return min($skor, 100);
    }

    /**
     * İlişki skoru etiketi
     */
    public function getIliskiSkoruLabelAttribute(): string
    {
        $skor = $this->iliski_skoru;
        
        return match (true) {
            $skor >= 80 => 'Mükemmel',
            $skor >= 60 => 'İyi',
            $skor >= 40 => 'Orta',
            $skor >= 20 => 'Zayıf',
            default => 'Çok Zayıf'
        };
    }

    /**
     * İlişki skoru rengi
     */
    public function getIliskiSkoruColorAttribute(): string
    {
        $skor = $this->iliski_skoru;
        
        return match (true) {
            $skor >= 80 => 'green',
            $skor >= 60 => 'blue',
            $skor >= 40 => 'yellow',
            $skor >= 20 => 'orange',
            default => 'red'
        };
    }

    /**
     * Validation kuralları
     */
    public static function getValidationRules(): array
    {
        $iliskiTipleri = array_map(fn($case) => $case->value, IliskiTipi::cases());
        $durumlar = array_map(fn($case) => $case->value, IliskiDurumu::cases());

        return [
            'musteri_id' => 'required|exists:musteri,id',
            'mulk_id' => 'required|exists:mulkler,id',
            'mulk_type' => 'required|string|max:50',
            'iliski_tipi' => 'required|in:' . implode(',', $iliskiTipleri),
            'baslangic_tarihi' => 'nullable|date',
            'bitis_tarihi' => 'nullable|date|after:baslangic_tarihi',
            'durum' => 'required|in:' . implode(',', $durumlar),
            'ilgi_seviyesi' => 'integer|min:1|max:10',
            'teklif_miktari' => 'nullable|numeric|min:0',
            'teklif_para_birimi' => 'nullable|string|size:3|in:TRY,USD,EUR',
            'son_teklif_tarihi' => 'nullable|date',
            'beklenen_karar_tarihi' => 'nullable|date|after:today',
            'karar_verme_sebebi' => 'nullable|string|max:1000',
            'rekabet_durumu' => 'nullable|string|max:1000',
            'avantajlar' => 'nullable|array',
            'avantajlar.*' => 'string|max:255',
            'dezavantajlar' => 'nullable|array',
            'dezavantajlar.*' => 'string|max:255',
            'ozel_istekler' => 'nullable|array',
            'ozel_istekler.*' => 'string|max:255',
            'finansman_durumu' => 'nullable|string|max:255',
            'aciliyet_seviyesi' => 'integer|min:1|max:10',
            'referans_kaynak' => 'nullable|string|max:255',
            'takip_sikligi' => 'nullable|integer|min:1|max:365',
            'son_aktivite_tarihi' => 'nullable|date',
            'sonraki_adim' => 'nullable|string|max:1000',
            'sorumlu_personel_id' => 'nullable|exists:users,id',
            'notlar' => 'nullable|string|max:10000',
            'etiketler' => 'nullable|array',
            'etiketler.*' => 'string|max:50',
            'ozel_alanlar' => 'nullable|array',
            'aktif_mi' => 'boolean',
            'siralama' => 'integer|min:0',
        ];
    }

    /**
     * İlişki güncelleme
     */
    public function updateIliski(array $data): bool
    {
        $degisiklikler = [];

        // İlgi seviyesi değişikliğini logla
        if (isset($data['ilgi_seviyesi']) && $data['ilgi_seviyesi'] !== $this->ilgi_seviyesi) {
            $degisiklikler[] = 'İlgi seviyesi ' . $this->ilgi_seviyesi . ' → ' . $data['ilgi_seviyesi'];
        }

        // Durum değişikliğini logla
        if (isset($data['durum']) && $data['durum'] !== $this->durum?->value) {
            $eskiDurum = $this->durum?->label() ?? 'Bilinmiyor';
            $yeniDurum = IliskiDurumu::fromValue($data['durum'])?->label() ?? 'Bilinmiyor';
            $degisiklikler[] = 'Durum ' . $eskiDurum . ' → ' . $yeniDurum;
        }

        // İlişki tipi değişikliğini logla
        if (isset($data['iliski_tipi']) && $data['iliski_tipi'] !== $this->iliski_tipi?->value) {
            $eskiTip = $this->iliski_tipi?->label() ?? 'Bilinmiyor';
            $yeniTip = IliskiTipi::fromValue($data['iliski_tipi'])?->label() ?? 'Bilinmiyor';
            $degisiklikler[] = 'İlişki tipi ' . $eskiTip . ' → ' . $yeniTip;
        }

        // Teklif miktarı değişikliğini logla
        if (isset($data['teklif_miktari']) && $data['teklif_miktari'] !== $this->teklif_miktari) {
            $eskiTeklif = $this->teklif_miktari ? number_format($this->teklif_miktari, 0, ',', '.') : 'Yok';
            $yeniTeklif = $data['teklif_miktari'] ? number_format($data['teklif_miktari'], 0, ',', '.') : 'Yok';
            $degisiklikler[] = 'Teklif miktarı ' . $eskiTeklif . ' → ' . $yeniTeklif;
        }

        // Değişiklikleri notlara ekle
        if (!empty($degisiklikler)) {
            $logEntry = now()->format('d.m.Y H:i') . ' - ' . implode(', ', $degisiklikler);
            $data['notlar'] = ($this->notlar ? $this->notlar . "\n" : '') . $logEntry;
        }

        // Son aktivite tarihini güncelle
        $data['son_aktivite_tarihi'] = now();

        return $this->update($data);
    }

    /**
     * Etiket ekle
     */
    public function addTag(string $tag): void
    {
        $etiketler = $this->etiketler ?? [];
        
        if (!in_array($tag, $etiketler)) {
            $etiketler[] = $tag;
            $this->update(['etiketler' => $etiketler]);
        }
    }

    /**
     * Etiket kaldır
     */
    public function removeTag(string $tag): void
    {
        $etiketler = $this->etiketler ?? [];
        $etiketler = array_values(array_filter($etiketler, fn($t) => $t !== $tag));
        $this->update(['etiketler' => $etiketler]);
    }

    /**
     * Avantaj ekle
     */
    public function addAdvantage(string $avantaj): void
    {
        $avantajlar = $this->avantajlar ?? [];
        
        if (!in_array($avantaj, $avantajlar)) {
            $avantajlar[] = $avantaj;
            $this->update(['avantajlar' => $avantajlar]);
        }
    }

    /**
     * Dezavantaj ekle
     */
    public function addDisadvantage(string $dezavantaj): void
    {
        $dezavantajlar = $this->dezavantajlar ?? [];
        
        if (!in_array($dezavantaj, $dezavantajlar)) {
            $dezavantajlar[] = $dezavantaj;
            $this->update(['dezavantajlar' => $dezavantajlar]);
        }
    }

    /**
     * Özel istek ekle
     */
    public function addSpecialRequest(string $istek): void
    {
        $ozelIstekler = $this->ozel_istekler ?? [];
        
        if (!in_array($istek, $ozelIstekler)) {
            $ozelIstekler[] = $istek;
            $this->update(['ozel_istekler' => $ozelIstekler]);
        }
    }

    /**
     * Sonraki adımı güncelle
     */
    public function updateNextStep(string $adim): void
    {
        $this->update([
            'sonraki_adim' => $adim,
            'son_aktivite_tarihi' => now(),
        ]);
    }

    /**
     * Teklif ver
     */
    public function makeOffer(float $miktar, string $paraBirimi = 'TRY'): void
    {
        $this->update([
            'teklif_miktari' => $miktar,
            'teklif_para_birimi' => $paraBirimi,
            'son_teklif_tarihi' => now(),
            'iliski_tipi' => IliskiTipi::TEKLIF_VERDI,
            'son_aktivite_tarihi' => now(),
        ]);
    }

    /**
     * Karar tarihi belirle
     */
    public function setDecisionDate(Carbon $tarih): void
    {
        $this->update([
            'beklenen_karar_tarihi' => $tarih,
            'son_aktivite_tarihi' => now(),
        ]);
    }

    /**
     * İlişki durumu özeti
     */
    public function getStatusSummary(): array
    {
        return [
            'id' => $this->id,
            'musteri' => $this->musteri?->display_name,
            'mulk' => $this->mulk?->baslik,
            'tip' => $this->iliski_tipi_label,
            'tip_rengi' => $this->iliski_tipi_color,
            'durum' => $this->durum_label,
            'durum_rengi' => $this->durum_color,
            'ilgi_seviyesi' => $this->ilgi_seviyesi,
            'ilgi_rengi' => $this->ilgi_seviyesi_color,
            'aciliyet' => $this->aciliyet_seviyesi,
            'aciliyet_rengi' => $this->aciliyet_seviyesi_color,
            'teklif' => $this->formatted_teklif_miktari,
            'sure' => $this->formatted_iliski_suresi,
            'son_aktivite' => $this->formatted_son_aktivite_suresi,
            'skor' => $this->iliski_skoru,
            'skor_label' => $this->iliski_skoru_label,
            'skor_rengi' => $this->iliski_skoru_color,
            'oncelik' => $this->oncelik,
            'hizmet_sayisi' => $this->hizmet_sayisi,
            'etiket_sayisi' => $this->etiket_sayisi,
        ];
    }
}