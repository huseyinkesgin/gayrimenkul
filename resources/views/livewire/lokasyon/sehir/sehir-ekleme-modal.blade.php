<x-custom-modal name="sehir-ekleme-modal" title="Yeni Şehir Ekle" size="500x320"
    subtitle="Türkiye'deki şehirleri sisteme ekleyebilirsiniz" save-action="kaydet" clear-action="formuTemizle"
    save-title="💾 Kaydet" clear-title="🔄 Formu Temizle">

    <!-- Şehir Bilgileri Kartı -->
    <div class="bg-white border border-amber-200 shadow-sm">

        <div class="p-6 space-y-6">
            <!-- Şehir Adı -->
            <x-form.form-alani ad="Şehir Adı *">
                <flux:input size="sm" placeholder="Örn: İstanbul, Ankara, İzmir..." wire:model.defer="ad"
                    autocomplete="off" />
                <flux:error name="ad" />
            </x-form.form-alani>

            <!-- Plaka ve Telefon Kodları -->

            <x-form.form-alani ad="Plaka Kodu *">
                <flux:input size="sm" mask="99" placeholder="01-81" wire:model.defer="plaka_kodu"
                    autocomplete="off" />
                <flux:error name="plaka_kodu" />
            </x-form.form-alani>

            <!-- Plaka ve Telefon Kodları -->

            <x-form.form-alani ad="Telefon Kodu">
                <flux:input size="sm" mask="999" placeholder="212, 312, 232..." wire:model.defer="telefon_kodu"
                    autocomplete="off" />
                <flux:error name="telefon_kodu" />
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
    </div>

</x-custom-modal>
