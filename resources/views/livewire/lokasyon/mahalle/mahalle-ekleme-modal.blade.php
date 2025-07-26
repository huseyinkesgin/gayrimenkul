<flux:modal :name="'mahalle-ekleme-modal'" class="md:w-96">

    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Yeni Mahalle Ekle</flux:heading>
            <flux:text class="mt-2">Yeni mahalle bilgilerini giriniz.</flux:text>
        </div>

        <form wire:submit.prevent="addMahalle" class="space-y-4">
            <!-- Şehir Seçimi -->
            <flux:select label="Şehir" wire:model.live="sehir_id" name="sehir_id">
                <option value="">Seçiniz</option>
                @foreach ($sehirler as $sehir)
                    <option value="{{ $sehir->id }}">{{ $sehir->ad }}</option>
                @endforeach
            </flux:select>
            @if (!empty($ilceler))
                <flux:select label="İlçe" wire:model.live="ilce_id" name="ilce_id">
                    <option value="">Seçiniz</option>
                    @foreach ($ilceler as $ilce)
                        <option value="{{ $ilce->id }}">{{ $ilce->ad }}</option>
                    @endforeach
                </flux:select>
            @endif

            @if (!empty($semtler))
                <flux:select label="Semt" wire:model.live="semt_id" name="semt_id">
                    <option value="">Seçiniz</option>
                    @foreach ($semtler as $semt)
                        <option value="{{ $semt->id }}">{{ $semt->ad }}</option>
                    @endforeach
                </flux:select>
            @endif






            <!-- Mahalle Adı -->
            <flux:input label="Mahalle Adı" placeholder="Mahalle adı" wire:model.defer="ad" name="ad" />

            <!-- Posta Kodu -->
            <flux:input label="Posta Kodu" placeholder="Posta kodu" wire:model.defer="posta_kodu" name="posta_kodu" />

            <!-- Not -->
            <flux:textarea label="Not" placeholder="Not" wire:model.defer="not" name="not" rows="3" />

            <!-- Aktif Mi -->
            <div class="flex items-center gap-2">
                <flux:checkbox wire:model.defer="aktif_mi" name="aktif_mi" />
                <label class="text-sm">Aktif</label>
            </div>

            <div class="flex justify-end space-x-2">
                <flux:button type="button" variant="ghost" flux:modal.close>İptal</flux:button>
                <flux:button type="submit" variant="primary">Kaydet</flux:button>
            </div>
        </form>
    </div>
</flux:modal>
