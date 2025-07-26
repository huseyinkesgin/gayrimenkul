<?php

namespace App\Rules;

use App\Enums\DokumanTipi;
use Illuminate\Contracts\Validation\Rule;

class DokumanValidationRules implements Rule
{
    private DokumanTipi $dokumanTipi;
    private string $attribute;

    public function __construct(DokumanTipi $dokumanTipi)
    {
        $this->dokumanTipi = $dokumanTipi;
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
        if (!in_array($value->getMimeType(), $this->dokumanTipi->allowedMimeTypes())) {
            return false;
        }

        // Dosya boyutu kontrolü
        $maxSize = $this->dokumanTipi->maxFileSize() * 1024 * 1024; // MB to bytes
        if ($value->getSize() > $maxSize) {
            return false;
        }

        return true;
    }

    /**
     * Hata mesajını döndür
     */
    public function message()
    {
        $allowedTypes = implode(', ', $this->dokumanTipi->allowedMimeTypes());
        $maxSize = $this->dokumanTipi->maxFileSize();
        
        return "Dosya {$this->dokumanTipi->label()} için uygun değil. İzin verilen formatlar: {$allowedTypes}. Maksimum boyut: {$maxSize}MB";
    }

    /**
     * Genel döküman validation kuralları
     */
    public static function getGeneralRules(): array
    {
        return [
            'baslik' => 'nullable|string|max:255',
            'aciklama' => 'nullable|string|max:1000',
            'gizli_mi' => 'boolean',
            'erisim_izinleri' => 'nullable|array',
            'erisim_izinleri.*' => 'uuid|exists:users,id'
        ];
    }

    /**
     * Döküman tipi bazında validation kuralları
     */
    public static function getRulesForType(DokumanTipi $type): array
    {
        $baseRules = self::getGeneralRules();
        
        $baseRules['file'] = [
            'required',
            'file',
            new self($type)
        ];

        // Tip bazında özel kurallar
        switch ($type) {
            case DokumanTipi::TAPU:
                $baseRules['baslik'] = 'required|string|max:255';
                break;
                
            case DokumanTipi::AUTOCAD:
                $baseRules['aciklama'] = 'required|string|max:1000';
                break;
                
            case DokumanTipi::RUHSAT:
                $baseRules['baslik'] = 'required|string|max:255';
                $baseRules['ruhsat_no'] = 'nullable|string|max:50';
                $baseRules['gecerlilik_tarihi'] = 'nullable|date|after:today';
                break;
        }

        return $baseRules;
    }

    /**
     * Toplu yükleme için validation kuralları
     */
    public static function getBulkUploadRules(DokumanTipi $type): array
    {
        return [
            'files' => 'required|array|min:1|max:10',
            'files.*' => [
                'required',
                'file',
                new self($type)
            ],
            'baslik' => 'nullable|string|max:255',
            'aciklama' => 'nullable|string|max:1000',
            'gizli_mi' => 'boolean',
        ];
    }

    /**
     * Versiyon güncelleme için validation kuralları
     */
    public static function getVersionUpdateRules(DokumanTipi $type): array
    {
        $rules = self::getRulesForType($type);
        $rules['versiyon_notu'] = 'nullable|string|max:500';
        
        return $rules;
    }
}

/**
 * Özel döküman tipi validation kuralları
 */
class TapuValidationRule extends DokumanValidationRules
{
    public function __construct()
    {
        parent::__construct(DokumanTipi::TAPU);
    }

    public function passes($attribute, $value)
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        // Tapu için özel kontroller
        // Örneğin: PDF olması zorunlu
        return $value->getMimeType() === 'application/pdf';
    }

    public function message()
    {
        return 'Tapu dökümanı PDF formatında olmalıdır.';
    }
}

class AutoCADValidationRule extends DokumanValidationRules
{
    public function __construct()
    {
        parent::__construct(DokumanTipi::AUTOCAD);
    }

    public function passes($attribute, $value)
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        // AutoCAD için özel kontroller
        $allowedExtensions = ['dwg', 'dxf', 'dwt'];
        $extension = strtolower($value->getClientOriginalExtension());
        
        return in_array($extension, $allowedExtensions);
    }

    public function message()
    {
        return 'AutoCAD dosyası .dwg, .dxf veya .dwt formatında olmalıdır.';
    }
}

class ProjeResmiValidationRule extends DokumanValidationRules
{
    public function __construct()
    {
        parent::__construct(DokumanTipi::PROJE_RESMI);
    }

    public function passes($attribute, $value)
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        // Proje resmi için özel kontroller
        if (str_starts_with($value->getMimeType(), 'image/')) {
            // Minimum çözünürlük kontrolü
            $imageInfo = getimagesize($value->getRealPath());
            if ($imageInfo && ($imageInfo[0] < 800 || $imageInfo[1] < 600)) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return 'Proje resmi minimum 800x600 çözünürlüğünde olmalıdır.';
    }
}