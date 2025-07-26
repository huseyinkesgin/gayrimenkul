<?php

namespace App\Helpers;

use App\Enums\HizmetTipi;
use App\Enums\HizmetSonucu;
use App\Enums\DegerlendirmeTipi;

class HizmetFormHelper
{
    /**
     * Hizmet tipine göre form alanları oluştur
     */
    public function generateFormFields(HizmetTipi $hizmetTipi, array $currentValues = []): array
    {
        $baseFields = $this->getBaseFields($currentValues);
        $specificFields = $this->getTypeSpecificFields($hizmetTipi, $currentValues);
        
        return array_merge($baseFields, $specificFields);
    }

    /**
     * Temel form alanları
     */
    private function getBaseFields(array $currentValues = []): array
    {
        return [
            'musteri_id' => [
                'type' => 'select',
                'label' => 'Müşteri',
                'required' => true,
                'value' => $currentValues['musteri_id'] ?? null,
                'options' => [], // Livewire component'te doldurulacak
                'attributes' => [
                    'wire:model' => 'form.musteri_id',
                    'class' => 'form-select',
                ],
            ],
            'hizmet_tipi' => [
                'type' => 'select',
                'label' => 'Hizmet Tipi',
                'required' => true,
                'value' => $currentValues['hizmet_tipi'] ?? null,
                'options' => $this->getHizmetTipiOptions(),
                'attributes' => [
                    'wire:model.live' => 'form.hizmet_tipi',
                    'class' => 'form-select',
                ],
            ],
            'hizmet_tarihi' => [
                'type' => 'datetime-local',
                'label' => 'Hizmet Tarihi',
                'required' => true,
                'value' => $currentValues['hizmet_tarihi'] ?? now()->format('Y-m-d\TH:i'),
                'attributes' => [
                    'wire:model' => 'form.hizmet_tarihi',
                    'class' => 'form-input',
                ],
            ],
            'aciklama' => [
                'type' => 'textarea',
                'label' => 'Açıklama',
                'required' => false,
                'value' => $currentValues['aciklama'] ?? '',
                'attributes' => [
                    'wire:model' => 'form.aciklama',
                    'class' => 'form-textarea',
                    'rows' => 3,
                    'placeholder' => 'Hizmet detaylarını açıklayın...',
                ],
            ],
            'mulk_id' => [
                'type' => 'select',
                'label' => 'İlgili Mülk (Opsiyonel)',
                'required' => false,
                'value' => $currentValues['mulk_id'] ?? null,
                'options' => [], // Livewire component'te doldurulacak
                'attributes' => [
                    'wire:model' => 'form.mulk_id',
                    'class' => 'form-select',
                ],
            ],
        ];
    }

