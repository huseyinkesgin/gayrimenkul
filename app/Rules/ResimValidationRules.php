<?php

namespace App\Rules;

use App\Enums\ResimKategorisi;
use Illuminate\Contracts\Validation\Rule;

class ResimValidationRules implements Rule
{
    private ResimKategorisi $kategori;
    private string $attribute;

    public function __construct(ResimKategorisi $kategori)
    {
        $this->kategori = $kategori;
    }

    /**
     * Validation kuralını kontrol et
     */
    public function passes($attribute, $value)
    {
        $this->attribute = $attribute;

        if (!$value || !$value->isValid()) {
            return false;
        }

        // MIME type kontrolü
        if (!in_array($value->getMimeType(), $this->kategori->allowedMimeTypes())) {
            return false;
        }

        // Dosya boyutu kontrolü
        $maxSize = $this->kategori->maxFileSize() * 1024 * 1024; // MB to bytes
        if ($value->getSize() > $maxSize) {
            return false;
        }

        // Resim boyut kontrolü
        try {
            $imageInfo = getimagesize($value->getRealPath());
            if (!$imageInfo) {
                return false;
            }

            $minDimensions = $this->getMinimumDimensions();
            if ($imageInfo[0] < $minDimensions['width'] || $imageInfo[1] < $minDimensions['height']) {
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Hata mesajını döndür
     */
    public function message()
    {
        $allowedTypes = implode(', ', $this->kategori->allowedMimeTypes());
        $maxSize = $this->kategori->maxFileSize();
        $minDimensions = $this->getMinimumDimensions();
        
        return "Resim {$this->kategori->label()} kategorisi için uygun değil. " .
               "İzin verilen formatlar: {$allowedTypes}. " .
               "Maksimum boyut: {$maxSize}MB. " .
               "Minimum çözünürlük: {$minDimensions['width']}x{$minDimensions['height']}";
    }

    /**
     * Minimum boyutları al
     */
    private function getMinimumDimensions(): array
    {
        return match ($this->kategori) {
            ResimKategorisi::AVATAR => ['width' => 100, 'height' => 100],
            ResimKategorisi::LOGO => ['width' => 100, 'height' => 50],
            ResimKategorisi::KAPAK_RESMI => ['width' => 800, 'height' => 450],
            ResimKategorisi::GALERI, ResimKategorisi::IC_MEKAN, ResimKategorisi::DIS_MEKAN => ['width' => 640, 'height' => 480],
            default => ['width' => 200, 'height' => 200],
        };
    }

    /**
     * Genel resim validation kuralları
     */
    public static function getGeneralRules(): array
    {
        return [
            'baslik' => 'nullable|string|max:255',
            'aciklama' => 'nullable|string|max:1000',
            'cekim_tarihi' => 'nullable|date',
            'alt_text' => 'nullable|string|max:255',
            'copyright_bilgisi' => 'nullable|string|max:255',
            'etiketler' => 'nullable|array',
            'etiketler.*' => 'string|max:50',
            'siralama' => 'integer|min:0',
        ];
    }

    /**
     * Kategori bazında validation kuralları
     */
    public static function getRulesForCategory(ResimKategorisi $kategori): array
    {
        $baseRules = self::getGeneralRules();
        
        $baseRules['file'] = [
            'required',
            'file',
            'image',
            new self($kategori)
        ];

        // Kategori bazında özel kurallar
        switch ($kategori) {
            case ResimKategorisi::AVATAR:
                $baseRules['baslik'] = 'nullable|string|max:100';
                break;
                
            case ResimKategorisi::LOGO:
                $baseRules['baslik'] = 'required|string|max:255';
                break;
                
            case ResimKategorisi::KAPAK_RESMI:
                $baseRules['baslik'] = 'required|string|max:255';
                $baseRules['alt_text'] = 'required|string|max:255';
                break;

            case ResimKategorisi::GALERI:
            case ResimKategorisi::IC_MEKAN:
            case ResimKategorisi::DIS_MEKAN:
            case ResimKategorisi::DETAY:
            case ResimKategorisi::CEPHE:
            case ResimKategorisi::MANZARA:
                $baseRules['alt_text'] = 'required|string|max:255';
                break;
        }

        return $baseRules;
    }

    /**
     * Toplu yükleme için validation kuralları
     */
    public static function getBulkUploadRules(ResimKategorisi $kategori): array
    {
        return [
            'files' => 'required|array|min:1|max:20',
            'files.*' => [
                'required',
                'file',
                'image',
                new self($kategori)
            ],
            'baslik' => 'nullable|string|max:255',
            'aciklama' => 'nullable|string|max:1000',
            'etiketler' => 'nullable|array',
            'etiketler.*' => 'string|max:50',
        ];
    }
}

/**
 * Özel resim kategorisi validation kuralları
 */
class AvatarValidationRule extends ResimValidationRules
{
    public function __construct()
    {
        parent::__construct(ResimKategorisi::AVATAR);
    }

    public function passes($attribute, $value)
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        // Avatar için özel kontroller
        try {
            $imageInfo = getimagesize($value->getRealPath());
            
            // Kare format kontrolü (tolerans %10)
            $aspectRatio = $imageInfo[0] / $imageInfo[1];
            if ($aspectRatio < 0.9 || $aspectRatio > 1.1) {
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'Avatar resmi kare format (1:1) olmalıdır ve minimum 100x100 boyutunda olmalıdır.';
    }
}

class LogoValidationRule extends ResimValidationRules
{
    public function __construct()
    {
        parent::__construct(ResimKategorisi::LOGO);
    }

    public function passes($attribute, $value)
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        // Logo için özel kontroller
        try {
            $imageInfo = getimagesize($value->getRealPath());
            
            // Aspect ratio kontrolü (yatay olmalı)
            $aspectRatio = $imageInfo[0] / $imageInfo[1];
            if ($aspectRatio < 1.5) { // En az 3:2 oranında yatay
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'Logo resmi yatay format olmalıdır (minimum 3:2 oranı) ve minimum 100x50 boyutunda olmalıdır.';
    }
}

class KapakResmiValidationRule extends ResimValidationRules
{
    public function __construct()
    {
        parent::__construct(ResimKategorisi::KAPAK_RESMI);
    }

    public function passes($attribute, $value)
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        // Kapak resmi için özel kontroller
        try {
            $imageInfo = getimagesize($value->getRealPath());
            
            // 16:9 aspect ratio kontrolü (tolerans %5)
            $aspectRatio = $imageInfo[0] / $imageInfo[1];
            $targetRatio = 16 / 9;
            $tolerance = 0.05;
            
            if (abs($aspectRatio - $targetRatio) > $tolerance) {
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'Kapak resmi 16:9 aspect ratio\'da olmalıdır ve minimum 800x450 boyutunda olmalıdır.';
    }
}

class GaleriValidationRule extends ResimValidationRules
{
    public function __construct()
    {
        parent::__construct(ResimKategorisi::GALERI);
    }

    public function passes($attribute, $value)
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        // Galeri için özel kontroller
        try {
            $imageInfo = getimagesize($value->getRealPath());
            
            // Çok dar veya çok geniş resimler kabul edilmez
            $aspectRatio = $imageInfo[0] / $imageInfo[1];
            if ($aspectRatio < 0.5 || $aspectRatio > 3.0) {
                return false;
            }

            // Minimum kalite kontrolü (dosya boyutu / piksel sayısı)
            $pixelCount = $imageInfo[0] * $imageInfo[1];
            $bytesPerPixel = $value->getSize() / $pixelCount;
            
            if ($bytesPerPixel < 0.5) { // Çok sıkıştırılmış
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'Galeri resmi uygun aspect ratio\'da (0.5-3.0 arası) ve yeterli kalitede olmalıdır.';
    }
}

class PlanValidationRule extends ResimValidationRules
{
    public function __construct()
    {
        parent::__construct(ResimKategorisi::PLAN);
    }

    public function passes($attribute, $value)
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        // Plan için özel kontroller
        try {
            $imageInfo = getimagesize($value->getRealPath());
            
            // Yüksek çözünürlük gerekli
            if ($imageInfo[0] < 1024 || $imageInfo[1] < 768) {
                return false;
            }

            // Dosya boyutu kontrolü (çok küçük olmamalı - detay kaybı)
            $pixelCount = $imageInfo[0] * $imageInfo[1];
            $bytesPerPixel = $value->getSize() / $pixelCount;
            
            if ($bytesPerPixel < 1.0) { // Plan için daha yüksek kalite gerekli
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'Plan resmi minimum 1024x768 çözünürlüğünde ve yüksek kalitede olmalıdır.';
    }
}

class HaritaValidationRule extends ResimValidationRules
{
    private ResimKategorisi $haritaKategorisi;

    public function __construct(ResimKategorisi $haritaKategorisi)
    {
        parent::__construct($haritaKategorisi);
        $this->haritaKategorisi = $haritaKategorisi;
    }

    public function passes($attribute, $value)
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        // Harita kategorisi kontrolü
        if (!$this->haritaKategorisi->isMapCategory()) {
            return false;
        }

        // Harita için özel kontroller
        try {
            $imageInfo = getimagesize($value->getRealPath());
            
            // Minimum çözünürlük (harita detayları için)
            if ($imageInfo[0] < 512 || $imageInfo[1] < 512) {
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'Harita resmi minimum 512x512 çözünürlüğünde olmalıdır.';
    }
}