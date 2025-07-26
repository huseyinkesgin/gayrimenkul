<flux:modal :name="'pozisyon-ekleme-modal'" class="md:w-[500px] max-w-lg">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Yeni Pozisyon Ekle</flux:heading>
            <flux:text class="mt-2">Pozisyon bilgilerini ekleyebilirsiniz.</flux:text>
        </div>
        
        <form wire:submit.prevent="addPozisyon" class="space-y-4">
            <flux:input label="Pozisyon Adı *" placeholder="Pozisyon adı" wire:model.defer="ad"/>
            
            <flux:textarea label="Not" placeholder="Pozisyon notu" wire:model.defer="not" rows="3"/>
            
            <flux:input label="Sıralama" type="number" placeholder="0" wire:model.defer="siralama"/>
            
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
