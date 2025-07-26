<x-custom-modal name="personel-ekleme-modal" title="Yeni Personel Ekle" size="900x780"
    subtitle="Personel ve kişi bilgilerini ekleyebilirsiniz" save-action="addPersonel" clear-action="clearForm"
    save-title="Kaydet" clear-title="Formu Temizle">


    <div class="grid grid-cols-2  gap-4">

        <flux:fieldset class="border border-amber-500 mb-4">
            <!------------------ Ad ------------------------->
            <x-form.form-alani ad="Ad">
            <flux:input size="xs" placeholder="Ad" wire:model.defer="ad" autocomplete="off" />
            </x-form.form-alani>
            <!------------------ Soyad ------------------------->
            <x-form.form-alani ad="Soyad">
                <flux:input size="xs" placeholder="Soyad" wire:model.defer="soyad" autocomplete="off" />
            </x-form.form-alani>
            <!------------------ TC Kimlik No ------------------------->
            <x-form.form-alani ad="TC Kimlik No">
                <flux:input
                    autocomplete="off"
                size="xs" mask="99999999999" placeholder="12345678901"
                    wire:model.defer="tc_kimlik_no" />
            </x-form.form-alani>
            <!------------------ Doğum Tarihi ------------------------->
            <x-form.form-alani ad="Doğum Tarihi">
                <flux:input
                autocomplete="off"
                placeholder="GG/AA/YYYY"
                size="xs" wire:model.defer="dogum_tarihi" mask="99/99/9999" />
            </x-form.form-alani>
            <!------------------ Cinsiyet ------------------------->
            <x-form.form-alani ad="Cinsiyet">
                <flux:select size="xs" wire:model.defer="cinsiyet">
                    <option value="">Seçiniz</option>
                    <option value="Erkek">Erkek</option>
                    <option value="Kadın">Kadın</option>
                    <option value="Diğer">Diğer</option>
                </flux:select>
            </x-form.form-alani>
            <!------------------ Doğum Yeri ------------------------->
            <x-form.form-alani ad="Doğum Yeri">
                <flux:input
                autocomplete="off"
                size="xs" placeholder="Doğum yeri" wire:model.defer="dogum_yeri" />
            </x-form.form-alani>
            <!------------------ Medeni Hali ------------------------->
            <x-form.form-alani ad="Medeni Hali">
                <flux:select size="xs" wire:model.defer="medeni_hali">
                    <option value="">Seçiniz</option>
                    <option value="Bekar">Bekar</option>
                    <option value="Evli">Evli</option>
                    <option value="Dul">Dul</option>
                    <option value="Boşanmış">Boşanmış</option>
            </flux:select>
            </x-form.form-alani>

            <!------------------ Email ------------------------->
            <x-form.form-alani ad="Email">
                <flux:input size="xs" type="email" placeholder="email@example.com"
                    wire:model.defer="email" autocomplete="off" />
            </x-form.form-alani>
            <!------------------ Telefon ------------------------->
            <x-form.form-alani ad="Telefon">
                <flux:input size="xs" placeholder="Telefon" wire:model.defer="telefon" autocomplete="off" />
            </x-form.form-alani>
        </flux:fieldset>


        <!-- Adres Bilgileri -->
        <flux:fieldset class="border border-amber-200 mb-4">
            <!------------------ Adres Adı ------------------------------------------>
            <x-form.form-alani ad="Adres Adı">
                <flux:input size="xs" placeholder="Ev, İş, Ofis vb." wire:model.defer="adres_adi" autocomplete="off" />
            </x-form.form-alani>
            <!------------------ Varsayılan Adres ------------------------------------>
            <x-form.form-alani ad="Varsayılan Adres">
                <flux:field variant="inline">
                    <flux:switch wire:model.live="varsayilan_mi" />
                    <flux:error name="varsayilan_mi" />
                </flux:field>
            </x-form.form-alani>
            <!------------------ İl Seçme -------------------------------------->
            <x-form.form-alani ad="Şehir">
                <flux:select size="xs" wire:model.live="sehir_id">
                    <option value="">Şehir seçiniz</option>
                    @foreach ($sehirler as $sehir)
                        <option value="{{ $sehir->id }}">{{ $sehir->ad }}</option>
                    @endforeach
                </flux:select>
            </x-form.form-alani>
            <!------------------ İlçe Seçme ------------------------->
            <x-form.form-alani ad="İlçe">
                <flux:select size="xs" wire:model.live="ilce_id" :disabled="empty($sehir_id)">
                    <option value="">İlçe seçiniz</option>
                    @foreach ($ilceler as $ilce)
                        <option value="{{ $ilce->id }}">{{ $ilce->ad }}</option>
                    @endforeach
                </flux:select>
            </x-form.form-alani>
            <!------------------ Semt Seçme ------------------------->
            <x-form.form-alani ad="Semt">
                <flux:select size="xs" wire:model.live="semt_id" :disabled="empty($ilce_id)">
                    <option value="">Semt seçiniz</option>
                    @foreach ($semtler as $semt)
                        <option value="{{ $semt->id }}">{{ $semt->ad }}</option>
                    @endforeach
                </flux:select>
            </x-form.form-alani>
            <!------------------ Mahalle Seçme ------------------------->
            <x-form.form-alani ad="Mahalle">
                <flux:select size="xs" wire:model.live="mahalle_id" :disabled="empty($semt_id)">
                    <option value="">Mahalle seçiniz</option>
                    @foreach ($mahalleler as $mahalle)
                        <option value="{{ $mahalle->id }}">{{ $mahalle->ad }}</option>
                    @endforeach
                </flux:select>
            </x-form.form-alani>
            <!------------------ Adres Detay ------------------------->
            <x-form.form-alani ad="Adres Detay">
                <flux:textarea
                    wire:model.defer="adres_detay"
                    placeholder="Sokak, cadde, bina no, daire no vb."
                    rows="2"
                    autocomplete="off"
                />
            </x-form.form-alani>
            <x-form.form-alani ad="Adres Aktif mi?">
                <flux:field variant="inline">
                    <flux:switch wire:model.defer.live="aktif_mi" />
                    <flux:error name="aktif_mi" />
                </flux:field>
            </x-form.form-alani>
        </flux:fieldset>




        <!-- Personel Bilgileri -->
        <flux:fieldset class="border border-amber-200 mb-4">
            <!------------------ Şube Seçme ------------------------->
            <x-form.form-alani ad="Şube">
                <flux:select size="xs" wire:model.defer="sube_id">
                    <option value="">Şube seçiniz</option>
                    @foreach ($subeler as $sube)
                        <option value="{{ $sube->id }}">{{ $sube->ad }}</option>
                    @endforeach
                </flux:select>
            </x-form.form-alani>
            <!------------------ Departman Seçme ------------------------->
            <x-form.form-alani ad="Departman">
                <flux:select size="xs" wire:model.defer="departman_id">
                    <option value="">Departman seçiniz</option>
                    @foreach ($departmanlar as $departman)
                        <option value="{{ $departman->id }}">{{ $departman->ad }}</option>
                    @endforeach
                </flux:select>
            </x-form.form-alani>
            <!------------------ Pozisyon Seçme ------------------------->
            <x-form.form-alani ad="Pozisyon">
                <flux:select size="xs" wire:model.defer="pozisyon_id">
                    <option value="">Pozisyon seçiniz</option>
                    @foreach ($pozisyonlar as $pozisyon)
                        <option value="{{ $pozisyon->id }}">{{ $pozisyon->ad }}</option>
                    @endforeach
                </flux:select>
            </x-form.form-alani>
            <!------------------ Personel No ------------------------->
            <x-form.form-alani ad="Personel No">
                <flux:input size="xs" placeholder="Personel numarası" wire:model.defer="personel_no" autocomplete="off" />
            </x-form.form-alani>
            <!------------------ İşe Başlama Tarihi ------------------------->
            <x-form.form-alani ad="İşe Başlama Tarihi">
                <flux:input size="xs" mask="99/99/9999" wire:model.defer="ise_baslama_tarihi" autocomplete="off" />
            </x-form.form-alani>
            <!------------------ İşten Ayrılma Tarihi ------------------------->
            <x-form.form-alani ad="İşten Ayrılma Tarihi">
                <flux:input size="xs" mask="99/99/9999" wire:model.defer="isten_ayrilma_tarihi" autocomplete="off" />
            </x-form.form-alani>
            <!------------------ Çalışma Durumu ------------------------->
            <x-form.form-alani ad="Çalışma Durumu">
                <flux:select size="xs" wire:model.defer="calisma_durumu">
                    <option value="Aktif">Aktif</option>
                    <option value="Pasif">Pasif</option>
                    <option value="İzinli">İzinli</option>
                    <option value="Ayrılmış">Ayrılmış</option>
                </flux:select>
            </x-form.form-alani>
            <!------------------ Çalışma Şekli ------------------------->
            <x-form.form-alani ad="Çalışma Şekli">
                <flux:select size="xs" wire:model.defer="calisma_sekli">
                    <option value="Tam Zamanlı">Tam Zamanlı</option>
                    <option value="Yarı Zamanlı">Yarı Zamanlı</option>
                    <option value="Sözleşmeli">Sözleşmeli</option>
                </flux:select>
            </x-form.form-alani>
            <!------------------ Roller ------------------------->
           <x-form.form-alani ad="Roller">
               <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                   @foreach ($roller as $rol)
                       <label class="flex items-center text-xs">
                           <input type="checkbox" value="{{ $rol->id }}" wire:model.defer="selected_roller"
                                class="mr-2 text-amber-600 focus:ring-amber-500">
                            {{ $rol->ad }}
                        </label>
                    @endforeach
                </div>
           </x-form.form-alani>
        </flux:fieldset>


        <flux:fieldset class="border border-amber-200 mb-4">
             <!-- Avatar Container -->
                <div class="flex items-start space-x-6">
                    <!-- Main Avatar Display -->
                    <div class="relative group">
                        <div class="relative w-32 h-32 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 border-4 border-white dark:border-gray-700 shadow-lg">
                            @if ($photo)
                                <!-- Preview of new photo -->
                                <img src="{{ $photo->temporaryUrl() }}"
                                     alt="{{ __('Photo Preview') }}"
                                     class="w-full h-full object-cover">
                            @elseif ($currentAvatar)
                                <!-- Current avatar -->
                                <img src="{{ $currentAvatar }}"
                                     alt="{{ __('Profile Photo') }}"
                                     class="w-full h-full object-cover">
                            @else
                                <!-- Placeholder with initials -->
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-500 text-white">
                                    <span class="text-3xl font-semibold">{{ auth()->user()->initials() }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 rounded-full bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center cursor-pointer">
                            <div class="text-white text-center">
                                <svg class="w-8 h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="text-xs font-medium">{{ __('Change Photo') }}</span>
                            </div>
                        </div>

                        <!-- Hidden File Input -->
                        <input type="file"
                               wire:model="photo"
                               accept="image/*"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer rounded-full">
                    </div>

                    <!-- Photo Info and Actions -->
                    <div class="flex-1 space-y-3">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Profile Picture') }}
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('Click on the image to upload a new photo. JPG, PNG or GIF. Max size 1MB.') }}
                            </p>
                        </div>

                        @if ($photo)
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm text-blue-700 dark:text-blue-300">
                                        {{ __('New photo selected. Save to apply changes.') }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        @if ($currentAvatar && !$photo)
                            <button type="button"
                                    wire:click="removeAvatar"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                {{ __('Remove Photo') }}
                            </button>
                        @endif
                    </div>
                </div>

        </flux:fieldset>



    </div>
</x-custom-modal>
