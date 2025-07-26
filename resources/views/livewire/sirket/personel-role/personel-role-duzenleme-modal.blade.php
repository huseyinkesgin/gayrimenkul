<flux:modal :name="'personel-role-duzenleme-modal'" class="md:w-[500px] max-w-lg" x-on:close="$wire.resetModal()">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Personel Rolü Düzenle</flux:heading>
            <flux:text class="mt-2">Personel rolü bilgilerini düzenleyebilirsiniz.</flux:text>
        </div>
        
        @if($loading)
            <div class="text-center py-8">
                <flux:text>Yükleniyor...</flux:text>
            </div>
        @elseif($personelRole)
            <form wire:submit.prevent="updatePersonelRole" class="space-y-4">
                <flux:input label="Rol Adı *" placeholder="Rol adı" wire:model.defer="ad"/>
                
                <flux:textarea label="Not" placeholder="Rol notu" wire:model.defer="not" rows="3"/>
                
                <flux:input label="Sıralama" type="number" placeholder="0" wire:model.defer="siralama"/>
                
                <div class="flex items-center gap-2">
                    <flux:checkbox wire:model.defer="aktif_mi" label="Aktif" />
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary" size="sm">Güncelle</flux:button>
                </div>
            </form>
        @elseif($personelRoleId)
            <div class="text-center py-8">
                <flux:text>Personel rolü bulunamadı.</flux:text>
            </div>
        @else
            <div class="text-center py-8">
                <flux:text>Düzenlenecek personel rolü seçiniz.</flux:text>
            </div>
        @endif
    </div>
</flux:modal>
