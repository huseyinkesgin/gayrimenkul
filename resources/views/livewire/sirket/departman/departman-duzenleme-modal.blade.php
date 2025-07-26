<flux:modal :name="'departman-duzenleme-modal'" class="md:w-[500px] max-w-lg" x-on:close="$wire.resetModal()">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Departman Düzenle</flux:heading>
            <flux:text class="mt-2">Departman bilgilerini düzenleyebilirsiniz.</flux:text>
        </div>
        
        @if($loading)
            <div class="text-center py-8">
                <flux:text>Yükleniyor...</flux:text>
            </div>
        @elseif($departman)
            <form wire:submit.prevent="updateDepartman" class="space-y-4">
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
                    <flux:button type="submit" variant="primary" size="sm">Güncelle</flux:button>
                </div>
            </form>
        @elseif($departmanId)
            <div class="text-center py-8">
                <flux:text>Departman bulunamadı.</flux:text>
            </div>
        @else
            <div class="text-center py-8">
                <flux:text>Düzenlenecek departman seçiniz.</flux:text>
            </div>
        @endif
    </div>
</flux:modal>
