<div class="space-y-6">
    {{-- Header ve Aksiyonlar --}}
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Döküman Yönetimi</h3>
            <p class="text-sm text-gray-500">
                Toplam {{ $statistics['toplam_dokuman'] ?? 0 }} döküman
                @if ($statistics['arsivlenen_dokuman'] ?? 0 > 0)
                    ({{ $statistics['arsivlenen_dokuman'] }} arşivlenmiş)
                @endif
            </p>
        </div>

        <div class="flex space-x-2">
            <button wire:click="toggleStatistics"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
                İstatistikler
            </button>

            <button wire:click="openUploadModal"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Döküman Yükle
            </button>
        </div>
    </div>

    {{-- Eksik Zorunlu Dökümanlar Uyarısı --}}
    @if (count($eksikZorunluDokumanlar) > 0)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Eksik Zorunlu Dökümanlar</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($eksikZorunluDokumanlar as $eksik)
                                <li>{{ $eksik['label'] }} - {{ $eksik['description'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- İstatistikler --}}
    @if ($showStatistics && !empty($statistics))
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Döküman İstatistikleri</h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ $statistics['toplam_dokuman'] }}</div>
                        <div class="text-sm text-blue-600">Toplam Döküman</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">
                            {{ number_format($statistics['toplam_boyut'] / 1024 / 1024, 2) }} MB</div>
                        <div class="text-sm text-green-600">Toplam Boyut</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600">{{ $statistics['arsivlenen_dokuman'] }}</div>
                        <div class="text-sm text-yellow-600">Arşivlenmiş</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-red-600">{{ $statistics['silinen_dokuman'] ?? 0 }}</div>
                        <div class="text-sm text-red-600">Silinmiş</div>
                    </div>
                </div>

                {{-- Tip Bazında Dağılım --}}
                @if (!empty($statistics['tip_bazinda_dagilim']))
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-3">Tip Bazında Dağılım</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach ($statistics['tip_bazinda_dagilim'] as $tip => $data)
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="font-medium">{{ $data['label'] }}</div>
                                    <div class="text-sm text-gray-600">
                                        {{ $data['adet'] }} dosya -
                                        {{ number_format($data['toplam_boyut'] / 1024 / 1024, 2) }} MB
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Filtreleme ve Arama --}}
    <div class="bg-white shadow rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Arama</label>
                <input type="text" wire:model.debounce.300ms="searchTerm" placeholder="Döküman ara..."
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Döküman Tipi</label>
                <select wire:model="filterDokumanTipi"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">Tüm Tipler</option>
                    @foreach ($uygunTipler as $tip)
                        <option value="{{ $tip['value'] }}">{{ $tip['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Durum</label>
                <select wire:model="filterDurum"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="aktif">Aktif</option>
                    <option value="arsivlenmis">Arşivlenmiş</option>
                    <option value="silinen">Silinmiş</option>
                    <option value="tumu">Tümü</option>
                </select>
            </div>

            <div class="flex items-end">
                <button wire:click="resetFilters"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Filtreleri Sıfırla
                </button>
            </div>
        </div>
    </div>

    {{-- Döküman Listesi --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        @if ($dokumanlar->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Döküman bulunamadı</h3>
                <p class="mt-1 text-sm text-gray-500">Henüz döküman yüklenmemiş.</p>
                <div class="mt-6">
                    <button wire:click="openUploadModal"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        İlk Dökümanı Yükle
                    </button>
                </div>
            </div>
        @else
            @foreach ($dokumanlar as $tip => $tipDokumanlari)
                <div class="border-b border-gray-200 last:border-b-0">
                    <div class="bg-gray-50 px-4 py-3">
                        <h4 class="text-sm font-medium text-gray-900">
                            {{ \App\Enums\DokumanTipi::from($tip)->label() }}
                            <span class="ml-2 text-xs text-gray-500">({{ $tipDokumanlari->count() }} dosya)</span>
                        </h4>
                    </div>

                    <ul class="divide-y divide-gray-200">
                        @foreach ($tipDokumanlari as $dokuman)
                            <li class="px-4 py-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center min-w-0 flex-1">
                                        <div class="flex-shrink-0">
                                            @if ($dokuman->is_viewable)
                                                <svg class="h-8 w-8 text-green-500" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            @else
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            @endif
                                        </div>

                                        <div class="ml-4 min-w-0 flex-1">
                                            <div class="flex items-center">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $dokuman->baslik }}
                                                </p>

                                                @if ($dokuman->versiyon > 1)
                                                    <span
                                                        class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        v{{ $dokuman->versiyon }}
                                                    </span>
                                                @endif

                                                @if ($dokuman->gizli_mi)
                                                    <span
                                                        class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Gizli
                                                    </span>
                                                @endif

                                                @if (!$dokuman->aktif_mi)
                                                    <span
                                                        class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Arşivlenmiş
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="mt-1 flex items-center text-sm text-gray-500">
                                                <span>{{ $dokuman->formatted_size }}</span>
                                                <span class="mx-2">•</span>
                                                <span>{{ $dokuman->olusturma_tarihi->format('d.m.Y H:i') }}</span>
                                                @if ($dokuman->olusturan)
                                                    <span class="mx-2">•</span>
                                                    <span>{{ $dokuman->olusturan->name }}</span>
                                                @endif
                                                @if ($dokuman->erisim_sayisi > 0)
                                                    <span class="mx-2">•</span>
                                                    <span>{{ $dokuman->erisim_sayisi }} görüntüleme</span>
                                                @endif
                                            </div>

                                            @if ($dokuman->aciklama)
                                                <p class="mt-1 text-sm text-gray-600">{{ $dokuman->aciklama }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        {{-- İndir --}}
                                        <button wire:click="downloadDokuman({{ $dokuman->id }})"
                                            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                            title="İndir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                        </button>

                                        {{-- Versiyon Güncelle --}}
                                        @if ($dokuman->aktif_mi)
                                            <button wire:click="openVersionModal({{ $dokuman->id }})"
                                                class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                                title="Versiyon Güncelle">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                    </path>
                                                </svg>
                                            </button>
                                        @endif

                                        {{-- Sil/Geri Yükle --}}
                                        @if ($dokuman->aktif_mi)
                                            <button wire:click="openDeleteModal({{ $dokuman->id }})"
                                                class="text-red-600 hover:text-red-900 text-sm font-medium"
                                                title="Sil">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        @else
                                            <button wire:click="openRestoreModal({{ $dokuman->id }})"
                                                class="text-green-600 hover:text-green-900 text-sm font-medium"
                                                title="Geri Yükle">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6">
                                                    </path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Upload Modal --}}
    @if ($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="uploadDokuman">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Döküman Yükle</h3>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Döküman Tipi
                                                *</label>
                                            <select wire:model="selectedDokumanTipi"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                required>
                                                <option value="">Seçiniz...</option>
                                                @foreach ($uygunTipler as $tip)
                                                    <option value="{{ $tip['value'] }}">{{ $tip['label'] }}</option>
                                                @endforeach
                                            </select>
                                            @error('selectedDokumanTipi')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Dosyalar *</label>
                                            <input type="file" wire:model="files" multiple
                                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                                required>
                                            @error('files')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                            @error('files.*')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Başlık</label>
                                            <input type="text" wire:model="baslik"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                placeholder="Döküman başlığı">
                                            @error('baslik')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Açıklama</label>
                                            <textarea wire:model="aciklama" rows="3"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                placeholder="Döküman açıklaması"></textarea>
                                            @error('aciklama')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="flex items-center">
                                            <input type="checkbox" wire:model="gizliMi"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label class="ml-2 block text-sm text-gray-900">Gizli döküman</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Yükle
                            </button>
                            <button type="button" wire:click="closeUploadModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                İptal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Diğer modaller (Version, Delete, Restore) burada olacak --}}
    {{-- Bu modaller benzer yapıda olacağı için kısaltıldı --}}
</div>
