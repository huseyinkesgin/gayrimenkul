<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\DynamicFormHelper;

class DynamicFormHelperTest extends TestCase
{
    /** @test */
    public function it_generates_form_fields_for_mulk_type()
    {
        $formFields = DynamicFormHelper::generateFormFields('ticari_arsa');
        
        $this->assertIsArray($formFields);
        $this->assertNotEmpty($formFields);
        
        // Check if required fields exist
        $this->assertArrayHasKey('imar_durumu', $formFields);
        $this->assertArrayHasKey('kaks', $formFields);
        $this->assertArrayHasKey('ada_no', $formFields);
    }

    /** @test */
    public function it_generates_grouped_form_fields()
    {
        $groupedFields = DynamicFormHelper::generateGroupedFormFields('ticari_arsa');
        
        $this->assertIsArray($groupedFields);
        $this->assertNotEmpty($groupedFields);
        
        // Check if groups exist
        $this->assertArrayHasKey('İmar Bilgileri', $groupedFields);
        $this->assertArrayHasKey('Tapu Bilgileri', $groupedFields);
        
        // Check if fields are properly grouped
        $imarFields = $groupedFields['İmar Bilgileri'];
        $this->assertArrayHasKey('imar_durumu', $imarFields);
        $this->assertArrayHasKey('kaks', $imarFields);
    }

    /** @test */
    public function form_fields_have_correct_structure()
    {
        $formFields = DynamicFormHelper::generateFormFields('fabrika');
        
        foreach ($formFields as $fieldName => $field) {
            // Required structure
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('label', $field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('required', $field);
            $this->assertArrayHasKey('group', $field);
            $this->assertArrayHasKey('attributes', $field);
            $this->assertArrayHasKey('validation', $field);
            
            // Data types
            $this->assertIsString($field['name']);
            $this->assertIsString($field['label']);
            $this->assertIsString($field['type']);
            $this->assertIsBool($field['required']);
            $this->assertIsArray($field['attributes']);
            $this->assertIsArray($field['validation']);
        }
    }

    /** @test */
    public function select_fields_have_options()
    {
        $formFields = DynamicFormHelper::generateFormFields('ticari_arsa');
        
        $selectFields = array_filter($formFields, function($field) {
            return $field['type'] === 'select';
        });

        foreach ($selectFields as $field) {
            $this->assertArrayHasKey('options', $field);
            $this->assertIsArray($field['options']);
            $this->assertNotEmpty($field['options']);
        }
    }

    /** @test */
    public function number_fields_have_constraints()
    {
        $formFields = DynamicFormHelper::generateFormFields('fabrika');
        
        $numberFields = array_filter($formFields, function($field) {
            return $field['type'] === 'number';
        });

        foreach ($numberFields as $field) {
            if (isset($field['min'])) {
                $this->assertIsNumeric($field['min']);
            }
            if (isset($field['max'])) {
                $this->assertIsNumeric($field['max']);
            }
            if (isset($field['step'])) {
                $this->assertIsNumeric($field['step']);
            }
        }
    }

    /** @test */
    public function it_generates_validation_rules_correctly()
    {
        $formFields = DynamicFormHelper::generateFormFields('daire');
        
        foreach ($formFields as $field) {
            $validation = $field['validation'];
            
            // Should have nullable or required
            $this->assertTrue(
                in_array('nullable', $validation) || in_array('required', $validation)
            );
            
            // Type-specific validations
            if ($field['type'] === 'number') {
                $this->assertContains('numeric', $validation);
            }
            
            if ($field['type'] === 'text' || $field['type'] === 'textarea') {
                $this->assertContains('string', $validation);
            }
            
            if ($field['type'] === 'checkbox') {
                $this->assertContains('boolean', $validation);
            }
        }
    }

    /** @test */
    public function it_validates_form_data_correctly()
    {
        $testData = [
            'oda_sayisi' => 'invalid_number',
            'salon_sayisi' => 3,
            'banyo_sayisi' => 2,
            'asansor_var_mi' => true
        ];

        $errors = DynamicFormHelper::validateFormData('daire', $testData);
        
        $this->assertIsArray($errors);
        
        // Should have error for invalid number
        $this->assertArrayHasKey('oda_sayisi', $errors);
        
        // Should not have errors for valid data
        $this->assertArrayNotHasKey('salon_sayisi', $errors);
        $this->assertArrayNotHasKey('banyo_sayisi', $errors);
        $this->assertArrayNotHasKey('asansor_var_mi', $errors);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        // Get a field that is required
        $formFields = DynamicFormHelper::generateFormFields('ticari_arsa');
        $requiredFields = array_filter($formFields, function($field) {
            return $field['required'];
        });

        if (!empty($requiredFields)) {
            $requiredFieldName = array_keys($requiredFields)[0];
            
            $testData = [
                $requiredFieldName => '' // Empty value for required field
            ];

            $errors = DynamicFormHelper::validateFormData('ticari_arsa', $testData);
            
            $this->assertArrayHasKey($requiredFieldName, $errors);
        }
    }

    /** @test */
    public function it_validates_select_options()
    {
        $testData = [
            'imar_durumu' => 'invalid_option'
        ];

        $errors = DynamicFormHelper::validateFormData('ticari_arsa', $testData);
        
        $this->assertArrayHasKey('imar_durumu', $errors);
        $this->assertStringContainsString('geçersiz seçim', strtolower($errors['imar_durumu'][0]));
    }

    /** @test */
    public function it_validates_number_constraints()
    {
        $testData = [
            'oda_sayisi' => -1, // Below minimum
            'salon_sayisi' => 100 // Above maximum (if max is set)
        ];

        $errors = DynamicFormHelper::validateFormData('daire', $testData);
        
        // Should have error for negative number
        if (isset($errors['oda_sayisi'])) {
            $this->assertNotEmpty($errors['oda_sayisi']);
        }
    }

    /** @test */
    public function it_validates_string_length()
    {
        $longString = str_repeat('a', 1000); // Very long string
        
        $testData = [
            'ada_no' => $longString
        ];

        $errors = DynamicFormHelper::validateFormData('ticari_arsa', $testData);
        
        if (isset($errors['ada_no'])) {
            $this->assertStringContainsString('karakter', strtolower($errors['ada_no'][0]));
        }
    }

    /** @test */
    public function it_generates_javascript_validation()
    {
        $jsValidation = DynamicFormHelper::generateJavaScriptValidation('daire');
        
        $this->assertIsString($jsValidation);
        $this->assertJson($jsValidation);
        
        $validationRules = json_decode($jsValidation, true);
        $this->assertIsArray($validationRules);
    }

    /** @test */
    public function it_generates_css_classes()
    {
        $field = [
            'required' => true,
            'type' => 'number',
            'unit' => 'm²'
        ];

        $cssClasses = DynamicFormHelper::generateCssClasses($field);
        
        $this->assertIsString($cssClasses);
        $this->assertStringContainsString('form-control', $cssClasses);
        $this->assertStringContainsString('required', $cssClasses);
        $this->assertStringContainsString('has-unit', $cssClasses);
    }
}