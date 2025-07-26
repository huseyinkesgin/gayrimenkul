<flux:modal :name="'sube-ekleme-modal'" class="md:w-[600px] max-w-2xl">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Yeni Şube Ekle</flux:heading>
            <flux:text class="mt-2">Şube bilgilerini ekleyebilirsiniz.</flux:text>
        </div>
        <form wire:submit.prevent="addSube" class="space-y-4">
            <!-- Şube Bilgileri -->
            <div class="border-b pb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Şube Bilgileri</h3>
                <div class="space-y-3">
                    <flux:input label="Şube Adı *" placeholder="Şube adı" wire:model.defer="ad"/>
                    <flux:input label="Kod" placeholder="Kod" wire:model.defer="kod"/>
                    <flux:input label="Telefon" placeholder="Telefon" wire:model.defer="telefon"/>
                    <flux:input label="Email" placeholder="Email" wire:model.defer="email"/>
                </div>
            </div>

            <!-- Adres Bilgileri -->
            <div class="border-b pb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Adres Bilgileri</h3>
                <div class="space-y-3">
                    <flux:input label="Adres Adı *" placeholder="Ev, İş, Ofis vb." wire:model.defer="adres_adi"/>
                    <flux:textarea label="Adres Detayı *" placeholder="Sokak, cadde, bina no, daire no vb." wire:model.defer="adres_detay" rows="2"/>
                    <flux:input label="Posta Kodu" placeholder="Posta kodu" wire:model.defer="posta_kodu"/>
                    
                    <flux:select label="Şehir *" wire:model.live="sehir_id">
                        <option value="">Şehir seçiniz</option>
                        @foreach ($sehirler as $sehir)
                            <option value="{{ $sehir->id }}">{{ $sehir->ad }}</option>
                        @endforeach
                    </flux:select>

                    @if (!empty($ilceler) && count($ilceler) > 0)
                        <flux:select label="İlçe" wire:model.live="ilce_id">
                            <option value="">İlçe seçiniz</option>
                            @foreach ($ilceler as $ilce)
                                <option value="{{ $ilce->id }}">{{ $ilce->ad }}</option>
                            @endforeach
                        </flux:select>
                    @endif

                    @if (!empty($semtler) && count($semtler) > 0)
                        <flux:select label="Semt" wire:model.live="semt_id">
                            <option value="">Semt seçiniz</option>
                            @foreach ($semtler as $semt)
                                <option value="{{ $semt->id }}">{{ $semt->ad }}</option>
                            @endforeach
                        </flux:select>
                    @endif

                    @if (!empty($mahalleler) && count($mahalleler) > 0)
                        <flux:select label="Mahalle" wire:model.live="mahalle_id">
                            <option value="">Mahalle seçiniz</option>
                            @foreach ($mahalleler as $mahalle)
                                <option value="{{ $mahalle->id }}">{{ $mahalle->ad }}</option>
                            @endforeach
                        </flux:select>
                    @endif

                    <div class="flex items-center gap-2">
                        <flux:checkbox wire:model.defer="varsayilan_mi" label="Varsayılan adres" />
                    </div>
                </div>
            </div>

            <!-- Diğer Bilgiler -->
            <div class="space-y-3">
                <flux:textarea label="Notlar" placeholder="Ek notlar" wire:model.defer="not" rows="2"/>
                <div class="flex items-center gap-2">
                    <flux:checkbox wire:model.defer="aktif_mi" label="Aktif" />
                </div>
            </div>      <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary" size="sm">Kaydet</flux:button>
            </div>
        </form>
    </div>
</flux:modal>
