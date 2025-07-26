<?php

namespace App\Helpers;

use App\Services\MulkOzellikTanimlariService;

class DynamicFormHelper
{
    /**
     * Mülk tipi için form alanları oluştur
     */
    public static function generateFormFields(string $mulkType, array $currentValues = []): array
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari($mulkType);
        $formFields = [];

        foreach ($ozellikler as $key => $ozellik) {
            $formFields[$key] = self::createFormField($key, $ozellik, $currentValues[$key] ?? null);
        }

        return $formFields;
    }

    /**
     * Gruplara göre form alanları oluştur
     */
    public static function generateGroupedFormFields(string $mulkType, array $currentValues = []): array
    {
        $gruplar = MulkOzellikTanimlariService::getOzellikGruplari($mulkType);
        $groupedFields = [];

        foreach ($gruplar as $grup) {
            $ozellikler = MulkOzellikTanimlariService::getOzelliklerByGroup($mulkType, $grup);
            $groupedFields[$grup] = [];

            foreach ($ozellikler as $key => $ozellik) {
                $groupedFields[$grup][$key] = self::createFormField($key, $ozellik, $currentValues[$key] ?? null);
            }
        }

        return $groupedFields;
    }

    /**
     * Tek bir form alanı oluştur
     */
    private static function createFormField(string $key, array $ozellik, $currentValue = null): array
    {
        $field = [
            'name' => $key,
            'label' => $ozellik['label'],
            'type' => $ozellik['type'],
            'value' => $currentValue,
            'required' => $ozellik['required'] ?? false,
            'group' => $ozellik['group'] ?? 'Diğer',
            'help' => $ozellik['help'] ?? null,
            'attributes' => self::generateAttributes($ozellik),
            'validation' => self::generateValidationRules($ozellik),
        ];

        // Tip-spesifik özellikler ekle
        switch ($ozellik['type']) {
            case 'select':
                $field['options'] = $ozellik['options'] ?? [];
                break;
            case 'number':
                $field['min'] = $ozellik['min'] ?? null;
                $field['max'] = $ozellik['max'] ?? null;
                $field['step'] = $ozellik['step'] ?? null;
                $field['unit'] = $ozellik['unit'] ?? null;
                break;
            case 'text':
            case 'textarea':
                $field['maxlength'] = $ozellik['maxlength'] ?? null;
                break;
        }

        return $field;
    }

    /**
     * HTML attributes oluştur
     */
    private static function generateAttributes(array $ozellik): array
    {
        $attributes = [];

        switch ($ozellik['type']) {
            case 'number':
                if (isset($ozellik['min'])) $attributes['min'] = $ozellik['min'];
                if (isset($ozellik['max'])) $attributes['max'] = $ozellik['max'];
                if (isset($ozellik['step'])) $attributes['step'] = $ozellik['step'];
                break;
            case 'text':
            case 'textarea':
                if (isset($ozellik['maxlength'])) $attributes['maxlength'] = $ozellik['maxlength'];
                break;
        }

        if ($ozellik['required'] ?? false) {
            $attributes['required'] = true;
        }

        return $attributes;
    }

    /**
     * Validation kuralları oluştur
     */
    private static function generateValidationRules(array $ozellik): array
    {
        $rules = [];

        if ($ozellik['required'] ?? false) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($ozellik['type']) {
            case 'number':
                $rules[] = 'numeric';
                if (isset($ozellik['min'])) $rules[] = 'min:' . $ozellik['min'];
                if (isset($ozellik['max'])) $rules[] = 'max:' . $ozellik['max'];
                break;
            case 'text':
                $rules[] = 'string';
                if (isset($ozellik['maxlength'])) $rules[] = 'max:' . $ozellik['maxlength'];
                break;
            case 'textarea':
                $rules[] = 'string';
                if (isset($ozellik['maxlength'])) $rules[] = 'max:' . $ozellik['maxlength'];
                break;
            case 'select':
                if (isset($ozellik['options'])) {
                    $rules[] = 'in:' . implode(',', array_keys($ozellik['options']));
                }
                break;
            case 'checkbox':
                $rules[] = 'boolean';
                break;
        }

        return $rules;
    }

    /**
     * Livewire için form component oluştur
     */
    public static function generateLivewireComponent(string $mulkType): string
    {
        $groupedFields = self::generateGroupedFormFields($mulkType);
        $component = '';

        foreach ($groupedFields as $groupName => $fields) {
            $component .= self::generateGroupSection($groupName, $fields);
        }

        return $component;
    }

    /**
     * Grup section oluştur
     */
    private static function generateGroupSection(string $groupName, array $fields): string
    {
        $section = "<div class=\"form-group-section\">\n";
        $section .= "    <h3 class=\"form-group-title\">{$groupName}</h3>\n";
        $section .= "    <div class=\"form-group-fields\">\n";

        foreach ($fields as $field) {
            $section .= self::generateFieldHtml($field);
        }

        $section .= "    </div>\n";
        $section .= "</div>\n\n";

        return $section;
    }

    /**
     * Field HTML oluştur
     */
    private static function generateFieldHtml(array $field): string
    {
        $html = "        <div class=\"form-field\">\n";
        $html .= "            <label for=\"{$field['name']}\">{$field['label']}";
        
        if ($field['required']) {
            $html .= " <span class=\"required\">*</span>";
        }
        
        $html .= "</label>\n";

        switch ($field['type']) {
            case 'text':
                $html .= self::generateTextInput($field);
                break;
            case 'number':
                $html .= self::generateNumberInput($field);
                break;
            case 'textarea':
                $html .= self::generateTextarea($field);
                break;
            case 'select':
                $html .= self::generateSelect($field);
                break;
            case 'checkbox':
                $html .= self::generateCheckbox($field);
                break;
        }

        if ($field['help']) {
            $html .= "            <small class=\"form-help\">{$field['help']}</small>\n";
        }

        $html .= "        </div>\n";

        return $html;
    }

    /**
     * Text input oluştur
     */
    private static function generateTextInput(array $field): string
    {
        $attributes = self::attributesToString($field['attributes']);
        return "            <input type=\"text\" id=\"{$field['name']}\" name=\"{$field['name']}\" wire:model=\"properties.{$field['name']}\" {$attributes}>\n";
    }

    /**
     * Number input oluştur
     */
    private static function generateNumberInput(array $field): string
    {
        $attributes = self::attributesToString($field['attributes']);
        $html = "            <div class=\"number-input-group\">\n";
        $html .= "                <input type=\"number\" id=\"{$field['name']}\" name=\"{$field['name']}\" wire:model=\"properties.{$field['name']}\" {$attributes}>\n";
        
        if ($field['unit']) {
            $html .= "                <span class=\"input-unit\">{$field['unit']}</span>\n";
        }
        
        $html .= "            </div>\n";
        
        return $html;
    }

    /**
     * Textarea oluştur
     */
    private static function generateTextarea(array $field): string
    {
        $attributes = self::attributesToString($field['attributes']);
        return "            <textarea id=\"{$field['name']}\" name=\"{$field['name']}\" wire:model=\"properties.{$field['name']}\" {$attributes}></textarea>\n";
    }

    /**
     * Select oluştur
     */
    private static function generateSelect(array $field): string
    {
        $html = "            <select id=\"{$field['name']}\" name=\"{$field['name']}\" wire:model=\"properties.{$field['name']}\">\n";
        $html .= "                <option value=\"\">Seçiniz...</option>\n";
        
        foreach ($field['options'] as $value => $label) {
            $html .= "                <option value=\"{$value}\">{$label}</option>\n";
        }
        
        $html .= "            </select>\n";
        
        return $html;
    }

    /**
     * Checkbox oluştur
     */
    private static function generateCheckbox(array $field): string
    {
        return "            <input type=\"checkbox\" id=\"{$field['name']}\" name=\"{$field['name']}\" wire:model=\"properties.{$field['name']}\" value=\"1\">\n";
    }

    /**
     * Attributes'ları string'e çevir
     */
    private static function attributesToString(array $attributes): string
    {
        $attributeStrings = [];
        
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $attributeStrings[] = $key;
                }
            } else {
                $attributeStrings[] = "{$key}=\"{$value}\"";
            }
        }
        
        return implode(' ', $attributeStrings);
    }

    /**
     * JavaScript validation oluştur
     */
    public static function generateJavaScriptValidation(string $mulkType): string
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari($mulkType);
        $validationRules = [];

        foreach ($ozellikler as $key => $ozellik) {
            $field = self::createFormField($key, $ozellik);
            if (!empty($field['validation'])) {
                $validationRules[$key] = $field['validation'];
            }
        }

        return json_encode($validationRules, JSON_PRETTY_PRINT);
    }

    /**
     * CSS sınıfları oluştur
     */
    public static function generateCssClasses(array $field): string
    {
        $classes = ['form-control'];

        if ($field['required']) {
            $classes[] = 'required';
        }

        if ($field['type'] === 'number' && isset($field['unit'])) {
            $classes[] = 'has-unit';
        }

        return implode(' ', $classes);
    }

    /**
     * Form data'sını validate et
     */
    public static function validateFormData(string $mulkType, array $data): array
    {
        $ozellikler = MulkOzellikTanimlariService::getOzellikTanimlari($mulkType);
        $errors = [];

        foreach ($ozellikler as $key => $ozellik) {
            $field = self::createFormField($key, $ozellik);
            $value = $data[$key] ?? null;

            // Required kontrolü
            if ($field['required'] && empty($value)) {
                $errors[$key][] = "{$field['label']} alanı zorunludur.";
                continue;
            }

            // Tip-spesifik validasyonlar
            if (!empty($value)) {
                $fieldErrors = self::validateFieldValue($field, $value);
                if (!empty($fieldErrors)) {
                    $errors[$key] = $fieldErrors;
                }
            }
        }

        return $errors;
    }

    /**
     * Field değerini validate et
     */
    private static function validateFieldValue(array $field, $value): array
    {
        $errors = [];

        switch ($field['type']) {
            case 'number':
                if (!is_numeric($value)) {
                    $errors[] = "{$field['label']} sayısal bir değer olmalıdır.";
                } else {
                    if (isset($field['min']) && $value < $field['min']) {
                        $errors[] = "{$field['label']} en az {$field['min']} olmalıdır.";
                    }
                    if (isset($field['max']) && $value > $field['max']) {
                        $errors[] = "{$field['label']} en fazla {$field['max']} olmalıdır.";
                    }
                }
                break;
            case 'text':
            case 'textarea':
                if (isset($field['maxlength']) && strlen($value) > $field['maxlength']) {
                    $errors[] = "{$field['label']} en fazla {$field['maxlength']} karakter olabilir.";
                }
                break;
            case 'select':
                if (isset($field['options']) && !array_key_exists($value, $field['options'])) {
                    $errors[] = "{$field['label']} için geçersiz seçim.";
                }
                break;
        }

        return $errors;
    }
}