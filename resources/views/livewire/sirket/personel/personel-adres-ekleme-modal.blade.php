<x-custom-modal name="personel-adres-ekleme-modal" title="Personele Adres Ekle" size="400x500" :subtitle="$personel && $kisi ? $kisi->ad . ' ' . $kisi->soyad . ' için yeni adres' : null"
    save-action="addAdres" clear-action="clearForm" save-title="Kaydet" clear-title="Formu Temizle">

    @if ($personel && $kisi)


        <flux:fieldset class="border border-amber-200 mb-4">
            <!------------------ Adres Adı ------------------------------------------>
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>Adres Adı</flux:label>
                <flux:input size="sm" placeholder="Ev, İş, Ofis vb." wire:model.defer="adres_adi" />
            </div>
            <!------------------ Varsayılan Adres ------------------------------------>
            <div class="m-2">
                <flux:field variant="inline">
                    <flux:label>Varsayılan adres</flux:label>
                    <flux:switch wire:model.live="varsayilan_mi" />
                    <flux:error name="varsayilan_mi" />
                </flux:field>
            </div>
        </flux:fieldset>

        <flux:fieldset class="border border-amber-200 mb-4">
            <!------------------ İl Seçme -------------------------------------->
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>Şehir *</flux:label>
                <flux:select size="sm" wire:model.live="sehir_id">
                    <option value="">Şehir seçiniz</option>
                    @foreach ($sehirler as $sehir)
                        <option value="{{ $sehir->id }}">{{ $sehir->ad }}</option>
                    @endforeach
                </flux:select>
            </div>
            <!------------------ İlçe Seçme ------------------------->
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>İlçe *</flux:label>
                <flux:select size="sm" wire:model.live="ilce_id" :disabled="empty($sehir_id)">
                    <option value="">İlçe seçiniz</option>
                    @foreach ($ilceler as $ilce)
                        <option value="{{ $ilce->id }}">{{ $ilce->ad }}</option>
                    @endforeach
                </flux:select>
            </div>
            <!------------------ Semt Seçme ------------------------->
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>Semt *</flux:label>
                <flux:select size="sm" wire:model.live="semt_id" :disabled="empty($ilce_id)">
                    <option value="">Semt seçiniz</option>
                    @foreach ($semtler as $semt)
                        <option value="{{ $semt->id }}">{{ $semt->ad }}</option>
                    @endforeach
                </flux:select>
            </div>
            <!------------------ Mahalle Seçme ------------------------->
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>Mahalle *</flux:label>
                <flux:select size="sm" wire:model.live="mahalle_id" :disabled="empty($semt_id)">
                    <option value="">Mahalle seçiniz</option>
                    @foreach ($mahalleler as $mahalle)
                        <option value="{{ $mahalle->id }}">{{ $mahalle->ad }}</option>
                    @endforeach
                </flux:select>
            </div>
            <!------------------ Adres Detay ------------------------->
            <div class="m-2">
                <flux:textarea wire:model.defer="adres_detay" placeholder="Sokak, cadde, bina no, daire no vb."
                    rows="2" />
            </div>
        </flux:fieldset>
        <!------------------ Adres Aktif mi? ------------------------->
        <div class="m-2">
            <flux:field variant="inline">
                <flux:label>Adres Aktif mi?</flux:label>
                <flux:switch wire:model.defer.live="aktif_mi" />
                <flux:error name="aktif_mi" />
            </flux:field>
        </div>
    @else
        <div class="text-center py-8 text-gray-600">
            Adres eklenecek personel seçiniz.
        </div>
    @endif
</x-custom-modal>
