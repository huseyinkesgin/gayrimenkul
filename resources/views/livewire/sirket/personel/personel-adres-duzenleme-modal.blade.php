<x-custom-modal 
    name="personel-adres-duzenleme-modal"
    title="Adres Düzenle"
    size="400x500"
    :subtitle="$personel && $kisi && $adres ? $kisi->ad . ' ' . $kisi->soyad . ' - ' . $adres->adres_adi : 'Adres bilgilerini düzenleyebilirsiniz'"
    save-action="updateAdres"
    clear-action="clearForm"
    save-title="Güncelle"
    clear-title="Orijinal Değerleri Yükle">
    
    @if($loading)
        <div class="text-center py-8">
            <div class="inline-flex items-center px-4 py-2 bg-amber-100 text-amber-800 rounded-lg">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Yükleniyor...
            </div>
        </div>
    @elseif($adres)
      <flux:fieldset class="border border-amber-200 mb-4">
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>Adres Adı</flux:label>
                <flux:input size="sm" placeholder="Ev, İş, Ofis vb." wire:model.defer="adres_adi" />
            </div>
            <div class="m-2">
                <flux:field variant="inline">
                    <flux:label>Varsayılan adres</flux:label>
                    <flux:switch wire:model.live="varsayilan_mi" />
                    <flux:error name="varsayilan_mi" />
                </flux:field>
            </div>
        </flux:fieldset>
        <flux:fieldset class="border border-amber-200 mb-4">
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>Şehir *</flux:label>
                <flux:select size="sm" wire:model.live="sehir_id">
                    <option value="">Şehir seçiniz</option>
                    @foreach ($sehirler as $sehir)
                        <option value="{{ $sehir->id }}">{{ $sehir->ad }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>İlçe *</flux:label>
                <flux:select size="sm" wire:model.live="ilce_id" :disabled="empty($sehir_id)">
                    <option value="">İlçe seçiniz</option>
                    @foreach ($ilceler as $ilce)
                        <option value="{{ $ilce->id }}">{{ $ilce->ad }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>Semt *</flux:label>
                <flux:select size="sm" wire:model.live="semt_id" :disabled="empty($ilce_id)">
                    <option value="">Semt seçiniz</option>
                    @foreach ($semtler as $semt)
                        <option value="{{ $semt->id }}">{{ $semt->ad }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="grid grid-cols-2 gap-4 m-2">
                <flux:label>Mahalle *</flux:label>
                <flux:select size="sm" wire:model.live="mahalle_id" :disabled="empty($semt_id)">
                    <option value="">Mahalle seçiniz</option>
                    @foreach ($mahalleler as $mahalle)
                        <option value="{{ $mahalle->id }}">{{ $mahalle->ad }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="m-2">
                <flux:textarea wire:model.defer="adres_detay" placeholder="Sokak, cadde, bina no, daire no vb."
                    rows="2" />
            </div>
        </flux:fieldset>




        <div class="m-2">
            <flux:field variant="inline">
                <flux:label>Adres Aktif mi?</flux:label>
                <flux:switch wire:model.defer.live="aktif_mi" />
                <flux:error name="aktif_mi" />
            </flux:field>
        </div>
    @else
        <div class="text-center py-8">
            <div class="text-amber-600">Düzenlenecek adres bulunamadı.</div>
        </div>
    @endif
</x-custom-modal>