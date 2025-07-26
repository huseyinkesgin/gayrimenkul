<flux:modal :name="'semt-ekleme-modal'" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Yeni Semt Ekle</flux:heading>
            <flux:text class="mt-2">Semt bilgilerini ekleyebilirsiniz.</flux:text>
        </div>
        <form wire:submit.prevent="addSemt" class="space-y-4">
            <flux:select label="Şehir" wire:model.live="sehir_id" name="sehir_id">
                <option value="">Seçiniz</option>
                @foreach ($sehirler as $sehir)
                    <option value="{{ $sehir->id }}">{{ $sehir->ad }}</option>
                @endforeach
            </flux:select>
            @if(!empty($ilceler))
                <flux:select label="İlçe" wire:model.live="ilce_id" name="ilce_id">
                    <option value="">Seçiniz</option>
                    @foreach ($ilceler as $ilce)
                        <option value="{{ $ilce->id }}">{{ $ilce->ad }}</option>
                    @endforeach
                </flux:select>
                @endif
        
            <flux:input label="Semt Adı" placeholder="Semt adı" wire:model.defer="ad" name="ad" />

            <flux:input label="Not" placeholder="Not" wire:model.defer="not" name="not" />
            <div class="flex items-center gap-2">
                <label class="text-xs">Aktif mi?</label>
                <input type="checkbox" wire:model.defer="aktif_mi" name="aktif_mi" />
            </div>
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary" size="sm">Kaydet</flux:button>
            </div>
        </form>
    </div>
</flux:modal>
