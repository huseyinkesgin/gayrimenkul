<x-custom-modal name="ilce-ekleme-modal" title="Yeni İlçe Ekle" size="400x230"
    subtitle="Türkiye'deki ilçeleri sisteme ekleyebilirsiniz" save-action="addIlce" clear-action="formuTemizle"
    save-title="💾 Kaydet" clear-title="🔄 Formu Temizle">

    <!-- İlçe    Bilgileri Kartı -->
    <div class="bg-white border border-amber-200 shadow-sm">


        <!-- İlçe Adı -->
        <x-form.form-alani ad="İlçe Adı *">
            <flux:input size="xs" placeholder="Örn: İstanbul, Ankara, İzmir..." wire:model.defer="ad"
                autocomplete="off" />
            <flux:error name="ad" />
        </x-form.form-alani>

        <!-- Şehir Seçimi -->
        <x-form.form-alani ad="Şehir Seçimi *">
            <flux:select size="xs" wire:model.defer="sehir_id" placeholder="Şehir seçiniz...">
                <option class="text-xs" value="">Şehir Seçiniz</option>
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
