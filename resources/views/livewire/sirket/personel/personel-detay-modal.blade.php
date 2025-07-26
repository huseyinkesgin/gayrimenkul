<x-custom-modal name="personel-detay-modal" title="👁️ Personel Detayları" size="1200x900"
    :subtitle="$personel && $personel->kisi ? '📋 ' . $personel->kisi->ad . ' ' . $personel->kisi->soyad . ' (' . $personel->personel_no . ') - Detaylı Bilgiler' : 'Personel detaylarını görüntüleyebilirsiniz'"
    :show-save="false" :show-clear="false">

    <x-slot name="actions">
        @if($personel)
            <button wire:click="$dispatch('loadPersonelForEdit', { personelId: '{{ $personel->id }}' }); $dispatch('open-modal', { name: 'personel-duzenleme-modal' })"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                ✏️ Düzenle
            </button>
            <button wire:click="$dispatch('loadPersonelForAdres', { personelId: '{{ $personel->id }}' }); $dispatch('open-modal', { name: 'personel-adres-ekleme-modal' })"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                🏠 Adres Ekle
            </button>
        @endif
    </x-slot>

    @if($loading)
        <div class="flex items-center justify-center py-16">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-amber-100 to-orange-100 rounded-full mb-6">
                    <svg class="animate-spin w-10 h-10 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-amber-900 mb-3">Personel Detayları Yükleniyor</h3>
                <p class="text-amber-600">Bilgiler hazırlanıyor, lütfen bekleyiniz...</p>
            </div>
        </div>
    @elseif($personel && $personel->kisi)
        <!-- Hero Section - Personel Özet Kartı -->
        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 rounded-2xl p-8 mb-8 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 bg-black bg-opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.1"><circle cx="30" cy="30" r="4"/></g></g></svg>' </div>
            </div>
            
            <div class="relative flex items-center space-x-8">
                <!-- Avatar -->
                <div class="relative">
                    <div class="w-32 h-32 rounded-full overflow-hidden bg-white bg-opacity-20 border-4 border-white shadow-2xl">
                        @if($personel->avatar)
                            <img src="{{ asset('storage/' . $personel->avatar->url) }}" 
                                 alt="{{ $personel->kisi->ad }} {{ $personel->kisi->soyad }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-white">
                                <span class="text-5xl font-bold">
                                    {{ strtoupper(substr($personel->kisi->ad, 0, 1)) }}{{ strtoupper(substr($personel->kisi->soyad, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Status Badge -->
                    <div class="absolute -bottom-2 -right-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold shadow-lg
                            @if($personel->calisma_durumu === 'Aktif') bg-green-500 text-white
                            @elseif($personel->calisma_durumu === 'Pasif') bg-yellow-500 text-white
                            @elseif($personel->calisma_durumu === 'İzinli') bg-blue-500 text-white
                            @else bg-red-500 text-white
                            @endif">
                            @if($personel->calisma_durumu === 'Aktif') ✅
                            @elseif($personel->calisma_durumu === 'Pasif') ⏸️
                            @elseif($personel->calisma_durumu === 'İzinli') 🏖️
                            @else ❌
                            @endif
                            {{ $personel->calisma_durumu }}
                        </span>
                    </div>
                </div>
                
                <!-- Personel Bilgileri -->
                <div class="flex-1">
                    <h1 class="text-4xl font-bold mb-2">{{ $personel->kisi->ad }} {{ $personel->kisi->soyad }}</h1>
                    <div class="flex items-center space-x-6 text-lg mb-4">
                        <span class="flex items-center text-black bg-white bg-opacity-20 px-3 py-1 rounded-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V4a2 2 0 114 0v2m-4 0a2 2 0 104 0m-4 0v2m4-2v2"></path>
                            </svg>
                            {{ $personel->personel_no }}
                        </span>
                        @if($personel->pozisyon)
                            <span class="flex items-center text-black bg-white bg-opacity-20 px-3 py-1 rounded-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 00-2 2H8a2 2 0 00-2-2V4"></path>
                                </svg>
                                {{ $personel->pozisyon->ad }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        @if($personel->departman)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <strong>Departman:</strong> {{ $personel->departman->ad }}
                            </div>
                        @endif
                        @if($personel->sube)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <strong>Şube:</strong> {{ $personel->sube->ad }}
                            </div>
                        @endif
                        @if($personel->ise_baslama_tarihi)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <strong>İşe Başlama:</strong> {{ $personel->ise_baslama_tarihi->format('d.m.Y') }}
                            </div>
                        @endif
                        @if($personel->calisma_sekli)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <strong>Çalışma Şekli:</strong> {{ $personel->calisma_sekli }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Detay Bilgiler -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Kişi Bilgileri -->
            <div class="bg-white rounded-xl border border-amber-200 shadow-sm">
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white px-6 py-4 rounded-t-xl">
                    <h3 class="text-lg font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        👤 Kişi Bilgileri
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Ad Soyad</label>
                            <p class="text-sm text-gray-900 font-semibold">{{ $personel->kisi->ad }} {{ $personel->kisi->soyad }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">TC Kimlik No</label>
                            <p class="text-sm text-gray-900">{{ $personel->kisi->tc_kimlik_no }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Doğum Tarihi</label>
                            <p class="text-sm text-gray-900">{{ $personel->kisi->dogum_tarihi?->format('d.m.Y') ?? 'Belirtilmemiş' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Cinsiyet</label>
                            <p class="text-sm text-gray-900">
                                @if($personel->kisi->cinsiyet === 'Erkek') 👨 Erkek
                                @elseif($personel->kisi->cinsiyet === 'Kadın') 👩 Kadın
                                @elseif($personel->kisi->cinsiyet === 'Diğer') ⚧ Diğer
                                @else Belirtilmemiş
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Doğum Yeri</label>
                            <p class="text-sm text-gray-900">{{ $personel->kisi->dogum_yeri ?? 'Belirtilmemiş' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Medeni Hali</label>
                            <p class="text-sm text-gray-900">
                                @if($personel->kisi->medeni_hali === 'Bekar') 💍 Bekar
                                @elseif($personel->kisi->medeni_hali === 'Evli') 💑 Evli
                                @elseif($personel->kisi->medeni_hali === 'Dul') 🖤 Dul
                                @elseif($personel->kisi->medeni_hali === 'Boşanmış') 💔 Boşanmış
                                @else Belirtilmemiş
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">📧 Email</label>
                            <p class="text-sm text-gray-900">{{ $personel->kisi->email ?? 'Belirtilmemiş' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">📱 Telefon</label>
                            <p class="text-sm text-gray-900">{{ $personel->kisi->telefon ?? 'Belirtilmemiş' }}</p>
                        </div>
                    </div>
                    @if($personel->kisi->notlar)
                        <div>
                            <label class="text-sm font-medium text-gray-600">📝 Kişi Notları</label>
                            <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">{{ $personel->kisi->notlar }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Personel Bilgileri -->
            <div class="bg-white rounded-xl border border-orange-200 shadow-sm">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-6 py-4 rounded-t-xl">
                    <h3 class="text-lg font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 00-2 2H8a2 2 0 00-2-2V4"></path>
                        </svg>
                        💼 Personel Bilgileri
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Personel No</label>
                            <p class="text-sm text-gray-900 font-semibold">{{ $personel->personel_no }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Çalışma Durumu</label>
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold
                                @if($personel->calisma_durumu === 'Aktif') bg-green-100 text-green-700
                                @elseif($personel->calisma_durumu === 'Pasif') bg-yellow-100 text-yellow-700
                                @elseif($personel->calisma_durumu === 'İzinli') bg-blue-100 text-blue-700
                                @else bg-red-100 text-red-700
                                @endif">
                                @if($personel->calisma_durumu === 'Aktif') ✅
                                @elseif($personel->calisma_durumu === 'Pasif') ⏸️
                                @elseif($personel->calisma_durumu === 'İzinli') 🏖️
                                @else ❌
                                @endif
                                {{ $personel->calisma_durumu }}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">🏢 Şube</label>
                            <p class="text-sm text-gray-900">{{ $personel->sube?->ad ?? 'Belirtilmemiş' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">🏛️ Departman</label>
                            <p class="text-sm text-gray-900">{{ $personel->departman?->ad ?? 'Belirtilmemiş' }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">👔 Pozisyon</label>
                            <p class="text-sm text-gray-900">{{ $personel->pozisyon?->ad ?? 'Belirtilmemiş' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">⏰ Çalışma Şekli</label>
                            <p class="text-sm text-gray-900">{{ $personel->calisma_sekli ?? 'Belirtilmemiş' }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">📅 İşe Başlama Tarihi</label>
                            <p class="text-sm text-gray-900">{{ $personel->ise_baslama_tarihi?->format('d.m.Y') ?? 'Belirtilmemiş' }}</p>
                        </div>
                        @if($personel->isten_ayrilma_tarihi)
                            <div>
                                <label class="text-sm font-medium text-gray-600">📅 İşten Ayrılma Tarihi</label>
                                <p class="text-sm text-gray-900">{{ $personel->isten_ayrilma_tarihi->format('d.m.Y') }}</p>
                            </div>
                        @endif
                    </div>
                    @if($personel->roller && $personel->roller->count() > 0)
                        <div>
                            <label class="text-sm font-medium text-gray-600">🎭 Roller</label>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach($personel->roller as $rol)
                                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                        {{ $rol->ad }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($personel->notlar)
                        <div>
                            <label class="text-sm font-medium text-gray-600">📝 Personel Notları</label>
                            <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">{{ $personel->notlar }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Adres Bilgileri -->
        @if($personel->kisi->adresler && $personel->kisi->adresler->count() > 0)
            <div class="bg-white rounded-xl border border-yellow-200 shadow-sm mt-8">
                <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-6 py-4 rounded-t-xl">
                    <h3 class="text-lg font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        🏠 Adres Bilgileri
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($personel->kisi->adresler as $adres)
                            <div class="border border-gray-200 p-4 rounded-xl bg-gradient-to-br from-gray-50 to-white shadow-sm">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="font-semibold text-gray-800 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        {{ $adres->adres_adi }}
                                    </h4>
                                    <div class="flex items-center gap-2">
                                        @if($adres->varsayilan_mi)
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                                ⭐ Varsayılan
                                            </span>
                                        @endif
                                        <button wire:click="$dispatch('loadPersonelAdresForEdit', { personelId: '{{ $personel->id }}', adresId: '{{ $adres->id }}' })" 
                                                class="p-1 text-green-600 hover:text-green-800 hover:bg-green-100 rounded transition-colors" 
                                                title="Adresi Düzenle">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <flux:modal.trigger name="adres-sil-{{ $adres->id }}" class="inline-block">
                                            <button class="p-1 text-red-600 hover:text-red-800 hover:bg-red-100 rounded transition-colors" 
                                                    title="Adresi Sil">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </flux:modal.trigger>
                                        
                                        <flux:modal name="adres-sil-{{ $adres->id }}" class="min-w-[22rem]">
                                            <div class="space-y-6">
                                                <div>
                                                    <flux:heading size="lg">🗑️ Adresi Sil</flux:heading>
                                                    <flux:text class="mt-2">
                                                        <p><strong>{{ $adres->adres_adi }}</strong> adresini silmek üzeresiniz.</p>
                                                        <p class="text-red-600 font-medium">Bu işlem geri alınamaz!</p>
                                                    </flux:text>
                                                </div>
                                                <div class="flex gap-2">
                                                    <flux:spacer />
                                                    <flux:modal.close>
                                                        <flux:button variant="ghost" size="sm">❌ Vazgeç</flux:button>
                                                    </flux:modal.close>
                                                    <flux:button type="button" variant="danger" size="sm"
                                                        wire:click="deleteAdres('{{ $adres->id }}')">🗑️ Sil</flux:button>
                                                </div>
                                            </div>
                                        </flux:modal>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-700 mb-3 bg-white p-2 rounded-lg">{{ $adres->adres_detay }}</p>
                                <div class="text-xs text-gray-600 space-y-1">
                                    <p class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        </svg>
                                        {{ $adres->mahalle?->ad }} {{ $adres->semt?->ad }}
                                    </p>
                                    <p class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        {{ $adres->ilce?->ad }} / {{ $adres->sehir?->ad }}
                                    </p>
                                    @if($adres->posta_kodu)
                                        <p class="flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            Posta Kodu: {{ $adres->posta_kodu }}
                                        </p>
                                    @endif
                                </div>
                                @if($adres->notlar)
                                    <p class="text-xs text-gray-500 mt-3 italic bg-yellow-50 p-2 rounded">💭 {{ $adres->notlar }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    @elseif($personelId)
        <div class="text-center py-16">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Personel Bulunamadı</h3>
            <p class="text-gray-600">Seçilen personel bulunamadı veya silinmiş olabilir.</p>
        </div>
    @else
        <div class="text-center py-16">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-amber-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Personel Seçiniz</h3>
            <p class="text-gray-600">Detaylarını görüntülemek için bir personel seçiniz.</p>
        </div>
    @endif

</x-custom-modal>