    /**
     * Hizmet tipine özel alanlar
     */
    private function getTypeSpecificFields(HizmetTipi $hizmetTipi, array $currentValues = []): array
    {
        $fields = [];

        // Süre gerektiren hizmetler
        if ($hizmetTipi->requiresDuration()) {
            $fields['sure_dakika'] = [
                'type' => 'number',
                'label' => 'Süre (Dakika)',
                'required' => true,
                'value' => $currentValues['sure_dakika'] ?? null,
                'attributes' => [
                    'wire:model' => 'form.sure_dakika',
                    'class' => 'form-input',
                    'min' => 1,
                    'max' => 1440,
                    'placeholder' => 'Örn: 30',
                ],
            ];

            $fields['bitis_tarihi'] = [
                'type' => 'datetime-local',
                'label' => 'Bitiş Tarihi (Opsiyonel)',
                'required' => false,
                'value' => $currentValues['bitis_tarihi'] ?? null,
                'attributes' => [
                    'wire:model' => 'form.bitis_tarihi',
                    'class' => 'form-input',
                ],
            ];
        }

        // Lokasyon gerektiren hizmetler
        if ($hizmetTipi->requiresLocation()) {
            $fields['lokasyon'] = [
                'type' => 'text',
                'label' => 'Lokasyon',
                'required' => true,
                'value' => $currentValues['lokasyon'] ?? '',
                'attributes' => [
                    'wire:model' => 'form.lokasyon',
                    'class' => 'form-input',
                    'placeholder' => 'Örn: Ofis, Müşteri Adresi, Mülk Lokasyonu',
                ],
            ];
        }

        // Katılımcı gerektiren hizmetler
        if ($hizmetTipi->requiresParticipants()) {
            $fields['katilimcilar'] = [
                'type' => 'tags',
                'label' => 'Katılımcılar',
                'required' => true,
                'value' => $currentValues['katilimcilar'] ?? [],
                'attributes' => [
                    'wire:model' => 'form.katilimcilar',
                    'class' => 'form-tags',
                    'placeholder' => 'Katılımcı adı yazın ve Enter\'a basın',
                ],
            ];
        }

        // Hizmet tipine özel alanlar
        switch ($hizmetTipi) {
            case HizmetTipi::TELEFON:
                $fields['telefon_numarasi'] = [
                    'type' => 'tel',
                    'label' => 'Aranan Numara',
                    'required' => false,
                    'value' => $currentValues['telefon_numarasi'] ?? '',
                    'attributes' => [
                        'wire:model' => 'form.telefon_numarasi',
                        'class' => 'form-input',
                        'placeholder' => '+90 555 123 45 67',
                    ],
                ];
                break;

            case HizmetTipi::EMAIL:
                $fields['email_konusu'] = [
                    'type' => 'text',
                    'label' => 'E-posta Konusu',
                    'required' => false,
                    'value' => $currentValues['email_konusu'] ?? '',
                    'attributes' => [
                        'wire:model' => 'form.email_konusu',
                        'class' => 'form-input',
                        'placeholder' => 'E-posta konusunu girin',
                    ],
                ];
                break;

            case HizmetTipi::SUNUM:
                $fields['sunum_konusu'] = [
                    'type' => 'text',
                    'label' => 'Sunum Konusu',
                    'required' => true,
                    'value' => $currentValues['sunum_konusu'] ?? '',
                    'attributes' => [
                        'wire:model' => 'form.sunum_konusu',
                        'class' => 'form-input',
                        'placeholder' => 'Sunum konusunu girin',
                    ],
                ];

                $fields['sunum_materyalleri'] = [
                    'type' => 'tags',
                    'label' => 'Sunum Materyalleri',
                    'required' => false,
                    'value' => $currentValues['sunum_materyalleri'] ?? [],
                    'attributes' => [
                        'wire:model' => 'form.sunum_materyalleri',
                        'class' => 'form-tags',
                        'placeholder' => 'Materyal adı yazın ve Enter\'a basın',
                    ],
                ];
                break;

            case HizmetTipi::VIDEO_GORUSME:
                $fields['platform'] = [
                    'type' => 'select',
                    'label' => 'Platform',
                    'required' => false,
                    'value' => $currentValues['platform'] ?? '',
                    'options' => [
                        '' => 'Platform Seçin',
                        'zoom' => 'Zoom',
                        'teams' => 'Microsoft Teams',
                        'meet' => 'Google Meet',
                        'skype' => 'Skype',
                        'whatsapp' => 'WhatsApp Video',
                        'diger' => 'Diğer',
                    ],
                    'attributes' => [
                        'wire:model' => 'form.platform',
                        'class' => 'form-select',
                    ],
                ];
                break;
        }

        return $fields;
    }

    /**
     * Sonuç alanları
     */
    public function getSonucFields(array $currentValues = []): array
    {
        return [
            'sonuc' => [
                'type' => 'textarea',
                'label' => 'Hizmet Sonucu',
                'required' => false,
                'value' => $currentValues['sonuc'] ?? '',
                'attributes' => [
                    'wire:model' => 'form.sonuc',
                    'class' => 'form-textarea',
                    'rows' => 3,
                    'placeholder' => 'Hizmet sonucunu detaylı olarak açıklayın...',
                ],
            ],
            'sonuc_tipi' => [
                'type' => 'select',
                'label' => 'Sonuç Tipi',
                'required' => false,
                'value' => $currentValues['sonuc_tipi'] ?? '',
                'options' => $this->getSonucTipiOptions(),
                'attributes' => [
                    'wire:model' => 'form.sonuc_tipi',
                    'class' => 'form-select',
                ],
            ],
            'takip_tarihi' => [
                'type' => 'datetime-local',
                'label' => 'Takip Tarihi',
                'required' => false,
                'value' => $currentValues['takip_tarihi'] ?? null,
                'attributes' => [
                    'wire:model' => 'form.takip_tarihi',
                    'class' => 'form-input',
                ],
            ],
            'takip_notu' => [
                'type' => 'textarea',
                'label' => 'Takip Notu',
                'required' => false,
                'value' => $currentValues['takip_notu'] ?? '',
                'attributes' => [
                    'wire:model' => 'form.takip_notu',
                    'class' => 'form-textarea',
                    'rows' => 2,
                    'placeholder' => 'Takip için not ekleyin...',
                ],
            ],
        ];
    }

