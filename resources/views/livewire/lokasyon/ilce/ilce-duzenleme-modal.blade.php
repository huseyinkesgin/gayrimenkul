<x-custom-modal name="ilce-duzenleme-modal" title="İlçe Düzenle" size="500x280"
    :subtitle="$ilce ? $ilce->ad . ' ilcesini düzenliyorsunuz' : 'İlçe bilgilerini güncelleyebilirsiniz'"
    save-action="updateIlce" clear-action="clearForm"
    save-title="Güncelle" clear-title="Orijinal Değerleri Yükle">


        <div class="bg-white border border-amber-200 shadow-sm">
            <!-- İlçe Adı -->
            <x-form.form-alani ad="İlçe Adı *">
                <flux:input size="sm" placeholder="Örn: İstanbul, Ankara, İzmir..." wire:model.defer="ad"
                    autocomplete="off" />
                <flux:error name="ad" />
            </x-form.form-alani>

            <!-- Şehir Seçimi -->
            <x-form.form-alani ad="Şehir Seçimi *">
                <flux:select size="sm" wire:model.defer="sehir_id" placeholder="Şehir seçiniz...">
                    <option value="">Şehir Seçiniz</option>
                    @foreach ($sehirler as $sehir)
                        <option value="{{ $sehir->id }}">{{ $sehir->ad }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="sehir_id" />
            </x-form.form-alani>

            <!-- Aktif Durumu -->

            <x-form.form-alani ad="Durum">
                <flux:field variant="inline">
                    <flux:switch wire:model.live="aktif_mi" />
                    <flux:label class="ml-3">
                        <span class="flex items-center">
                            @if ($aktif_mi ?? true)
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                ✅ Aktif
                            @else
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                ❌ Pasif
                            @endif
                        </span>
                    </flux:label>
                    <flux:error name="aktif_mi" />
                </flux:field>
            </x-form.form-alani>
        </div>


</x-custom-modal>
