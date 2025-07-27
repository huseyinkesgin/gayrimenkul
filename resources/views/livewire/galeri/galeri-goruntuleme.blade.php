<div class="galeri-container">
    {{-- Galeri Başlığı ve Kontroller --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">
                {{ $mulkBaslik }} - Galeri
            </h2>
            @if ($galeriAktif && !empty($galeriIstatistikleri))
                <p class="text-sm text-gray-600 mt-1">
                    {{ $galeriIstatistikleri['toplam_resim'] }} resim
                    ({{ $galeriIstatistikleri['min_resim'] }}-{{ $galeriIstatistikleri['max_resim'] }} arası)
                    - %{{ number_format($this->galeriDolulukOrani, 1) }} dolu
                </p>
            @endif
        </div>

        @if ($galeriAktif)
            <div class="flex space-x-2">
                {{-- Organizasyon Önerileri --}}
                <button wire:click="organizasyonOnerileriniAl"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-lightbulb mr-2"></i>
                    Öneriler
                </button>

                {{-- Sıralama Modu --}}
                <button wire:click="siralamaModunuToggle"
                    class="px-4 py-2 {{ $siralamaModuAktif ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white rounded-lg transition-colors">
                    <i class="fas fa-sort mr-2"></i>
                    {{ $siralamaModuAktif ? 'Sıralamayı Bitir' : 'Sırala' }}
                </button>

                {{-- Toplu İşlem Modu --}}
                <button wire:click="topluIslemModunuToggle"
                    class="px-4 py-2 {{ $topluIslemModuAktif ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white rounded-lg transition-colors">
                    <i class="fas fa-check-square mr-2"></i>
                    {{ $topluIslemModuAktif ? 'Seçimi İptal Et' : 'Toplu İşlem' }}
                </button>
            </div>
        @endif
    </div>

    @if (!$galeriAktif)
        {{-- Galeri Aktif Değil Uyarısı --}}
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <i class="fas fa-info-circle text-yellow-600 text-3xl mb-3"></i>
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">Galeri Mevcut Değil</h3>
            <p class="text-yellow-700">Bu mülk tipi için galeri özelliği desteklenmemektedir.</p>
        </div>
    @else
        {{-- Filtre ve Sıralama Kontrolleri --}}
        <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                {{-- Kategori Filtresi --}}
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Kategori:</label>
                    <select wire:model.live="secilenKategori" wire:change="kategoriDegistir"
                        class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tüm Kategoriler</option>
                        @foreach ($kategoriSecenekleri as $kategori)
                            <option value="{{ $kategori->value }}">{{ $kategori->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sıralama --}}
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Sıralama:</label>
                    <select wire:model.live="siralama" wire:change="siralamaDegistir"
                        class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="sira_asc">Sıra (A-Z)</option>
                        <option value="sira_desc">Sıra (Z-A)</option>
                        <option value="tarih_asc">Tarih (Eski-Yeni)</option>
                        <option value="tarih_desc">Tarih (Yeni-Eski)</option>
                        <option value="ana_resim">Ana Resim Önce</option>
                    </select>
                </div>

                {{-- İstatistikler --}}
                @if (!empty($galeriIstatistikleri))
                    <div class="ml-auto flex items-center space-x-4 text-sm text-gray-600">
                        <span>Toplam: {{ $galeriIstatistikleri['toplam_resim'] }}</span>
                        @if ($galeriIstatistikleri['ana_resim'])
                            <span class="text-green-600">
                                <i class="fas fa-star mr-1"></i>Ana resim var
                            </span>
                        @else
                            <span class="text-orange-600">
                                <i class="fas fa-star mr-1"></i>Ana resim yok
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Toplu İşlem Kontrolleri --}}
        @if ($topluIslemModuAktif)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-red-800">
                            {{ $this->seciliResimSayisi }} resim seçildi
                        </span>
                        <button wire:click="tumResimleriSec" class="text-sm text-red-600 hover:text-red-800 underline">
                            Tümünü Seç
                        </button>
                        <button wire:click="secimiTemizle" class="text-sm text-red-600 hover:text-red-800 underline">
                            Seçimi Temizle
                        </button>
                    </div>
                    <button wire:click="seciliResimleriSil"
                        wire:confirm="Seçili resimleri silmek istediğinizden emin misiniz?"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        @if (empty($secilenResimler)) disabled @endif>
                        <i class="fas fa-trash mr-2"></i>
                        Seçilenleri Sil
                    </button>
                </div>
            </div>
        @endif

        {{-- Galeri Grid --}}
        @if (empty($resimler))
            <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-12 text-center">
                <i class="fas fa-images text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Henüz resim yüklenmemiş</h3>
                <p class="text-gray-500">Bu mülk için galeri resimi bulunmamaktadır.</p>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4"
                @if ($siralamaModuAktif) x-data="{ 
                         sortable: null,
                         init() {
                             this.sortable = new Sortable(this.$el, {
                                 animation: 150,
                                 onEnd: (evt) => {
                                     let resimSiralari = Array.from(this.$el.children).map(el => 
                                         parseInt(el.dataset.resimId)
                                     );
                                     $wire.resimSiralamasiniGuncelle(resimSiralari);
                                 }
                             });
                         }
                     }" @endif>
                @foreach ($resimler as $resim)
                    <div class="relative group bg-white rounded-lg shadow-sm border overflow-hidden {{ $siralamaModuAktif ? 'cursor-move' : '' }}"
                        data-resim-id="{{ $resim['id'] }}">
                        {{-- Resim --}}
                        <div class="aspect-square relative">
                            <img src="{{ $resim['urls']['medium'] }}" alt="{{ $resim['baslik'] }}"
                                class="w-full h-full object-cover" loading="lazy">

                            {{-- Ana Resim Badge --}}
                            @if ($resim['ana_resim_mi'])
                                <div
                                    class="absolute top-2 left-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                    <i class="fas fa-star mr-1"></i>Ana
                                </div>
                            @endif

                            {{-- Sıra Numarası --}}
                            @if ($resim['sira'] > 0)
                                <div
                                    class="absolute top-2 right-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded-full text-xs">
                                    {{ $resim['sira'] }}
                                </div>
                            @endif

                            {{-- Toplu İşlem Checkbox --}}
                            @if ($topluIslemModuAktif)
                                <div class="absolute top-2 left-2">
                                    <input type="checkbox" wire:click="resimSec({{ $resim['id'] }})"
                                        @if (in_array($resim['id'], $secilenResimler)) checked @endif
                                        class="w-5 h-5 text-red-600 bg-white border-gray-300 rounded focus:ring-red-500">
                                </div>
                            @endif

                            {{-- Hover Overlay --}}
                            @if (!$siralamaModuAktif && !$topluIslemModuAktif)
                                <div
                                    class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center">
                                    <div
                                        class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex space-x-2">
                                        {{-- Detay Görüntüle --}}
                                        <button wire:click="resimDetayiGoster({{ $resim['id'] }})"
                                            class="p-2 bg-white text-gray-800 rounded-full hover:bg-gray-100 transition-colors"
                                            title="Detay Görüntüle">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        {{-- Ana Resim Yap --}}
                                        @if (!$resim['ana_resim_mi'])
                                            <button wire:click="anaResimBelirle({{ $resim['id'] }})"
                                                class="p-2 bg-yellow-500 text-white rounded-full hover:bg-yellow-600 transition-colors"
                                                title="Ana Resim Yap">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Resim Bilgileri --}}
                        <div class="p-3">
                            <h4 class="font-semibold text-sm text-gray-900 truncate">{{ $resim['baslik'] }}</h4>
                            <p class="text-xs text-gray-500 mt-1">{{ $resim['kategori']->label() }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $resim['created_at']->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- Resim Detay Modal --}}
    @if ($detayModalAcik && $secilenResim)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('detayModalAcik') }">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="detayModalKapat">
                </div>

                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $secilenResim['baslik'] }}</h3>
                            <button wire:click="detayModalKapat" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- Resim --}}
                            <div>
                                <img src="{{ $secilenResim['urls']['large'] }}" alt="{{ $secilenResim['baslik'] }}"
                                    class="w-full rounded-lg">
                            </div>

                            {{-- Bilgiler --}}
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kategori</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $secilenResim['kategori']->label() }}</p>
                                </div>

                                @if ($secilenResim['aciklama'])
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Açıklama</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $secilenResim['aciklama'] }}</p>
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Yükleme Tarihi</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $secilenResim['created_at']->format('d.m.Y H:i:s') }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Boyutlar</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $secilenResim['metadata']['genislik'] ?? 'N/A' }} x
                                        {{ $secilenResim['metadata']['yukseklik'] ?? 'N/A' }} px
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Dosya Boyutu</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ number_format($secilenResim['metadata']['boyut'] / 1024, 2) }} KB</p>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700">Ana Resim:</span>
                                    @if ($secilenResim['ana_resim_mi'])
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-star mr-1"></i>Evet
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Hayır
                                        </span>
                                    @endif
                                </div>

                                {{-- Aksiyon Butonları --}}
                                <div class="flex space-x-2 pt-4">
                                    @if (!$secilenResim['ana_resim_mi'])
                                        <button wire:click="anaResimBelirle({{ $secilenResim['id'] }})"
                                            class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                                            <i class="fas fa-star mr-2"></i>Ana Resim Yap
                                        </button>
                                    @endif

                                    <a href="{{ $secilenResim['urls']['original'] }}" download
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-download mr-2"></i>İndir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Mesaj Bildirimi --}}
    @if ($mesaj)
        <div class="fixed bottom-4 right-4 z-50" x-data="{ show: true }" x-show="show" x-transition>
            <div
                class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            @if ($mesajTipi === 'success')
                                <i class="fas fa-check-circle text-green-400"></i>
                            @elseif($mesajTipi === 'error')
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            @elseif($mesajTipi === 'warning')
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            @else
                                <i class="fas fa-info-circle text-blue-400"></i>
                            @endif
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900">{{ $mesaj }}</p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button @click="show = false"
                                class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush
