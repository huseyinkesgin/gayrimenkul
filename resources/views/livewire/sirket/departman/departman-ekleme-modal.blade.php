<flux:modal :name="'departman-ekleme-modal'" class="md:w-[500px] max-w-lg">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Yeni Departman Ekle</flux:heading>
            <flux:text class="mt-2">Departman bilgilerini ekleyebilirsiniz.</flux:text>
        </div>
        
        <form wire:submit.prevent="addDepartman" class="space-y-4">
            <flux:input label="Departman Adı *" placeholder="Departman adı" wire:model.defer="ad"/>
            
            <flux:textarea label="Açıklama" placeholder="Departman açıklaması" wire:model.defer="aciklama" rows="3"/>
            
            <flux:select label="Yönetici" wire:model.defer="yonetici_id">
                <option value="">Yönetici seçiniz</option>
                @foreach ($personeller as $personel)
                    <option value="{{ $personel->id }}">{{ $personel->kisi->ad }} {{ $personel->kisi->soyad }}</option>
                @endforeach
            </flux:select>
            
            <div class="flex items-center gap-2">
                <flux:checkbox wire:model.defer="aktif_mi" label="Aktif" />
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary" size="sm">Kaydet</flux:button>
            </div>
        </form>
    </div>
</flux:modal>
