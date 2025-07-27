<?php

namespace App\Models;

use App\Enums\DokumanTipi;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dokuman extends BaseModel
{
    use HasFactory;
    
    protected $table = 'dokumanlar';

    protected $fillable = [
        'url',
        'documentable_id',
        'documentable_type',
        'dokuman_tipi',
        'baslik',
        'aciklama',
        'dosya_adi',
        'orijinal_dosya_adi',
        'dosya_boyutu',
        'mime_type',
        'dosya_uzantisi',
        'dosya_hash',
        'versiyon',
        'ana_dokuman_id',
        'gizli_mi',
        'erisim_izinleri',
        'metadata',
        'son_erisim_tarihi',
        'erisim_sayisi',
        'olusturan_id',
        'guncelleyen_id',
        'aktif_mi',
    ];

    protected $casts = [
        'aktif_mi' => 'boolean',
        'gizli_mi' => 'boolean',
        'dokuman_tipi' => DokumanTipi::class,
        'dosya_boyutu' => 'integer',
        'versiyon' => 'integer',
        'erisim_sayisi' => 'integer',
        'erisim_izinleri' => 'json',
        'metadata' => 'json',
        'son_erisim_tarihi' => 'datetime',
        'olusturma_tarihi' => 'datetime',
        'guncelleme_tarihi' => 'datetime',
        'silinme_tarihi' => 'datetime',
    ];

    /**
     * Polymorphic ilişki - Bu döküman hangi modele ait
     */
    public function documentable()
    {
        return $this->morphTo();
    }

    /**
     * Aktif dökümanlar scope
     */
    public function scopeActive($query)
    {
        return $query->where('aktif_mi', true);
    }

    /**
     * Döküman tipine göre scope
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('dokuman_tipi', $type);
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
     * Dosya uzantısını döndür
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->dosya_adi, PATHINFO_EXTENSION);
    }

    /**
     * Dosyanın görüntülenebilir olup olmadığını kontrol et
     */
    public function getIsViewableAttribute(): bool
    {
        $viewableTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        return in_array($this->mime_type, $viewableTypes);
    }

    /**
     * Ana döküman ilişkisi (versiyonlama için)
     */
    public function anaDokuman()
    {
        return $this->belongsTo(Dokuman::class, 'ana_dokuman_id');
    }

    /**
     * Alt versiyonlar ilişkisi
     */
    public function versiyonlar()
    {
        return $this->hasMany(Dokuman::class, 'ana_dokuman_id')->orderBy('versiyon', 'desc');
    }

    /**
     * Oluşturan kullanıcı ilişkisi
     */
    public function olusturan()
    {
        return $this->belongsTo(\App\Models\User::class, 'olusturan_id');
    }

    /**
     * Güncelleyen kullanıcı ilişkisi
     */
    public function guncelleyen()
    {
        return $this->belongsTo(\App\Models\User::class, 'guncelleyen_id');
    }

    /**
     * Gizli dökümanlar scope
     */
    public function scopeGizli($query)
    {
        return $query->where('gizli_mi', true);
    }

    /**
     * Herkese açık dökümanlar scope
     */
    public function scopeAcik($query)
    {
        return $query->where('gizli_mi', false);
    }

    /**
     * En son versiyon scope
     */
    public function scopeEnSonVersiyon($query)
    {
        return $query->whereNull('ana_dokuman_id')
                    ->orWhereRaw('versiyon = (SELECT MAX(versiyon) FROM dokumanlar d2 WHERE d2.ana_dokuman_id = dokumanlar.ana_dokuman_id OR d2.id = dokumanlar.ana_dokuman_id)');
    }

    /**
     * Belirli bir versiyonu getir
     */
    public function scopeVersiyon($query, int $versiyon)
    {
        return $query->where('versiyon', $versiyon);
    }

    /**
     * Dosya hash'ine göre arama
     */
    public function scopeByHash($query, string $hash)
    {
        return $query->where('dosya_hash', $hash);
    }

    /**
     * Full-text search
     */
    public function scopeSearch($query, string $term)
    {
        return $query->whereRaw("MATCH(baslik, aciklama, dosya_adi) AGAINST(? IN BOOLEAN MODE)", [$term]);
    }

    /**
     * Erişim sayısını artır
     */
    public function incrementAccess()
    {
        $this->increment('erisim_sayisi');
        $this->update(['son_erisim_tarihi' => now()]);
    }

    /**
     * Yeni versiyon oluştur
     */
    public function createNewVersion(array $data): self
    {
        $anaId = $this->ana_dokuman_id ?? $this->id;
        $sonVersiyon = static::where('ana_dokuman_id', $anaId)
                            ->orWhere('id', $anaId)
                            ->max('versiyon');

        $data['ana_dokuman_id'] = $anaId;
        $data['versiyon'] = $sonVersiyon + 1;
        $data['documentable_id'] = $this->documentable_id;
        $data['documentable_type'] = $this->documentable_type;
        $data['dokuman_tipi'] = $this->dokuman_tipi;

        return static::create($data);
    }

    /**
     * Dosya tipine göre upload kurallarını kontrol et
     */
    public function validateUploadRules(string $mimeType, int $fileSize): array
    {
        $errors = [];
        $dokumanTipi = $this->dokuman_tipi;

        // MIME type kontrolü
        if (!in_array($mimeType, $dokumanTipi->allowedMimeTypes())) {
            $errors[] = "Bu döküman tipi için {$mimeType} formatı desteklenmiyor.";
        }

        // Dosya boyutu kontrolü (MB to bytes)
        $maxSize = $dokumanTipi->maxFileSize() * 1024 * 1024;
        if ($fileSize > $maxSize) {
            $maxSizeMB = $dokumanTipi->maxFileSize();
            $errors[] = "Dosya boyutu {$maxSizeMB}MB'ı aşamaz.";
        }

        return $errors;
    }

    /**
     * Duplicate dosya kontrolü
     */
    public static function isDuplicate(string $hash, string $documentableType, string $documentableId): bool
    {
        return static::where('dosya_hash', $hash)
                    ->where('documentable_type', $documentableType)
                    ->where('documentable_id', $documentableId)
                    ->where('aktif_mi', true)
                    ->exists();
    }

    /**
     * Kullanıcının erişim yetkisi var mı kontrol et
     */
    public function hasAccess($userId): bool
    {
        // Gizli değilse herkese açık
        if (!$this->gizli_mi) {
            return true;
        }

        // Oluşturan kişi her zaman erişebilir
        if ($this->olusturan_id == $userId) {
            return true;
        }

        // Erişim izinleri kontrolü
        if ($this->erisim_izinleri) {
            return in_array($userId, $this->erisim_izinleri);
        }

        return false;
    }
}