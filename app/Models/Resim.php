<?php

namespace App\Models;

use App\Enums\ResimKategorisi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Resim extends BaseModel
{
    protected $table = 'resim';

    protected $fillable = [
        'url',
        'imageable_id',
        'imageable_type',
        'kategori',
        'baslik',
        'aciklama',
        'cekim_tarihi',
        'dosya_boyutu',
        'genislik',
        'yukseklik',
        'mime_type',
        'dosya_adi',
        'orijinal_dosya_adi',
        'hash',
        'exif_data',
        'thumbnail_url',
        'medium_url',
        'large_url',
        'is_processed',
        'processing_error',
        'upload_ip',
        'upload_user_agent',
        'yükleyen_id',
        'onay_durumu',
        'onaylayan_id',
        'onay_tarihi',
        'görüntülenme_sayisi',
        'son_görüntülenme_tarihi',
        'etiketler',
        'alt_text',
        'copyright_bilgisi',
        'aktif_mi',
        'siralama',
    ];

    protected $casts = [
        'aktif_mi' => 'boolean',
        'is_processed' => 'boolean',
        'cekim_tarihi' => 'datetime',
        'onay_tarihi' => 'datetime',
        'son_görüntülenme_tarihi' => 'datetime',
        'kategori' => ResimKategorisi::class,
        'dosya_boyutu' => 'integer',
        'genislik' => 'integer',
        'yukseklik' => 'integer',
        'görüntülenme_sayisi' => 'integer',
        'siralama' => 'integer',
        'exif_data' => 'json',
        'etiketler' => 'json',
    ];

    protected $searchableFields = [
        'baslik',
        'aciklama',
        'dosya_adi',
        'orijinal_dosya_adi',
        'alt_text',
        'etiketler',
    ];

    protected $sortableFields = [
        'baslik',
        'cekim_tarihi',
        'dosya_boyutu',
        'görüntülenme_sayisi',
        'onay_tarihi',
        'olusturma_tarihi',
        'siralama',
    ];

    protected $defaultSortField = 'siralama';
    protected $defaultSortDirection = 'asc';

    /**
     * Polymorphic ilişki - Bu resim hangi modele ait
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Resmi yükleyen kullanıcı
     */
    public function yükleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'yükleyen_id');
    }

    /**
     * Resmi onaylayan kullanıcı
     */
    public function onaylayan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'onaylayan_id');
    }

    /**
     * İşlenmiş resimler scope
     */
    public function scopeIslenmis($query)
    {
        return $query->where('is_processed', true);
    }

    /**
     * Onaylanmış resimler scope
     */
    public function scopeOnaylanmis($query)
    {
        return $query->where('onay_durumu', 'onaylandı');
    }

    /**
     * Onay bekleyen resimler scope
     */
    public function scopeOnayBekleyen($query)
    {
        return $query->where('onay_durumu', 'beklemede');
    }

    /**
     * Reddedilmiş resimler scope
     */
    public function scopeReddedilmis($query)
    {
        return $query->where('onay_durumu', 'reddedildi');
    }

    /**
     * Kategoriye göre scope
     */
    public function scopeKategoriye($query, ResimKategorisi|string $category)
    {
        $value = $category instanceof ResimKategorisi ? $category->value : $category;
        return $query->where('kategori', $value);
    }

    /**
     * Galeri resimleri scope
     */
    public function scopeGaleri($query)
    {
        return $query->where('kategori', ResimKategorisi::GALERI);
    }

    /**
     * Profil resimleri scope (Avatar/Logo)
     */
    public function scopeProfil($query)
    {
        return $query->whereIn('kategori', [ResimKategorisi::AVATAR, ResimKategorisi::LOGO]);
    }

    /**
     * Harita resimleri scope
     */
    public function scopeHarita($query)
    {
        return $query->whereIn('kategori', [
            ResimKategorisi::UYDU,
            ResimKategorisi::OZNITELIK,
            ResimKategorisi::BUYUKSEHIR,
            ResimKategorisi::EGIM,
            ResimKategorisi::EIMAR
        ]);
    }

    /**
     * Mülk tipine göre uygun kategorilerdeki resimler
     */
    public function scopeMulkTipine($query, string $propertyType)
    {
        $categories = ResimKategorisi::forPropertyType($propertyType);
        $categoryValues = array_map(fn($cat) => $cat->value, $categories);
        
        return $query->whereIn('kategori', $categoryValues);
    }

    /**
     * Boyut aralığına göre scope
     */
    public function scopeBoyutAraliginda($query, ?int $minWidth = null, ?int $maxWidth = null, ?int $minHeight = null, ?int $maxHeight = null)
    {
        if ($minWidth) {
            $query->where('genislik', '>=', $minWidth);
        }
        if ($maxWidth) {
            $query->where('genislik', '<=', $maxWidth);
        }
        if ($minHeight) {
            $query->where('yukseklik', '>=', $minHeight);
        }
        if ($maxHeight) {
            $query->where('yukseklik', '<=', $maxHeight);
        }
        
        return $query;
    }

    /**
     * Dosya boyutuna göre scope
     */
    public function scopeDosyaBoyutuna($query, ?int $minSize = null, ?int $maxSize = null)
    {
        if ($minSize) {
            $query->where('dosya_boyutu', '>=', $minSize);
        }
        if ($maxSize) {
            $query->where('dosya_boyutu', '<=', $maxSize);
        }
        
        return $query;
    }

    /**
     * Tarih aralığına göre scope
     */
    public function scopeTarihAraliginda($query, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        if ($startDate) {
            $query->where('cekim_tarihi', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('cekim_tarihi', '<=', $endDate);
        }
        
        return $query;
    }

    /**
     * Etiket bazında scope
     */
    public function scopeEtiketli($query, string $tag)
    {
        return $query->whereJsonContains('etiketler', $tag);
    }

    /**
     * Yükleyen kullanıcıya göre scope
     */
    public function scopeYukleyen($query, int $userId)
    {
        return $query->where('yükleyen_id', $userId);
    }

    /**
     * Popüler resimler scope (çok görüntülenen)
     */
    public function scopePopuler($query, int $minViews = 100)
    {
        return $query->where('görüntülenme_sayisi', '>=', $minViews);
    }

    /**
     * Son yüklenen resimler scope
     */
    public function scopeSonYuklenen($query, int $days = 7)
    {
        return $query->where('olusturma_tarihi', '>=', now()->subDays($days));
    }

    /**
     * Yüksek kaliteli resimler scope
     */
    public function scopeYuksekKalite($query, int $minScore = 70)
    {
        return $query->whereRaw('
            (CASE 
                WHEN genislik * yukseklik >= 12000000 THEN 40
                WHEN genislik * yukseklik >= 8000000 THEN 30
                WHEN genislik * yukseklik >= 5000000 THEN 20
                WHEN genislik * yukseklik >= 2000000 THEN 10
                ELSE 0
            END) +
            (CASE 
                WHEN dosya_boyutu BETWEEN 1048576 AND 10485760 THEN 30
                WHEN dosya_boyutu BETWEEN 524288 AND 15728640 THEN 20
                WHEN dosya_boyutu >= 104857 THEN 10
                ELSE 0
            END) +
            (CASE WHEN is_processed = 1 THEN 20 ELSE 0 END) +
            (CASE WHEN onay_durumu = "onaylandı" THEN 10 ELSE 0 END) >= ?
        ', [$minScore]);
    }

    /**
     * Büyük boyutlu resimler scope
     */
    public function scopeBuyukBoyut($query)
    {
        return $query->where('dosya_boyutu', '>', 5242880); // 5MB'dan büyük
    }

    /**
     * Küçük boyutlu resimler scope
     */
    public function scopeKucukBoyut($query)
    {
        return $query->where('dosya_boyutu', '<', 1048576); // 1MB'dan küçük
    }

    /**
     * Yatay resimler scope
     */
    public function scopeYatay($query)
    {
        return $query->whereRaw('genislik > yukseklik');
    }

    /**
     * Dikey resimler scope
     */
    public function scopeDikey($query)
    {
        return $query->whereRaw('yukseklik > genislik');
    }

    /**
     * Kare resimler scope
     */
    public function scopeKare($query)
    {
        return $query->whereRaw('genislik = yukseklik');
    }

    /**
     * Hatalı işleme scope
     */
    public function scopeHatali($query)
    {
        return $query->whereNotNull('processing_error');
    }

    /**
     * Kategori etiketi
     */
    public function getKategoriLabelAttribute(): string
    {
        return $this->kategori?->label() ?? 'Bilinmiyor';
    }

    /**
     * Kategori açıklaması
     */
    public function getKategoriDescriptionAttribute(): string
    {
        return $this->kategori?->description() ?? '';
    }

    /**
     * Kategori rengi
     */
    public function getKategoriColorAttribute(): string
    {
        return $this->kategori?->color() ?? 'gray';
    }

    /**
     * Dosya boyutunu human readable format'ta döndür
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->dosya_boyutu;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Resim boyutlarını string olarak döndür
     */
    public function getDimensionsAttribute(): string
    {
        if ($this->genislik && $this->yukseklik) {
            return $this->genislik . 'x' . $this->yukseklik;
        }
        return 'Bilinmiyor';
    }

    /**
     * Aspect ratio hesapla
     */
    public function getAspectRatioAttribute(): ?float
    {
        if ($this->genislik && $this->yukseklik) {
            return round($this->genislik / $this->yukseklik, 2);
        }
        return null;
    }

    /**
     * Resim orientasyonu
     */
    public function getOrientationAttribute(): string
    {
        if (!$this->genislik || !$this->yukseklik) {
            return 'bilinmiyor';
        }

        if ($this->genislik > $this->yukseklik) {
            return 'yatay';
        } elseif ($this->yukseklik > $this->genislik) {
            return 'dikey';
        } else {
            return 'kare';
        }
    }

    /**
     * Megapiksel hesapla
     */
    public function getMegapixelsAttribute(): ?float
    {
        if ($this->genislik && $this->yukseklik) {
            return round(($this->genislik * $this->yukseklik) / 1000000, 1);
        }
        return null;
    }

    /**
     * Thumbnail URL'sini döndür
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->attributes['thumbnail_url']) {
            return $this->attributes['thumbnail_url'];
        }

        // Otomatik thumbnail oluştur
        return $this->generateThumbnailUrl();
    }

    /**
     * Medium boyut URL'sini döndür
     */
    public function getMediumUrlAttribute(): string
    {
        if ($this->attributes['medium_url']) {
            return $this->attributes['medium_url'];
        }

        return $this->generateMediumUrl();
    }

    /**
     * Large boyut URL'sini döndür
     */
    public function getLargeUrlAttribute(): string
    {
        if ($this->attributes['large_url']) {
            return $this->attributes['large_url'];
        }

        return $this->generateLargeUrl();
    }

    /**
     * Onay durumu etiketi
     */
    public function getOnayDurumuLabelAttribute(): string
    {
        return match ($this->onay_durumu) {
            'onaylandı' => 'Onaylandı',
            'reddedildi' => 'Reddedildi',
            'beklemede' => 'Onay Bekliyor',
            default => 'Belirsiz'
        };
    }

    /**
     * Onay durumu rengi
     */
    public function getOnayDurumuColorAttribute(): string
    {
        return match ($this->onay_durumu) {
            'onaylandı' => 'green',
            'reddedildi' => 'red',
            'beklemede' => 'yellow',
            default => 'gray'
        };
    }

    /**
     * İşlem durumu etiketi
     */
    public function getProcessingStatusAttribute(): string
    {
        if ($this->processing_error) {
            return 'Hata: ' . $this->processing_error;
        }

        return $this->is_processed ? 'İşlendi' : 'İşleniyor';
    }

    /**
     * Dosya uzantısı
     */
    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->dosya_adi, PATHINFO_EXTENSION));
    }

    /**
     * EXIF verilerinden kamera bilgisi
     */
    public function getCameraInfoAttribute(): ?string
    {
        if (!$this->exif_data) {
            return null;
        }

        $make = $this->exif_data['Make'] ?? null;
        $model = $this->exif_data['Model'] ?? null;

        if ($make && $model) {
            return $make . ' ' . $model;
        }

        return $make ?: $model;
    }

    /**
     * EXIF verilerinden çekim ayarları
     */
    public function getShootingSettingsAttribute(): array
    {
        if (!$this->exif_data) {
            return [];
        }

        return [
            'iso' => $this->exif_data['ISOSpeedRatings'] ?? null,
            'aperture' => $this->exif_data['FNumber'] ?? null,
            'shutter_speed' => $this->exif_data['ExposureTime'] ?? null,
            'focal_length' => $this->exif_data['FocalLength'] ?? null,
            'flash' => $this->exif_data['Flash'] ?? null,
        ];
    }

    /**
     * GPS koordinatları
     */
    public function getGpsCoordinatesAttribute(): ?array
    {
        if (!$this->exif_data) {
            return null;
        }

        $lat = $this->exif_data['GPSLatitude'] ?? null;
        $lon = $this->exif_data['GPSLongitude'] ?? null;

        if ($lat && $lon) {
            return [
                'latitude' => $lat,
                'longitude' => $lon,
            ];
        }

        return null;
    }

    /**
     * Etiket sayısı
     */
    public function getEtiketSayisiAttribute(): int
    {
        return is_array($this->etiketler) ? count($this->etiketler) : 0;
    }

    /**
     * Son görüntülenmeden bu yana geçen süre
     */
    public function getSonGoruntulemeSuresiAttribute(): ?string
    {
        if (!$this->son_görüntülenme_tarihi) {
            return 'Hiç görüntülenmemiş';
        }

        return $this->son_görüntülenme_tarihi->diffForHumans();
    }

    /**
     * Popülerlik skoru (görüntülenme + yaş bazlı)
     */
    public function getPopularityScoruAttribute(): float
    {
        $views = $this->görüntülenme_sayisi ?? 0;
        $ageInDays = $this->olusturma_tarihi->diffInDays(now());
        
        // Yaş faktörü (yeni resimler daha yüksek skor alır)
        $ageFactor = max(1, 365 - $ageInDays) / 365;
        
        return round($views * $ageFactor, 2);
    }

    /**
     * Kalite skoru (boyut, çözünürlük bazlı)
     */
    public function getQualityScoruAttribute(): int
    {
        $score = 0;
        
        // Çözünürlük skoru
        if ($this->megapixels) {
            if ($this->megapixels >= 12) $score += 40;
            elseif ($this->megapixels >= 8) $score += 30;
            elseif ($this->megapixels >= 5) $score += 20;
            elseif ($this->megapixels >= 2) $score += 10;
        }
        
        // Dosya boyutu skoru (çok küçük veya çok büyük değil)
        $sizeInMB = $this->dosya_boyutu / 1048576;
        if ($sizeInMB >= 1 && $sizeInMB <= 10) $score += 30;
        elseif ($sizeInMB >= 0.5 && $sizeInMB <= 15) $score += 20;
        elseif ($sizeInMB >= 0.1) $score += 10;
        
        // İşlenme durumu skoru
        if ($this->is_processed) $score += 20;
        
        // Onay durumu skoru
        if ($this->onay_durumu === 'onaylandı') $score += 10;
        
        return min($score, 100);
    }

    /**
     * Display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->baslik ?: $this->orijinal_dosya_adi ?: $this->dosya_adi ?: 'İsimsiz Resim';
    }
}

    /**
     * Thumbnail URL oluştur
     */
    protected function generateThumbnailUrl(): string
    {
        $path = parse_url($this->url, PHP_URL_PATH);
        $pathInfo = pathinfo($path);
        
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
    }

    /**
     * Medium URL oluştur
     */
    protected function generateMediumUrl(): string
    {
        $path = parse_url($this->url, PHP_URL_PATH);
        $pathInfo = pathinfo($path);
        
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_medium.' . $pathInfo['extension'];
    }

    /**
     * Large URL oluştur
     */
    protected function generateLargeUrl(): string
    {
        $path = parse_url($this->url, PHP_URL_PATH);
        $pathInfo = pathinfo($path);
        
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_large.' . $pathInfo['extension'];
    }

    /**
     * Resim boyutlarını güncelle
     */
    public function updateDimensions(): bool
    {
        if (!Storage::exists($this->url)) {
            return false;
        }

        try {
            $image = Image::make(Storage::path($this->url));
            
            $this->update([
                'genislik' => $image->width(),
                'yukseklik' => $image->height(),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->update(['processing_error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * EXIF verilerini çıkar ve kaydet
     */
    public function extractExifData(): bool
    {
        if (!Storage::exists($this->url)) {
            return false;
        }

        try {
            $filePath = Storage::path($this->url);
            $exifData = @exif_read_data($filePath);

            if ($exifData) {
                // Çekim tarihi EXIF'ten al
                $dateTime = null;
                if (isset($exifData['DateTime'])) {
                    $dateTime = Carbon::createFromFormat('Y:m:d H:i:s', $exifData['DateTime']);
                } elseif (isset($exifData['DateTimeOriginal'])) {
                    $dateTime = Carbon::createFromFormat('Y:m:d H:i:s', $exifData['DateTimeOriginal']);
                }

                $updateData = ['exif_data' => $exifData];
                
                if ($dateTime && !$this->cekim_tarihi) {
                    $updateData['cekim_tarihi'] = $dateTime;
                }

                $this->update($updateData);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->update(['processing_error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Resim hash'ini hesapla (duplicate detection için)
     */
    public function calculateHash(): bool
    {
        if (!Storage::exists($this->url)) {
            return false;
        }

        try {
            $filePath = Storage::path($this->url);
            $hash = hash_file('md5', $filePath);
            
            $this->update(['hash' => $hash]);
            return true;
        } catch (\Exception $e) {
            $this->update(['processing_error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Farklı boyutlarda resim oluştur
     */
    public function generateVariants(): bool
    {
        if (!Storage::exists($this->url)) {
            return false;
        }

        try {
            $image = Image::make(Storage::path($this->url));
            $pathInfo = pathinfo($this->url);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $extension = $pathInfo['extension'];

            // Thumbnail (150x150)
            $thumbnailPath = $directory . '/' . $filename . '_thumb.' . $extension;
            $thumbnail = clone $image;
            $thumbnail->fit(150, 150);
            Storage::put($thumbnailPath, $thumbnail->encode());

            // Medium (800x600)
            $mediumPath = $directory . '/' . $filename . '_medium.' . $extension;
            $medium = clone $image;
            $medium->resize(800, 600, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::put($mediumPath, $medium->encode());

            // Large (1920x1080)
            $largePath = $directory . '/' . $filename . '_large.' . $extension;
            $large = clone $image;
            $large->resize(1920, 1080, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::put($largePath, $large->encode());

            $this->update([
                'thumbnail_url' => $thumbnailPath,
                'medium_url' => $mediumPath,
                'large_url' => $largePath,
                'is_processed' => true,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->update(['processing_error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Resmi işle (boyutlar, EXIF, hash, variants)
     */
    public function processImage(): bool
    {
        $success = true;

        $success &= $this->updateDimensions();
        $success &= $this->extractExifData();
        $success &= $this->calculateHash();
        
        // Kategori bazlı işleme kuralları
        if ($this->kategori) {
            $success &= $this->applyProcessingRules();
        }

        $success &= $this->generateVariants();

        if ($success) {
            $this->update(['is_processed' => true, 'processing_error' => null]);
        }

        return $success;
    }

    /**
     * Kategori bazlı işleme kuralları uygula
     */
    protected function applyProcessingRules(): bool
    {
        try {
            switch ($this->kategori) {
                case ResimKategorisi::AVATAR:
                    return $this->processAvatar();
                
                case ResimKategorisi::LOGO:
                    return $this->processLogo();
                
                case ResimKategorisi::GALERI:
                    return $this->processGalleryImage();
                
                case ResimKategorisi::UYDU:
                case ResimKategorisi::OZNITELIK:
                case ResimKategorisi::BUYUKSEHIR:
                case ResimKategorisi::EGIM:
                case ResimKategorisi::EIMAR:
                    return $this->processMapImage();
                
                default:
                    return true;
            }
        } catch (\Exception $e) {
            $this->update(['processing_error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Avatar resmi işle
     */
    protected function processAvatar(): bool
    {
        if (!Storage::exists($this->url)) {
            return false;
        }

        $image = Image::make(Storage::path($this->url));
        
        // Avatar için kare format ve 300x300 boyut
        $image->fit(300, 300);
        
        // Kalite optimizasyonu
        $image->encode('jpg', 85);
        
        Storage::put($this->url, $image->getEncoded());
        
        return true;
    }

    /**
     * Logo resmi işle
     */
    protected function processLogo(): bool
    {
        if (!Storage::exists($this->url)) {
            return false;
        }

        $image = Image::make(Storage::path($this->url));
        
        // Logo için maksimum 500x200 boyut, aspect ratio koru
        $image->resize(500, 200, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        Storage::put($this->url, $image->encode());
        
        return true;
    }

    /**
     * Galeri resmi işle
     */
    protected function processGalleryImage(): bool
    {
        if (!Storage::exists($this->url)) {
            return false;
        }

        $image = Image::make(Storage::path($this->url));
        
        // Galeri için maksimum 2048x2048 boyut
        $image->resize(2048, 2048, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // Kalite optimizasyonu
        $image->encode('jpg', 90);
        
        Storage::put($this->url, $image->getEncoded());
        
        return true;
    }

    /**
     * Harita resmi işle
     */
    protected function processMapImage(): bool
    {
        // Harita resimleri için özel işleme kuralları
        // Genellikle orijinal boyutları korunur
        return true;
    }

    /**
     * Görüntülenme sayısını artır
     */
    public function incrementViews(): void
    {
        $this->increment('görüntülenme_sayisi');
        $this->update(['son_görüntülenme_tarihi' => now()]);
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
     * Resmi onayla
     */
    public function approve(int $userId): void
    {
        $this->update([
            'onay_durumu' => 'onaylandı',
            'onaylayan_id' => $userId,
            'onay_tarihi' => now(),
        ]);
    }

    /**
     * Resmi reddet
     */
    public function reject(int $userId, ?string $reason = null): void
    {
        $this->update([
            'onay_durumu' => 'reddedildi',
            'onaylayan_id' => $userId,
            'onay_tarihi' => now(),
            'processing_error' => $reason,
        ]);
    }

    /**
     * Duplicate resim kontrolü
     */
    public static function findDuplicates(string $hash): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('hash', $hash)->get();
    }

    /**
     * Kategori için maksimum dosya boyutu kontrolü
     */
    public function isWithinSizeLimit(): bool
    {
        if (!$this->kategori) {
            return true;
        }

        $maxSizeInBytes = $this->kategori->maxFileSize() * 1048576; // MB to bytes
        return $this->dosya_boyutu <= $maxSizeInBytes;
    }

    /**
     * MIME type kontrolü
     */
    public function hasValidMimeType(): bool
    {
        if (!$this->kategori) {
            return true;
        }

        return in_array($this->mime_type, $this->kategori->allowedMimeTypes());
    }

    /**
     * Resim için uygun mülk tipi kontrolü
     */
    public function isValidForPropertyType(string $propertyType): bool
    {
        if (!$this->kategori) {
            return true;
        }

        return in_array($propertyType, $this->kategori->applicableToPropertyTypes());
    }

    /**
     * Validation kuralları
     */
    public static function getValidationRules(): array
    {
        $categories = array_map(fn($case) => $case->value, ResimKategorisi::cases());

        return [
            'imageable_id' => 'required|string',
            'imageable_type' => 'required|string|max:255',
            'kategori' => 'required|in:' . implode(',', $categories),
            'baslik' => 'nullable|string|max:255',
            'aciklama' => 'nullable|string|max:1000',
            'cekim_tarihi' => 'nullable|date',
            'alt_text' => 'nullable|string|max:255',
            'copyright_bilgisi' => 'nullable|string|max:255',
            'etiketler' => 'nullable|array',
            'etiketler.*' => 'string|max:50',
            'aktif_mi' => 'boolean',
            'siralama' => 'integer|min:0',
        ];
    }

    /**
     * Resim özeti
     */
    public function getImageSummary(): array
    {
        return [
            'id' => $this->id,
            'baslik' => $this->display_name,
            'kategori' => $this->kategori_label,
            'boyutlar' => $this->dimensions,
            'dosya_boyutu' => $this->formatted_size,
            'megapiksel' => $this->megapixels,
            'orientation' => $this->orientation,
            'onay_durumu' => $this->onay_durumu_label,
            'görüntülenme' => $this->görüntülenme_sayisi,
            'kalite_skoru' => $this->quality_skoru,
            'popülerlik_skoru' => $this->popularity_skoru,
            'etiket_sayisi' => $this->etiket_sayisi,
            'işlenme_durumu' => $this->processing_status,
        ];
    }
}