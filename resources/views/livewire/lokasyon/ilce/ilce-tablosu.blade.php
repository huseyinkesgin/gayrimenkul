<div>
    <!-- İşlem Çubuğu -->
    <div class="grid grid-cols-12 gap-2 mb-2">

        <div class="col-span-2">
            <button x-on:click="$dispatch('open-modal', { name: 'ilce-ekleme-modal' })"
                    class="inline-flex items-center px-3 py-2 bg-amber-600 text-white text-sm font-medium rounded hover:bg-amber-700 focus:ring-amber-500">
                Yeni İlçe Ekle
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
            <th wire:click="sortBy('id')" class="px-2 cursor-pointer">#</th>
            <th wire:click="sortBy('sehir.ad')" class="px-2 cursor-pointer">Şehir Adı</th>
            <th wire:click="sortBy('ad')" class="px-2 cursor-pointer">İlçe Adı</th>
            <th wire:click="sortBy('aktif_mi')" class="px-2 cursor-pointer">Aktif Mi</th>
            <th class="px-2">İşlemler</th>
        </x-tablo.thead>
        <tbody wire:sortable="sortOrderUpdated" class="bg-white text-xs ">
            @foreach ($ilceler as $ilce)
                <tr wire:sortable.item="{{ $ilce->id }}" wire:key="ilce-{{ $ilce->id }}"
                    class="border border-amber-200 wire:sortable-item hover:bg-amber-200 cursor-pointer h-8">
                    <td class="px-2 py-2">
                        <input type="checkbox" value="{{ $ilce->id }}" wire:model.live="selected">
                    </td>
                    <td class="px-2 py-2">{{ $ilce->id }}</td>
                    <td class="px-2 py-2">{{ $ilce->sehir->ad }}</td>
                    <td class="px-2 py-2">{{ $ilce->ad }}</td>

                    <td class="px-2 py-2">
                        <flux:switch :checked="$ilce->aktif_mi" wire:click="toggleAktifMi('{{ $ilce->id }}')" />
                    </td>
                    <td class="px-2 py-2">
                        @if ($silinenleriGoster)
                            <flux:modal.trigger name="ilce-gerial-{{ $ilce->id }}" class="inline-block ml-2">
                                <flux:button variant="primary" size="xs">Geri Al</flux:button>
                            </flux:modal.trigger>
                            <flux:modal name="ilce-gerial-{{ $ilce->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">İlçeyi Geri Al</flux:heading>
                                        <flux:text class="mt-2">
                                            <p>Bu ilçeyi geri almak üzeresiniz.</p>
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost" size="sm">Vazgeç</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="button" variant="primary" size="sm"
                                            wire:click="geriAlItem('{{ $ilce->id }}')">Geri Al</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        @else
                            <button wire:click="editIlce('{{ $ilce->id }}')"
                                        x-on:click="$dispatch('open-modal', { name: 'ilce-duzenleme-modal' })"
                                        class="p-1 text-green-600 hover:text-green-800 hover:bg-green-100 rounded"
                                        title="Düzenle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            <flux:modal.trigger name="ilce-sil-{{ $ilce->id }}" class="inline-block ml-2">
                                <flux:button variant="danger" size="xs">Sil</flux:button>
                            </flux:modal.trigger>
                            <flux:modal name="ilce-sil-{{ $ilce->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">İlçeyi Sil</flux:heading>
                                        <flux:text class="mt-2">
                                            <p>Bu ilçeyi silmek üzeresiniz.</p>
                                            <p>Bu işlem geri alınamaz.</p>
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost" size="sm">Vazgeç</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="button" variant="danger" size="sm"
                                            wire:click="delete('{{ $ilce->id }}')">Sil</flux:button>
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
        {{ $ilceler->links() }}
        <div class="mt-4 flex items-center gap-2">
            <span class="text-xs text-gray-500">Silinenler:</span>
            <span
                class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-semibold">{{ $silinenSayisi }}</span>
            <button class="ml-2 px-2 py-1 text-xs bg-red-500 text-white rounded" wire:click="showSilinenler"
                @if ($silinenSayisi == 0) disabled @endif>Silinenleri Göster</button>
            @if ($silinenleriGoster)
                <button class="ml-2 px-2 py-1 text-xs bg-gray-400 text-white rounded" wire:click="hideSilinenler">Tümünü
                    Göster</button>
            @endif
        </div>

    </div>

      <!-- Modallar -->
    @livewire('lokasyon.ilce.ilce-ekleme-modal')
    @livewire('lokasyon.ilce.ilce-duzenleme-modal')
</div>
