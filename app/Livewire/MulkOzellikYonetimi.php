<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Mulk\BaseMulk;
use App\Models\MulkOzellik;
use App\Helpers\DynamicFormHelper;
use App\Services\MulkOzellikTanimlariService;

class MulkOzellikYonetimi extends Component
{
    public $mulk;
    public $mulkType;
    public $properties = [];
    public $activeGroup = null;
    public $showValidationErrors = false;
    public $validationErrors = [];

    protected $listeners = [
        'propertyUpdated' => 'handlePropertyUpdate',
        'saveProperties' => 'saveAllProperties'
    ];

    public function mount(BaseMulk $mulk)
    {
        $this->mulk = $mulk;
        $this->mulkType = $mulk->getMulkType();
        $this->loadCurrentProperties();
        $this->setDefaultActiveGroup();
    }

    /**
     * Mevcut özellikleri yükle
     */
    public function loadCurrentProperties()
    {
        $currentProperties = $this->mulk->getPropertiesArray();
        
        foreach ($currentProperties as $key => $property) {
            $this->properties[$key] = $property['value'];
        }
    }

    /**
     * Varsayılan aktif grubu ayarla
     */
    public function setDefaultActiveGroup()
    {
        $groups = MulkOzellikTanimlariService::getOzellikGruplari($this->mulkType);
        $this->activeGroup = $groups[0] ?? null;
    }

    /**
     * Aktif grubu değiştir
     */
    public function setActiveGroup($group)
    {
        $this->activeGroup = $group;
    }

    /**
     * Özellik güncelleme
     */
    public function updatedProperties($value, $key)
    {
        $this->validateProperty($key, $value);
        $this->emit('propertyUpdated', $key, $value);
    }

    /**
     * Tek bir özelliği validate et
     */
    public function validateProperty($key, $value)
    {
        $ozellikTanimlari = MulkOzellikTanimlariService::getOzellikTanimlari($this->mulkType);
        
        if (!isset($ozellikTanimlari[$key])) {
            return;
        }

        $field = DynamicFormHelper::createFormField($key, $ozellikTanimlari[$key], $value);
        $errors = DynamicFormHelper::validateFieldValue($field, $value);

        if (empty($errors)) {
            unset($this->validationErrors[$key]);
        } else {
            $this->validationErrors[$key] = $errors;
        }
    }

    /**
     * Tüm özellikleri validate et
     */
    public function validateAllProperties()
    {
        $this->validationErrors = DynamicFormHelper::validateFormData($this->mulkType, $this->properties);
        $this->showValidationErrors = !empty($this->validationErrors);
        
        return empty($this->validationErrors);
    }

    /**
     * Özellik kaydet
     */
    public function saveProperty($key)
    {
        if (!$this->validateProperty($key, $this->properties[$key] ?? null)) {
            return;
        }

        $value = $this->properties[$key] ?? null;
        
        if (empty($value)) {
            // Boş değer ise özelliği sil
            $this->mulk->removeProperty($key);
        } else {
            // Özellik tipini belirle
            $ozellikTanimlari = MulkOzellikTanimlariService::getOzellikTanimlari($this->mulkType);
            $ozellikTanimi = $ozellikTanimlari[$key] ?? null;
            
            if ($ozellikTanimi) {
                $type = $this->getPropertyType($ozellikTanimi['type']);
                $unit = $ozellikTanimi['unit'] ?? null;
                
                // Mevcut özelliği güncelle veya yeni oluştur
                if ($this->mulk->getProperty($key) !== null) {
                    $this->mulk->updateProperty($key, $value);
                } else {
                    $this->mulk->addProperty($key, $value, $type, $unit);
                }
            }
        }

        $this->emit('propertySaved', $key, $value);
        session()->flash('success', 'Özellik başarıyla kaydedildi.');
    }

    /**
     * Tüm özellikleri kaydet
     */
    public function saveAllProperties()
    {
        if (!$this->validateAllProperties()) {
            session()->flash('error', 'Lütfen hataları düzeltin.');
            return;
        }

        $savedCount = 0;
        $ozellikTanimlari = MulkOzellikTanimlariService::getOzellikTanimlari($this->mulkType);

        foreach ($this->properties as $key => $value) {
            if (isset($ozellikTanimlari[$key])) {
                $ozellikTanimi = $ozellikTanimlari[$key];
                $type = $this->getPropertyType($ozellikTanimi['type']);
                $unit = $ozellikTanimi['unit'] ?? null;

                if (empty($value)) {
                    $this->mulk->removeProperty($key);
                } else {
                    if ($this->mulk->getProperty($key) !== null) {
                        $this->mulk->updateProperty($key, $value);
                    } else {
                        $this->mulk->addProperty($key, $value, $type, $unit);
                    }
                    $savedCount++;
                }
            }
        }

        $this->emit('allPropertiesSaved', $savedCount);
        session()->flash('success', "{$savedCount} özellik başarıyla kaydedildi.");
    }

    /**
     * Form tipini MulkOzellik tipine çevir
     */
    private function getPropertyType($formType): string
    {
        return match ($formType) {
            'number' => 'sayi',
            'checkbox' => 'boolean',
            'select' => 'liste',
            default => 'metin'
        };
    }

    /**
     * Özelliği sil
     */
    public function removeProperty($key)
    {
        $this->mulk->removeProperty($key);
        unset($this->properties[$key]);
        unset($this->validationErrors[$key]);
        
        $this->emit('propertyRemoved', $key);
        session()->flash('success', 'Özellik başarıyla silindi.');
    }

    /**
     * Tüm özellikleri sıfırla
     */
    public function resetAllProperties()
    {
        $this->properties = [];
        $this->validationErrors = [];
        $this->showValidationErrors = false;
        
        // Veritabanından da sil
        $this->mulk->ozellikler()->delete();
        
        $this->emit('allPropertiesReset');
        session()->flash('success', 'Tüm özellikler sıfırlandı.');
    }

    /**
     * Özellikleri JSON olarak export et
     */
    public function exportProperties()
    {
        $properties = $this->mulk->getPropertiesArray();
        $filename = "mulk_ozellikleri_{$this->mulk->id}.json";
        
        return response()->streamDownload(function () use ($properties) {
            echo json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Özellikleri import et
     */
    public function importProperties($jsonData)
    {
        try {
            $importedProperties = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                session()->flash('error', 'Geçersiz JSON formatı.');
                return;
            }

            $importedCount = 0;
            $ozellikTanimlari = MulkOzellikTanimlariService::getOzellikTanimlari($this->mulkType);

            foreach ($importedProperties as $key => $propertyData) {
                if (isset($ozellikTanimlari[$key])) {
                    $value = $propertyData['value'] ?? $propertyData;
                    $this->properties[$key] = $value;
                    $importedCount++;
                }
            }

            session()->flash('success', "{$importedCount} özellik başarıyla import edildi.");
        } catch (\Exception $e) {
            session()->flash('error', 'Import işlemi sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Render
     */
    public function render()
    {
        $groupedFields = DynamicFormHelper::generateGroupedFormFields($this->mulkType, $this->properties);
        $groups = MulkOzellikTanimlariService::getOzellikGruplari($this->mulkType);

        return view('livewire.mulk-ozellik-yonetimi', [
            'groupedFields' => $groupedFields,
            'groups' => $groups,
            'currentGroup' => $this->activeGroup,
            'currentGroupFields' => $groupedFields[$this->activeGroup] ?? []
        ]);
    }
}