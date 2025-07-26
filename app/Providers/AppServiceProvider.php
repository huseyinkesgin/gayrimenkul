<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MulkOzellikTanimlariService;
use App\Services\MulkValidationService;
use App\Services\EnumService;
use App\Helpers\DynamicFormHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Mülk Özellik Tanımları Servisi
        $this->app->singleton(MulkOzellikTanimlariService::class, function ($app) {
            return new MulkOzellikTanimlariService();
        });

        // Mülk Validation Servisi
        $this->app->singleton(MulkValidationService::class, function ($app) {
            return new MulkValidationService();
        });

        // Enum Servisi
        $this->app->singleton(EnumService::class, function ($app) {
            return new EnumService();
        });

        // Dynamic Form Helper
        $this->app->singleton(DynamicFormHelper::class, function ($app) {
            return new DynamicFormHelper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // View Composers
        $this->registerViewComposers();
        
        // Blade Directives
        $this->registerBladeDirectives();
        
        // Validation Rules
        $this->registerCustomValidationRules();
    }

    /**
     * View composer'ları kaydet
     */
    protected function registerViewComposers(): void
    {
        // Mülk formu için enum'ları view'a aktar
        view()->composer('livewire.mulk-ozellik-yonetimi', function ($view) {
            $view->with('enumService', app(EnumService::class));
        });

        // Genel enum'ları tüm view'lara aktar
        view()->composer('*', function ($view) {
            $view->with('enums', app(EnumService::class)->getCachedEnums());
        });
    }

    /**
     * Özel Blade directive'leri kaydet
     */
    protected function registerBladeDirectives(): void
    {
        // Mülk özellik formu directive'i
        \Blade::directive('mulkOzellikForm', function ($expression) {
            return "<?php echo app(App\Helpers\DynamicFormHelper::class)->generateLivewireComponent({$expression}); ?>";
        });

        // Enum label directive'i
        \Blade::directive('enumLabel', function ($expression) {
            list($enumName, $value) = explode(',', str_replace(['(', ')', ' ', "'", '"'], '', $expression));
            return "<?php echo app(App\Services\EnumService::class)->getLabel('{$enumName}', {$value}); ?>";
        });

        // Enum color directive'i
        \Blade::directive('enumColor', function ($expression) {
            list($enumName, $value) = explode(',', str_replace(['(', ')', ' ', "'", '"'], '', $expression));
            return "<?php echo app(App\Services\EnumService::class)->getColor('{$enumName}', {$value}); ?>";
        });
    }

    /**
     * Özel validation kurallarını kaydet
     */
    protected function registerCustomValidationRules(): void
    {
        // Mülk tipi validation
        \Validator::extend('valid_mulk_type', function ($attribute, $value, $parameters, $validator) {
            $validTypes = [
                'ticari_arsa', 'sanayi_arsasi', 'konut_arsasi',
                'fabrika', 'depo', 'ofis', 'magaza', 'dukkan',
                'daire', 'villa', 'rezidans', 'yali', 'yazlik',
                'butik_otel', 'apart_otel', 'hotel', 'motel', 'tatil_koyu'
            ];
            return in_array($value, $validTypes);
        });

        // Mülk özellik validation
        \Validator::extend('valid_mulk_property', function ($attribute, $value, $parameters, $validator) {
            if (count($parameters) < 2) {
                return false;
            }
            
            $mulkType = $parameters[0];
            $propertyName = $parameters[1];
            
            $ozellikler = app(MulkOzellikTanimlariService::class)->getOzellikTanimlari($mulkType);
            return array_key_exists($propertyName, $ozellikler);
        });

        // Enum değer validation
        \Validator::extend('valid_enum_value', function ($attribute, $value, $parameters, $validator) {
            if (count($parameters) < 1) {
                return false;
            }
            
            $enumName = $parameters[0];
            return app(EnumService::class)->isValidValue($enumName, $value);
        });

        // Validation mesajları
        \Validator::replacer('valid_mulk_type', function ($message, $attribute, $rule, $parameters) {
            return 'Geçersiz mülk tipi.';
        });

        \Validator::replacer('valid_mulk_property', function ($message, $attribute, $rule, $parameters) {
            return 'Bu mülk tipi için geçersiz özellik.';
        });

        \Validator::replacer('valid_enum_value', function ($message, $attribute, $rule, $parameters) {
            return 'Geçersiz enum değeri.';
        });
    }
}
