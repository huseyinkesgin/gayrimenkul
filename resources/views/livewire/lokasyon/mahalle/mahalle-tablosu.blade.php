<div>
    <!-- İşlem Çubuğu -->
    <div class="grid grid-cols-12 gap-2 mb-2">

        <div class="col-span-2">
            <flux:modal.trigger name="mahalle-ekleme-modal" class="inline-block">
                <flux:button variant="primary" size="sm">Yeni Mahalle Ekle</flux:button>
            </flux:modal.trigger>
            @livewire('lokasyon.mahalle.mahalle-ekleme-modal')
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
            <th wire:click="sortBy('sehir.ad')" class="px-2 cursor-pointer">İlçe Adı</th>
            <th wire:click="sortBy('ad')" class="px-2 cursor-pointer">Semt Adı</th>
            <th wire:click="sortBy('mahalle.ad')" class="px-2 cursor-pointer">Mahalle Adı</th>
            <th wire:click="sortBy('aktif_mi')" class="px-2 cursor-pointer">Aktif Mi</th>
            <th class="px-2">İşlemler</th>
        </x-tablo.thead>
        <tbody wire:sortable="sortOrderUpdated" class="bg-white text-xs ">
            @foreach ($mahalleler as $mahalle)
                <tr wire:sortable.item="{{ $mahalle->id }}" wire:key="mahalle-{{ $mahalle->id }}"
                    class="border border-amber-200 wire:sortable-item hover:bg-amber-200 cursor-pointer h-8">
                    <td class="px-2 py-2">
                        <input type="checkbox" value="{{ $mahalle->id }}" wire:model.live="selected">
                    </td>
                    <td class="px-2 py-2">{{ $mahalle->id }}</td>
                    <td class="px-2 py-2">{{ $mahalle->semt->ilce->sehir->ad }}</td>
                    <td class="px-2 py-2">{{ $mahalle->semt->ilce->ad }}</td>
                    <td class="px-2 py-2">{{ $mahalle->semt->ad }}</td>
                    <td class="px-2 py-2">{{ $mahalle->ad }}</td>

                    <td class="px-2 py-2">
                        <flux:switch :checked="$mahalle->aktif_mi" wire:click="toggleAktifMi('{{ $mahalle->id }}')" />
                    </td>
                    <td class="px-2 py-2">
                        @if ($silinenleriGoster)
                            <flux:modal.trigger name="mahalle-gerial-{{ $mahalle->id }}" class="inline-block ml-2">
                                <flux:button variant="primary" size="xs">Geri Al</flux:button>
                            </flux:modal.trigger>
                            <flux:modal name="mahalle-gerial-{{ $mahalle->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Mahalleyi Geri Al</flux:heading>
                                        <flux:text class="mt-2">
                                            <p>Bu mahalleyi geri almak üzeresiniz.</p>
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost" size="sm">Vazgeç</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="button" variant="primary" size="sm"
                                            wire:click="geriAlItem('{{ $mahalle->id }}')">Geri Al</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        @else
                            <flux:modal.trigger name="duzenle-mahalle-{{ $mahalle->id }}" class="inline-block">
                                <flux:button size="xs">Düzenle</flux:button>
                            </flux:modal.trigger>
                            @livewire('lokasyon.mahalle.mahalle-duzenleme-modal', ['mahalle' => $mahalle], key('duzenle-mahalle-' . $mahalle->id))
                            <flux:modal.trigger name="mahalle-sil-{{ $mahalle->id }}" class="inline-block ml-2">
                                <flux:button variant="danger" size="xs">Sil</flux:button>
                            </flux:modal.trigger>
                            <flux:modal name="mahalle-sil-{{ $mahalle->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Mahalleyi Sil</flux:heading>
                                        <flux:text class="mt-2">
                                            <p>Bu mahalleyi silmek üzeresiniz.</p>
                                            <p>Bu işlem geri alınamaz.</p>
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost" size="sm">Vazgeç</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="button" variant="danger" size="sm"
                                            wire:click="delete('{{ $mahalle->id }}')">Sil</flux:button>
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
        {{ $mahalleler->links() }}
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
</div>
