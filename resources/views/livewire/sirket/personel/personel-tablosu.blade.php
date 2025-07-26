<div>
    <!-- İşlem Çubuğu -->
    <div class="grid grid-cols-12 gap-2 mb-2">
        <div class="col-span-2">
            <button x-on:click="$dispatch('open-modal', { name: 'personel-ekleme-modal' })"
                    class="inline-flex items-center px-2 py-1 bg-amber-600 text-white text-sm font-medium rounded hover:bg-amber-700 focus:ring-amber-500">
                Yeni Personel Ekle
            </button>
        </div>

        <div class="col-span-8">
            @if (is_array($selected) && count($selected) > 0)
                <div class="flex items-center gap-2 h-9">
                    <span class="text-xs text-gray-600">{{ count($selected) }} kayıt seçildi</span>
                    @if (count($selected) > 0)
                        <flux:button variant="danger" size="sm" wire:click="deleteSelected">
                            Seçili kayıtları sil
                        </flux:button>
                    @endif
                </div>
            @else
                <x-tablo.arama />
            @endif
        </div>
        <x-tablo.filtre-durum />
        <x-tablo.filtre-sayfalama />
    </div>

    <!-- Tablo -->
    <x-tablo.tablo>
        <x-tablo.thead>
            <th class="px-2">
                <flux:checkbox size="xs" wire:model.live="selectedPage" />
            </th>
            <th class="px-2">Avatar</th>
            <th wire:click="sortBy('personel_no')" class="px-2 cursor-pointer">Personel No</th>
            <th class="px-2">Ad Soyad</th>
            <th class="px-2">Şube</th>
            <th class="px-2">Departman</th>
            <th class="px-2">Pozisyon</th>
            <th wire:click="sortBy('calisma_durumu')" class="px-2 cursor-pointer">Çalışma Durumu</th>
            <th wire:click="sortBy('ise_baslama_tarihi')" class="px-2 cursor-pointer">İşe Başlama</th>
            <th class="px-2">İşlemler</th>
        </x-tablo.thead>
        <tbody wire:sortable="sortOrderUpdated" class="bg-white text-xs">
            @foreach ($personeller as $personel)
                <tr wire:sortable.item="{{ $personel->id }}" wire:key="personel-{{ $personel->id }}"
                    class="border border-amber-200 wire:sortable-item hover:bg-amber-200 cursor-pointer h-8">
                    <td class="px-2 py-2">
                        <input type="checkbox" value="{{ $personel->id }}" wire:model.live="selected">
                    </td>
                    <td class="px-2 py-2">
                        <div class="flex justify-center">
                            @if($personel->avatar)
                                <img src="{{ asset('storage/' . $personel->avatar->url) }}" 
                                     alt="{{ $personel->kisi ? $personel->kisi->ad . ' ' . $personel->kisi->soyad : 'Avatar' }}"
                                     class="w-8 h-8 rounded-full object-cover border-2 border-amber-200">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-xs font-semibold">
                                    @if($personel->kisi)
                                        {{ strtoupper(substr($personel->kisi->ad, 0, 1)) }}{{ strtoupper(substr($personel->kisi->soyad, 0, 1)) }}
                                    @else
                                        ?
                                    @endif
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-2 py-2">{{ $personel->personel_no }}</td>
                    <td class="px-2 py-2">
                        @if($personel->kisi)
                            {{ $personel->kisi->ad }} {{ $personel->kisi->soyad }}
                        @else
                            <span class="text-red-500">Kişi bulunamadı</span>
                        @endif
                    </td>
                    <td class="px-2 py-2">{{ $personel->sube?->ad ?? 'N/A' }}</td>
                    <td class="px-2 py-2">{{ $personel->departman?->ad ?? 'N/A' }}</td>
                    <td class="px-2 py-2">{{ $personel->pozisyon?->ad ?? 'N/A' }}</td>
                    <td class="px-2 py-2">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            @if($personel->calisma_durumu === 'Aktif') bg-green-100 text-green-700
                            @elseif($personel->calisma_durumu === 'Pasif') bg-yellow-100 text-yellow-700
                            @elseif($personel->calisma_durumu === 'İzinli') bg-blue-100 text-blue-700
                            @else bg-red-100 text-red-700
                            @endif">
                            {{ $personel->calisma_durumu }}
                        </span>
                    </td>
                    <td class="px-2 py-2">{{ $personel->ise_baslama_tarihi?->format('d.m.Y') ?? 'N/A' }}</td>
                    <td class="px-2 py-2">
                        @if ($silinenleriGoster)
                            <flux:modal.trigger name="personel-gerial-{{ $personel->id }}" class="inline-block ml-2">
                                <flux:button variant="primary" size="xs">Geri Al</flux:button>
                            </flux:modal.trigger>
                            <flux:modal name="personel-gerial-{{ $personel->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Personeli Geri Al</flux:heading>
                                        <flux:text class="mt-2">
                                            <p>Bu personeli geri almak üzeresiniz.</p>
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost" size="sm">Vazgeç</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="button" variant="primary" size="sm"
                                            wire:click="geriAlItem('{{ $personel->id }}')">Geri Al</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        @else
                            <div class="flex items-center gap-1">
                                <!-- Detay -->
                                <button wire:click="showPersonelDetay('{{ $personel->id }}')" 
                                        x-on:click="$dispatch('open-modal', { name: 'personel-detay-modal' })"
                                        class="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded" 
                                        title="Detay">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                
                                <!-- Düzenle -->
                                <button wire:click="editPersonel('{{ $personel->id }}')" 
                                        x-on:click="$dispatch('open-modal', { name: 'personel-duzenleme-modal' })"
                                        class="p-1 text-green-600 hover:text-green-800 hover:bg-green-100 rounded" 
                                        title="Düzenle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                
                                <!-- Adres Ekle -->
                                <button wire:click="addAdresForPersonel('{{ $personel->id }}')" 
                                        class="p-1 text-purple-600 hover:text-purple-800 hover:bg-purple-100 rounded" 
                                        title="Adres Ekle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </button>
                                
                                <!-- Sil -->
                                <flux:modal.trigger name="personel-sil-{{ $personel->id }}" class="inline-block">
                                    <button class="p-1 text-red-600 hover:text-red-800 hover:bg-red-100 rounded" 
                                            title="Sil">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </flux:modal.trigger>
                            </div>
                            <flux:modal name="personel-sil-{{ $personel->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Personeli Sil</flux:heading>
                                        <flux:text class="mt-2">
                                            <p>Bu personeli silmek üzeresiniz.</p>
                                            <p>Bu işlem geri alınamaz.</p>
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost" size="sm">Vazgeç</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="button" variant="danger" size="sm"
                                            wire:click="delete('{{ $personel->id }}')">Sil</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-tablo.tablo>

    <div class="mt-4">
        <!-- Sayfalama -->
        {{ $personeller->links() }}
        <div class="mt-4 flex items-center gap-2">
            <span class="text-xs text-gray-500">Silinenler:</span>
            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-semibold">{{ $silinenSayisi }}</span>
            <button class="ml-2 px-2 py-1 text-xs bg-red-500 text-white rounded" wire:click="showSilinenler"
                @if ($silinenSayisi == 0) disabled @endif>Silinenleri Göster</button>
            @if ($silinenleriGoster)
                <button class="ml-2 px-2 py-1 text-xs bg-gray-400 text-white rounded" wire:click="hideSilinenler">Tümünü Göster</button>
            @endif
        </div>
    </div>

    <!-- Modallar -->
    @livewire('sirket.personel.personel-detay-modal')
    @livewire('sirket.personel.personel-duzenleme-modal')
    @livewire('sirket.personel.personel-adres-ekleme-modal')
    @livewire('sirket.personel.personel-adres-duzenleme-modal')
</div>