    /**
     * Değerlendirme alanları
     */
    public function getDegerlendirmeFields(array $currentValues = []): array
    {
        return [
            'degerlendirme.tip' => [
                'type' => 'select',
                'label' => 'Değerlendirme Tipi',
                'required' => false,
                'value' => $currentValues['degerlendirme']['tip'] ?? '',
                'options' => $this->getDegerlendirmeTipiOptions(),
                'attributes' => [
                    'wire:model.live' => 'form.degerlendirme.tip',
                    'class' => 'form-select',
                ],
            ],
            'degerlendirme.puan' => [
                'type' => 'range',
                'label' => 'Değerlendirme Puanı (1-10)',
                'required' => false,
                'value' => $currentValues['degerlendirme']['puan'] ?? 5,
                'attributes' => [
                    'wire:model.live' => 'form.degerlendirme.puan',
                    'class' => 'form-range',
                    'min' => 1,
                    'max' => 10,
                    'step' => 1,
                ],
            ],
            'degerlendirme.notlar' => [
                'type' => 'textarea',
                'label' => 'Değerlendirme Notları',
                'required' => false,
                'value' => $currentValues['degerlendirme']['notlar'] ?? '',
                'attributes' => [
                    'wire:model' => 'form.degerlendirme.notlar',
                    'class' => 'form-textarea',
                    'rows' => 2,
                    'placeholder' => 'Değerlendirme ile ilgili notlar...',
                ],
            ],
        ];
    }

    /**
     * Ek bilgiler alanları
     */
    public function getEkBilgilerFields(array $currentValues = []): array
    {
        return [
            'maliyet' => [
                'type' => 'number',
                'label' => 'Maliyet',
                'required' => false,
                'value' => $currentValues['maliyet'] ?? null,
                'attributes' => [
                    'wire:model' => 'form.maliyet',
                    'class' => 'form-input',
                    'min' => 0,
                    'step' => 0.01,
                    'placeholder' => '0.00',
                ],
            ],
            'para_birimi' => [
                'type' => 'select',
                'label' => 'Para Birimi',
                'required' => false,
                'value' => $currentValues['para_birimi'] ?? 'TRY',
                'options' => [
                    'TRY' => '₺ Türk Lirası',
                    'USD' => '$ Amerikan Doları',
                    'EUR' => '€ Euro',
                ],
                'attributes' => [
                    'wire:model' => 'form.para_birimi',
                    'class' => 'form-select',
                ],
            ],
            'etiketler' => [
                'type' => 'tags',
                'label' => 'Etiketler',
                'required' => false,
                'value' => $currentValues['etiketler'] ?? [],
                'attributes' => [
                    'wire:model' => 'form.etiketler',
                    'class' => 'form-tags',
                    'placeholder' => 'Etiket yazın ve Enter\'a basın',
                ],
            ],
        ];
    }

    /**
     * Hizmet tipi seçenekleri
     */
    private function getHizmetTipiOptions(): array
    {
        $options = ['' => 'Hizmet Tipi Seçin'];
        
        foreach (HizmetTipi::cases() as $tip) {
            $options[$tip->value] = $tip->label();
        }
        
        return $options;
    }

    /**
     * Sonuç tipi seçenekleri
     */
    private function getSonucTipiOptions(): array
    {
        $options = ['' => 'Sonuç Tipi Seçin'];
        
        foreach (HizmetSonucu::cases() as $sonuc) {
            $options[$sonuc->value] = $sonuc->label();
        }
        
        return $options;
    }

    /**
     * Değerlendirme tipi seçenekleri
     */
    private function getDegerlendirmeTipiOptions(): array
    {
        $options = ['' => 'Değerlendirme Tipi Seçin'];
        
        foreach (DegerlendirmeTipi::cases() as $tip) {
            $options[$tip->value] = $tip->label();
        }
        
        return $options;
    }

