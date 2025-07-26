<div class="mulk-ozellik-yonetimi">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">
            {{ $mulk->baslik }} - Özellik Yönetimi
        </h2>
        <div class="flex space-x-2">
            <button wire:click="saveAllProperties" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Tümünü Kaydet
            </button>
            <button wire:click="exportProperties" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                Export
            </button>
            <button wire:click="resetAllProperties" 
                    onclick="return confirm('Tüm özellikler silinecek. Emin misiniz?')"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                Sıfırla
            </button>
        </div>
    </div>

    <!-- Validation Errors -->
    @if($showValidationErrors && !empty($validationErrors))
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Aşağıdaki hatalar düzeltilmelidir:</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($validationErrors as $field => $errors)
                                @foreach($errors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar - Groups -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Özellik Grupları</h3>
                <nav class="space-y-1">
                    @foreach($groups as $group)
                        <button wire:click="setActiveGroup('{{ $group }}')"
                                class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors
                                       {{ $currentGroup === $group 
                                          ? 'bg-blue-100 text-blue-700' 
                                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            {{ $group }}
                            @if(isset($groupedFields[$group]))
                                <span class="ml-2 text-xs text-gray-500">
                                    ({{ count($groupedFields[$group]) }})
                                </span>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </div>
        </div>

        <!-- Main Content - Form Fields -->
        <div class="lg:col-span-3">
            <div class="bg-white shadow rounded-lg p-6">
                @if($currentGroup && isset($currentGroupFields))
                    <div class="mb-4 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">{{ $currentGroup }}</h3>
                        <button wire:click="saveAllProperties" 
                                class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">
                            Kaydet
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($currentGroupFields as $fieldKey => $field)
                            <div class="form-field">
                                <label for="{{ $fieldKey }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $field['label'] }}
                                    @if($field['required'])
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>

                                @if($field['type'] === 'text')
                                    <input type="text" 
                                           id="{{ $fieldKey }}" 
                                           wire:model.lazy="properties.{{ $fieldKey }}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                                  @error('properties.' . $fieldKey) border-red-300 @enderror"
                                           @if(isset($field['attributes']['maxlength'])) maxlength="{{ $field['attributes']['maxlength'] }}" @endif
                                           @if($field['required']) required @endif>

                                @elseif($field['type'] === 'number')
                                    <div class="relative">
                                        <input type="number" 
                                               id="{{ $fieldKey }}" 
                                               wire:model.lazy="properties.{{ $fieldKey }}"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                                      @error('properties.' . $fieldKey) border-red-300 @enderror
                                                      @if(isset($field['unit'])) pr-12 @endif"
                                               @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                                               @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                                               @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                                               @if($field['required']) required @endif>
                                        @if(isset($field['unit']))
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">{{ $field['unit'] }}</span>
                                            </div>
                                        @endif
                                    </div>

                                @elseif($field['type'] === 'textarea')
                                    <textarea id="{{ $fieldKey }}" 
                                              wire:model.lazy="properties.{{ $fieldKey }}"
                                              rows="3"
                                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                                     @error('properties.' . $fieldKey) border-red-300 @enderror"
                                              @if(isset($field['attributes']['maxlength'])) maxlength="{{ $field['attributes']['maxlength'] }}" @endif
                                              @if($field['required']) required @endif></textarea>

                                @elseif($field['type'] === 'select')
                                    <select id="{{ $fieldKey }}" 
                                            wire:model="properties.{{ $fieldKey }}"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                                   @error('properties.' . $fieldKey) border-red-300 @enderror"
                                            @if($field['required']) required @endif>
                                        <option value="">Seçiniz...</option>
                                        @foreach($field['options'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>

                                @elseif($field['type'] === 'checkbox')
                                    <div class="mt-1">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" 
                                                   id="{{ $fieldKey }}" 
                                                   wire:model="properties.{{ $fieldKey }}"
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-600">Evet</span>
                                        </label>
                                    </div>
                                @endif

                                @if(isset($field['help']))
                                    <p class="mt-1 text-xs text-gray-500">{{ $field['help'] }}</p>
                                @endif

                                @error('properties.' . $fieldKey)
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                @if(isset($validationErrors[$fieldKey]))
                                    @foreach($validationErrors[$fieldKey] as $error)
                                        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
                                    @endforeach
                                @endif

                                <!-- Individual Save Button -->
                                <div class="mt-2">
                                    <button wire:click="saveProperty('{{ $fieldKey }}')" 
                                            class="text-xs bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 rounded">
                                        Kaydet
                                    </button>
                                    @if(isset($properties[$fieldKey]) && !empty($properties[$fieldKey]))
                                        <button wire:click="removeProperty('{{ $fieldKey }}')" 
                                                onclick="return confirm('Bu özellik silinecek. Emin misiniz?')"
                                                class="ml-2 text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded">
                                            Sil
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Özellik grubu seçin</h3>
                        <p class="mt-1 text-sm text-gray-500">Soldaki menüden bir özellik grubu seçerek başlayın.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Property Summary -->
    @if(!empty($properties))
        <div class="mt-6 bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Özellik Özeti</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($properties as $key => $value)
                    @if(!empty($value))
                        @php
                            $ozellikTanimlari = \App\Services\MulkOzellikTanimlariService::getOzellikTanimlari($mulkType);
                            $ozellik = $ozellikTanimlari[$key] ?? null;
                        @endphp
                        <div class="bg-gray-50 rounded-lg p-3">
                            <dt class="text-sm font-medium text-gray-500">
                                {{ $ozellik['label'] ?? $key }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($ozellik && $ozellik['type'] === 'checkbox')
                                    {{ $value ? 'Evet' : 'Hayır' }}
                                @elseif($ozellik && $ozellik['type'] === 'select' && isset($ozellik['options'][$value]))
                                    {{ $ozellik['options'][$value] }}
                                @else
                                    {{ $value }}
                                    @if($ozellik && isset($ozellik['unit']))
                                        {{ $ozellik['unit'] }}
                                    @endif
                                @endif
                            </dd>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
    // Auto-save functionality
    document.addEventListener('livewire:load', function () {
        let autoSaveTimeout;
        
        Livewire.on('propertyUpdated', function (key, value) {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                @this.call('saveProperty', key);
            }, 2000); // Auto-save after 2 seconds of inactivity
        });
    });
</script>