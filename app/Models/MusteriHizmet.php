<?php

namespace App\Models;

use App\Models\Mulk\BaseMulk;
use App\Models\Musteri\Musteri;
use App\Models\User;
use App\Enums\HizmetTipi;
use App\Enums\HizmetSonucu;
use App\Enums\DegerlendirmeTipi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MusteriHizmet extends BaseModel
{
    protected $table = 'musteri_hizmetleri';

    protected $fillable = [
        'musteri_id',
        'personel_id',
        'hizmet_tipi',
        'hizmet_tarihi',
        'bitis_tarihi',
        'lokasyon',
        'katilimcilar',
        'aciklama',
        'sonuc',
        'sonuc_tipi',
        'degerlendirme',
        'sure_dakika',
        'mulk_id',
        'mulk_type',
        'takip_tarihi',
        'takip_notu',
        'maliyet',
        'para_birimi',
        'etiketler',
        'dosyalar',
        'aktif_mi',
        'siralama',
    ];

    protected $casts = [
        'hizmet_tipi' => HizmetTipi::class,
        'sonuc_tipi' => HizmetSonucu::class,
        'hizmet_tarihi' => 'datetime',
        'bitis_tarihi' => 'datetime',
        'takip_tarihi' => 'datetime',
        'degerlendirme' => 'json',
        'katilimcilar' => 'json',
        'etiketler' => 'json',
        'dosyalar' => 'json',
        'sure_dakika' => 'integer',
        'maliyet' => 'decimal:2',
        'aktif_mi' => 'boolean',
        'siralama' => 'integer',
    ];

    protected $searchableFields = [
        'aciklama',
        'sonuc',
        'takip_notu',
        'lokasyon',
    ];

    protected $sortableFields = [
        'hizmet_tarihi',
        'bitis_tarihi',
        'sure_dakika',
        'maliyet',
        'olusturma_tarihi',
    ];

    protected $defaultSortField = 'hizmet_tarihi';
    protected $defaultSortDirection = 'desc';

    /**
     * Müşteri ilişkisi
     */
    public function musteri(): BelongsTo
    {
        return $this->belongsTo(Musteri::class);
    }

    /**
     * Personel ilişkisi
     */
    public function personel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'personel_id');
    }

    /**
     * Mülk ilişkisi (opsiyonel)
     */
    public function mulk(): BelongsTo
    {
        return $this->belongsTo(BaseMulk::class, 'mulk_id');
    }

    /**
     * Takip hizmetleri (bu hizmetten türeyen)
     */
    public function takipHizmetleri(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_hizmet_id');
    }

    /**
     * Ana hizmet (bu hizmet bir takip hizmetiyse)
     */
    public function anaHizmet(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_hizmet_id');
    }

    /**
     * Hizmet notları
     */
    public function notlar(): MorphMany
    {
        return $this->morphMany(\App\Models\Not::class, 'notable');
    }

    /**
     * Hizmet dökümanları
     */
    public function dokumanlar(): MorphMany
    {
        return $this->morphMany(\App\Models\Dokuman::class, 'documentable');
    }

    /**
     * Hizmet resimleri
     */
    public function resimler(): MorphMany
    {
        return $this->morphMany(\App\Models\Resim::class, 'imageable')
            ->where('aktif_mi', true)
            ->orderBy('siralama')
            ->orderBy('olusturma_tarihi', 'desc');
    }

    /**
     * Hizmet tipi etiketi
     */
    public function getHizmetTipiLabelAttribute(): string
    {
        return $this->hizmet_tipi?->label() ?? 'Bilinmiyor';
    }

    /**
     * Hizmet tipi ikonu
     */
    public function getHizmetTipiIconAttribute(): string
    {
        return $this->hizmet_tipi?->icon() ?? 'heroicon-o-question-mark-circle';
    }

    /**
     * Hizmet tipi rengi
     */
    public function getHizmetTipiColorAttribute(): string
    {
        return $this->hizmet_tipi?->color() ?? 'gray';
    }

    /**
     * Sonuç tipi etiketi
     */
    public function getSonucTipiLabelAttribute(): string
    {
        return $this->sonuc_tipi?->label() ?? 'Belirtilmemiş';
    }

    /**
     * Sonuç tipi rengi
     */
    public function getSonucTipiColorAttribute(): string
    {
        return $this->sonuc_tipi?->color() ?? 'gray';
    }

    /**
     * Sonuç tipi ikonu
     */
    public function getSonucTipiIconAttribute(): string
    {
        return $this->sonuc_tipi?->icon() ?? 'heroicon-o-question-mark-circle';
    }

    /**
     * Değerlendirme tipi
     */
    public function getDegerlendirmeTipiAttribute(): ?DegerlendirmeTipi
    {
        $tip = $this->degerlendirme['tip'] ?? null;
        return $tip ? DegerlendirmeTipi::fromValue($tip) : null;
    }

    /**
     * Değerlendirme puanı
     */
    public function getDegerlendirmePuaniAttribute(): ?int
    {
        return $this->degerlendirme['puan'] ?? null;
    }

    /**
     * Değerlendirme notları
     */
    public function getDegerlendirmeNotlariAttribute(): ?string
    {
        return $this->degerlendirme['notlar'] ?? null;
    }

    /**
     * Değerlendirme tarihi
     */
    public function getDegerlendirmeTarihiAttribute(): ?\Carbon\Carbon
    {
        $tarih = $this->degerlendirme['tarih'] ?? null;
        return $tarih ? \Carbon\Carbon::parse($tarih) : null;
    }

    /**
     * Değerlendirme rengi
     */
    public function getDegerlendirmeColorAttribute(): string
    {
        return $this->degerlendirme_tipi?->color() ?? 'gray';
    }

    /**
     * Değerlendirme ikonu
     */
    public function getDegerlendirmeIconAttribute(): string
    {
        return $this->degerlendirme_tipi?->icon() ?? 'heroicon-o-minus';
    }

    /**
     * Değerlendirme etiketi
     */
    public function getDegerlendirmeLabelAttribute(): string
    {
        return $this->degerlendirme_tipi?->label() ?? 'Değerlendirilmemiş';
    }

    /**
     * Süre formatlanmış
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->sure_dakika) {
            return 'Belirtilmemiş';
        }

        if ($this->sure_dakika < 60) {
            return $this->sure_dakika . ' dakika';
        }

        $hours = floor($this->sure_dakika / 60);
        $minutes = $this->sure_dakika % 60;

        if ($minutes === 0) {
            return $hours . ' saat';
        }

        return $hours . ' saat ' . $minutes . ' dakika';
    }

    /**
     * Formatlanmış maliyet
     */
    public function getFormattedMaliyetAttribute(): string
    {
        if (!$this->maliyet) {
            return 'Belirtilmemiş';
        }

        $currency = match ($this->para_birimi) {
            'USD' => '$',
            'EUR' => '€',
            default => '₺'
        };

        return number_format($this->maliyet, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Hizmet süresi (başlangıç-bitiş arası)
     */
    public function getGercekSureAttribute(): ?int
    {
        if (!$this->bitis_tarihi) {
            return null;
        }

        return $this->hizmet_tarihi->diffInMinutes($this->bitis_tarihi);
    }

    /**
     * Formatlanmış gerçek süre
     */
    public function getFormattedGercekSureAttribute(): string
    {
        $sure = $this->gercek_sure;
        
        if (!$sure) {
            return 'Hesaplanamaz';
        }

        if ($sure < 60) {
            return $sure . ' dakika';
        }

        $hours = floor($sure / 60);
        $minutes = $sure % 60;

        if ($minutes === 0) {
            return $hours . ' saat';
        }

        return $hours . ' saat ' . $minutes . ' dakika';
    }

    /**
     * Katılımcı sayısı
     */
    public function getKatilimciSayisiAttribute(): int
    {
        if (!$this->katilimcilar || !is_array($this->katilimcilar)) {
            return 0;
        }

        return count($this->katilimcilar);
    }

    /**
     * Etiket sayısı
     */
    public function getEtiketSayisiAttribute(): int
    {
        if (!$this->etiketler || !is_array($this->etiketler)) {
            return 0;
        }

        return count($this->etiketler);
    }

    /**
     * Dosya sayısı
     */
    public function getDosyaSayisiAttribute(): int
    {
        if (!$this->dosyalar || !is_array($this->dosyalar)) {
            return 0;
        }

        return count($this->dosyalar);
    }

    /**
     * Hizmet durumu
     */
    public function getHizmetDurumuAttribute(): string
    {
        if ($this->takip_tarihi && $this->takip_tarihi->isFuture()) {
            return 'Takip Bekliyor';
        }

        if ($this->sonuc_tipi?->requiresFollowUp()) {
            return 'Takip Gerekli';
        }

        if ($this->sonuc_tipi?->isPositive()) {
            return 'Tamamlandı';
        }

        if ($this->sonuc_tipi?->isNegative()) {
            return 'Başarısız';
        }

        return 'Devam Ediyor';
    }

    /**
     * Hizmet durumu rengi
     */
    public function getHizmetDurumuColorAttribute(): string
    {
        return match ($this->hizmet_durumu) {
            'Tamamlandı' => 'green',
            'Takip Bekliyor', 'Takip Gerekli' => 'yellow',
            'Başarısız' => 'red',
            'Devam Ediyor' => 'blue',
            default => 'gray'
        };
    }

    /**
     * Display name
     */
    public function getDisplayNameAttribute(): string
    {
        $musteri = $this->musteri?->display_name ?? 'Bilinmeyen Müşteri';
        $tip = $this->hizmet_tipi_label;
        $tarih = $this->hizmet_tarihi->format('d.m.Y H:i');
        
        return "{$musteri} - {$tip} ({$tarih})";
    }

    /**
     * Hizmet tipine göre scope
     */
    public function scopeByType($query, HizmetTipi|string $type)
    {
        $value = $type instanceof HizmetTipi ? $type->value : $type;
        return $query->where('hizmet_tipi', $value);
    }

    /**
     * Müşteriye göre scope
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('musteri_id', $customerId);
    }

    /**
     * Personele göre scope
     */
    public function scopeByPersonel($query, $personelId)
    {
        return $query->where('personel_id', $personelId);
    }

    /**
     * Mülke göre scope
     */
    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('mulk_id', $propertyId);
    }

    /**
     * Tarih aralığına göre scope
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('hizmet_tarihi', [$startDate, $endDate]);
    }

    /**
     * Sonuç tipine göre scope
     */
    public function scopeBySonucTipi($query, HizmetSonucu|string $sonuc)
    {
        $value = $sonuc instanceof HizmetSonucu ? $sonuc->value : $sonuc;
        return $query->where('sonuc_tipi', $value);
    }

    /**
     * Değerlendirme tipine göre scope
     */
    public function scopeByEvaluation($query, DegerlendirmeTipi|string $type)
    {
        $value = $type instanceof DegerlendirmeTipi ? $type->value : $type;
        return $query->whereJsonContains('degerlendirme->tip', $value);
    }

    /**
     * Olumlu değerlendirmeler scope
     */
    public function scopePositive($query)
    {
        return $query->whereJsonContains('degerlendirme->tip', DegerlendirmeTipi::OLUMLU->value);
    }

    /**
     * Olumsuz değerlendirmeler scope
     */
    public function scopeNegative($query)
    {
        return $query->whereJsonContains('degerlendirme->tip', DegerlendirmeTipi::OLUMSUZ->value);
    }

    /**
     * Nötr değerlendirmeler scope
     */
    public function scopeNeutral($query)
    {
        return $query->whereJsonContains('degerlendirme->tip', DegerlendirmeTipi::NOTR->value);
    }

    /**
     * Başarılı hizmetler scope
     */
    public function scopeSuccessful($query)
    {
        $successfulResults = array_map(fn($result) => $result->value, HizmetSonucu::positiveResults());
        return $query->whereIn('sonuc_tipi', $successfulResults);
    }

    /**
     * Başarısız hizmetler scope
     */
    public function scopeFailed($query)
    {
        $failedResults = array_map(fn($result) => $result->value, HizmetSonucu::negativeResults());
        return $query->whereIn('sonuc_tipi', $failedResults);
    }

    /**
     * Takip gereken hizmetler scope
     */
    public function scopeRequiresFollowUp($query)
    {
        $followUpResults = array_map(fn($result) => $result->value, HizmetSonucu::requiresFollowUp());
        return $query->whereIn('sonuc_tipi', $followUpResults);
    }

    /**
     * Bugün yapılan hizmetler scope
     */
    public function scopeToday($query)
    {
        return $query->whereDate('hizmet_tarihi', today());
    }

    /**
     * Bu hafta yapılan hizmetler scope
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('hizmet_tarihi', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Bu ay yapılan hizmetler scope
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('hizmet_tarihi', now()->month)
                    ->whereYear('hizmet_tarihi', now()->year);
    }

    /**
     * Süre aralığına göre scope
     */
    public function scopeByDuration($query, int $minMinutes = null, int $maxMinutes = null)
    {
        if ($minMinutes !== null) {
            $query->where('sure_dakika', '>=', $minMinutes);
        }

        if ($maxMinutes !== null) {
            $query->where('sure_dakika', '<=', $maxMinutes);
        }

        return $query;
    }

    /**
     * Maliyet aralığına göre scope
     */
    public function scopeByCost($query, float $minCost = null, float $maxCost = null)
    {
        if ($minCost !== null) {
            $query->where('maliyet', '>=', $minCost);
        }

        if ($maxCost !== null) {
            $query->where('maliyet', '<=', $maxCost);
        }

        return $query;
    }

    /**
     * Etikete göre scope
     */
    public function scopeByTag($query, string $tag)
    {
        return $query->whereJsonContains('etiketler', $tag);
    }

    /**
     * Lokasyona göre scope
     */
    public function scopeByLocation($query, string $location)
    {
        return $query->where('lokasyon', 'like', "%{$location}%");
    }

    /**
     * İletişim hizmetleri scope
     */
    public function scopeCommunication($query)
    {
        $communicationTypes = array_map(fn($type) => $type->value, HizmetTipi::communicationTypes());
        return $query->whereIn('hizmet_tipi', $communicationTypes);
    }

    /**
     * Fiziksel hizmetler scope
     */
    public function scopePhysical($query)
    {
        $physicalTypes = array_map(fn($type) => $type->value, HizmetTipi::physicalTypes());
        return $query->whereIn('hizmet_tipi', $physicalTypes);
    }

    /**
     * Validation kuralları
     */
    public static function getValidationRules(): array
    {
        $hizmetTipleri = array_map(fn($case) => $case->value, HizmetTipi::cases());
        $sonucTipleri = array_map(fn($case) => $case->value, HizmetSonucu::cases());
        $degerlendirmeTipleri = array_map(fn($case) => $case->value, DegerlendirmeTipi::cases());

        return [
            'musteri_id' => 'required|exists:musteri,id',
            'personel_id' => 'required|exists:users,id',
            'hizmet_tipi' => 'required|in:' . implode(',', $hizmetTipleri),
            'hizmet_tarihi' => 'required|date',
            'bitis_tarihi' => 'nullable|date|after:hizmet_tarihi',
            'lokasyon' => 'nullable|string|max:255',
            'katilimcilar' => 'nullable|array',
            'katilimcilar.*' => 'string|max:255',
            'aciklama' => 'nullable|string|max:5000',
            'sonuc' => 'nullable|string|max:5000',
            'sonuc_tipi' => 'nullable|in:' . implode(',', $sonucTipleri),
            'degerlendirme' => 'nullable|array',
            'degerlendirme.tip' => 'nullable|in:' . implode(',', $degerlendirmeTipleri),
            'degerlendirme.puan' => 'nullable|integer|min:1|max:10',
            'degerlendirme.notlar' => 'nullable|string|max:1000',
            'sure_dakika' => 'nullable|integer|min:1|max:1440',
            'mulk_id' => 'nullable|exists:mulkler,id',
            'mulk_type' => 'nullable|string|max:50',
            'takip_tarihi' => 'nullable|date|after:hizmet_tarihi',
            'takip_notu' => 'nullable|string|max:1000',
            'maliyet' => 'nullable|numeric|min:0',
            'para_birimi' => 'nullable|string|size:3|in:TRY,USD,EUR',
            'etiketler' => 'nullable|array',
            'etiketler.*' => 'string|max:50',
            'dosyalar' => 'nullable|array',
            'aktif_mi' => 'boolean',
            'siralama' => 'integer|min:0',
        ];
    }

    /**
     * Hizmet tipine göre özel validation kuralları
     */
    public function getTypeSpecificValidationRules(): array
    {
        if (!$this->hizmet_tipi) {
            return [];
        }

        $rules = [];

        // Süre gerektiren hizmetler
        if ($this->hizmet_tipi->requiresDuration()) {
            $rules['sure_dakika'] = 'required|integer|min:1|max:1440';
        }

        // Lokasyon gerektiren hizmetler
        if ($this->hizmet_tipi->requiresLocation()) {
            $rules['lokasyon'] = 'required|string|max:255';
        }

        // Katılımcı gerektiren hizmetler
        if ($this->hizmet_tipi->requiresParticipants()) {
            $rules['katilimcilar'] = 'required|array|min:1';
        }

        return $rules;
    }

    /**
     * Değerlendirme oluştur
     */
    public function createEvaluation(DegerlendirmeTipi|string $tip, int $puan, ?string $notlar = null): void
    {
        $tipValue = $tip instanceof DegerlendirmeTipi ? $tip->value : $tip;

        $this->update([
            'degerlendirme' => [
                'tip' => $tipValue,
                'puan' => $puan,
                'notlar' => $notlar,
                'tarih' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Takip hizmeti oluştur
     */
    public function createFollowUp(array $data): self
    {
        $followUpData = array_merge($data, [
            'musteri_id' => $this->musteri_id,
            'parent_hizmet_id' => $this->id,
            'hizmet_tarihi' => $this->takip_tarihi ?? now(),
        ]);

        return self::create($followUpData);
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
     * Katılımcı ekle
     */
    public function addParticipant(string $participant): void
    {
        $katilimcilar = $this->katilimcilar ?? [];
        
        if (!in_array($participant, $katilimcilar)) {
            $katilimcilar[] = $participant;
            $this->update(['katilimcilar' => $katilimcilar]);
        }
    }

    /**
     * Katılımcı kaldır
     */
    public function removeParticipant(string $participant): void
    {
        $katilimcilar = $this->katilimcilar ?? [];
        $katilimcilar = array_values(array_filter($katilimcilar, fn($k) => $k !== $participant));
        $this->update(['katilimcilar' => $katilimcilar]);
    }

    /**
     * Dosya ekle
     */
    public function addFile(string $fileName, string $filePath): void
    {
        $dosyalar = $this->dosyalar ?? [];
        
        $dosyalar[] = [
            'name' => $fileName,
            'path' => $filePath,
            'uploaded_at' => now()->toISOString(),
        ];
        
        $this->update(['dosyalar' => $dosyalar]);
    }

    /**
     * Hizmet özeti
     */
    public function getServiceSummary(): array
    {
        return [
            'id' => $this->id,
            'musteri' => $this->musteri?->display_name,
            'personel' => $this->personel?->name,
            'tip' => $this->hizmet_tipi_label,
            'tarih' => $this->hizmet_tarihi->format('d.m.Y H:i'),
            'sure' => $this->formatted_duration,
            'sonuc' => $this->sonuc_tipi_label,
            'degerlendirme' => $this->degerlendirme_label,
            'maliyet' => $this->formatted_maliyet,
            'durum' => $this->hizmet_durumu,
        ];
    }
}