    /**
     * Form validation kuralları
     */
    public function getValidationRules(HizmetTipi $hizmetTipi): array
    {
        $rules = [
            'form.musteri_id' => 'required|exists:musteri,id',
            'form.hizmet_tipi' => 'required|in:' . implode(',', array_map(fn($t) => $t->value, HizmetTipi::cases())),
            'form.hizmet_tarihi' => 'required|date',
            'form.aciklama' => 'nullable|string|max:5000',
            'form.mulk_id' => 'nullable|exists:mulkler,id',
            'form.sonuc' => 'nullable|string|max:5000',
            'form.sonuc_tipi' => 'nullable|in:' . implode(',', array_map(fn($s) => $s->value, HizmetSonucu::cases())),
            'form.takip_tarihi' => 'nullable|date|after:form.hizmet_tarihi',
            'form.takip_notu' => 'nullable|string|max:1000',
            'form.maliyet' => 'nullable|numeric|min:0',
            'form.para_birimi' => 'nullable|in:TRY,USD,EUR',
            'form.etiketler' => 'nullable|array',
            'form.etiketler.*' => 'string|max:50',
            'form.degerlendirme.tip' => 'nullable|in:' . implode(',', array_map(fn($d) => $d->value, DegerlendirmeTipi::cases())),
            'form.degerlendirme.puan' => 'nullable|integer|min:1|max:10',
            'form.degerlendirme.notlar' => 'nullable|string|max:1000',
        ];

        // Hizmet tipine özel kurallar
        if ($hizmetTipi->requiresDuration()) {
            $rules['form.sure_dakika'] = 'required|integer|min:1|max:1440';
            $rules['form.bitis_tarihi'] = 'nullable|date|after:form.hizmet_tarihi';
        }

        if ($hizmetTipi->requiresLocation()) {
            $rules['form.lokasyon'] = 'required|string|max:255';
        }

        if ($hizmetTipi->requiresParticipants()) {
            $rules['form.katilimcilar'] = 'required|array|min:1';
            $rules['form.katilimcilar.*'] = 'string|max:255';
        }

        return $rules;
    }

    /**
     * Form mesajları
     */
    public function getValidationMessages(): array
    {
        return [
            'form.musteri_id.required' => 'Müşteri seçimi zorunludur.',
            'form.musteri_id.exists' => 'Seçilen müşteri bulunamadı.',
            'form.hizmet_tipi.required' => 'Hizmet tipi seçimi zorunludur.',
            'form.hizmet_tarihi.required' => 'Hizmet tarihi zorunludur.',
            'form.sure_dakika.required' => 'Bu hizmet tipi için süre bilgisi zorunludur.',
            'form.sure_dakika.min' => 'Süre en az 1 dakika olmalıdır.',
            'form.sure_dakika.max' => 'Süre en fazla 1440 dakika (24 saat) olabilir.',
            'form.lokasyon.required' => 'Bu hizmet tipi için lokasyon bilgisi zorunludur.',
            'form.katilimcilar.required' => 'Bu hizmet tipi için en az bir katılımcı eklemelisiniz.',
            'form.katilimcilar.min' => 'En az bir katılımcı eklemelisiniz.',
            'form.bitis_tarihi.after' => 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.',
            'form.takip_tarihi.after' => 'Takip tarihi hizmet tarihinden sonra olmalıdır.',
            'form.maliyet.min' => 'Maliyet negatif olamaz.',
            'form.degerlendirme.puan.min' => 'Değerlendirme puanı en az 1 olmalıdır.',
            'form.degerlendirme.puan.max' => 'Değerlendirme puanı en fazla 10 olabilir.',
        ];
    }

    /**
     * Form alanlarını grupla
     */
    public function getGroupedFields(HizmetTipi $hizmetTipi, array $currentValues = []): array
    {
        return [
            'temel_bilgiler' => [
                'title' => 'Temel Bilgiler',
                'fields' => $this->generateFormFields($hizmetTipi, $currentValues),
            ],
            'sonuc_bilgileri' => [
                'title' => 'Sonuç Bilgileri',
                'fields' => $this->getSonucFields($currentValues),
            ],
            'degerlendirme' => [
                'title' => 'Değerlendirme',
                'fields' => $this->getDegerlendirmeFields($currentValues),
            ],
            'ek_bilgiler' => [
                'title' => 'Ek Bilgiler',
                'fields' => $this->getEkBilgilerFields($currentValues),
            ],
        ];
    }
}