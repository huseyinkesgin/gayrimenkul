<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\MusteriTalep;
use App\Models\TalepPortfoyEslestirme;

class TalepEslestirmeBildirim extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tip,
        public MusteriTalep $talep,
        public ?TalepPortfoyEslestirme $eslestirme = null,
        public array $ekstraBilgi = []
    ) {}

    /**
     * Bildirim kanallarını belirle
     */
    public function via($notifiable): array
    {
        $kanallar = ['database'];

        // Email bildirimi için kullanıcı tercihini kontrol et
        if ($notifiable->bildirim_tercihleri['email'] ?? true) {
            $kanallar[] = 'mail';
        }

        return $kanallar;
    }

    /**
     * Email bildirimi
     */
    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getEmailSubject())
            ->greeting("Merhaba {$notifiable->name},");

        return match($this->tip) {
            'yeni_eslestirme' => $this->yeniEslestirmeEmail($mailMessage),
            'yuksek_skorlu_eslestirme' => $this->yuksekSkorluEslestirmeEmail($mailMessage),
            'eslestirme_sunuldu' => $this->eslestirmeSunulduEmail($mailMessage),
            'eslestirme_kabul' => $this->eslestirmeKabulEmail($mailMessage),
            'eslestirme_red' => $this->eslestirmeRedEmail($mailMessage),
            'talep_guncellendi' => $this->talepGuncellendiEmail($mailMessage),
            default => $mailMessage->line('Yeni bir eşleştirme bildirimi var.')
        };
    }

    /**
     * Database bildirimi
     */
    public function toDatabase($notifiable): array
    {
        return [
            'tip' => $this->tip,
            'baslik' => $this->getDatabaseTitle(),
            'mesaj' => $this->getDatabaseMessage(),
            'talep_id' => $this->talep->id,
            'talep_baslik' => $this->talep->baslik,
            'eslestirme_id' => $this->eslestirme?->id,
            'eslestirme_skoru' => $this->eslestirme?->eslestirme_skoru,
            'ekstra_bilgi' => $this->ekstraBilgi,
            'olusturma_tarihi' => now()->toISOString(),
        ];
    }

    /**
     * Email konusu
     */
    protected function getEmailSubject(): string
    {
        return match($this->tip) {
            'yeni_eslestirme' => 'Yeni Portföy Eşleştirmesi',
            'yuksek_skorlu_eslestirme' => 'Yüksek Skorlu Eşleştirme',
            'eslestirme_sunuldu' => 'Portföy Müşteriye Sunuldu',
            'eslestirme_kabul' => 'Eşleştirme Kabul Edildi',
            'eslestirme_red' => 'Eşleştirme Reddedildi',
            'talep_guncellendi' => 'Talep Güncellendi',
            default => 'Eşleştirme Bildirimi'
        };
    }

    /**
     * Database bildirim başlığı
     */
    protected function getDatabaseTitle(): string
    {
        return match($this->tip) {
            'yeni_eslestirme' => 'Yeni Eşleştirme',
            'yuksek_skorlu_eslestirme' => 'Yüksek Skorlu Eşleştirme',
            'eslestirme_sunuldu' => 'Portföy Sunuldu',
            'eslestirme_kabul' => 'Eşleştirme Kabul',
            'eslestirme_red' => 'Eşleştirme Red',
            'talep_guncellendi' => 'Talep Güncellendi',
            default => 'Bildirim'
        };
    }

    /**
     * Database bildirim mesajı
     */
    protected function getDatabaseMessage(): string
    {
        return match($this->tip) {
            'yeni_eslestirme' => "'{$this->talep->baslik}' talebi için yeni eşleştirme bulundu.",
            'yuksek_skorlu_eslestirme' => "'{$this->talep->baslik}' talebi için yüksek skorlu eşleştirme bulundu (Skor: " . number_format($this->eslestirme->eslestirme_skoru, 2) . ").",
            'eslestirme_sunuldu' => "'{$this->talep->baslik}' talebi için portföy müşteriye sunuldu.",
            'eslestirme_kabul' => "'{$this->talep->baslik}' talebi için eşleştirme kabul edildi.",
            'eslestirme_red' => "'{$this->talep->baslik}' talebi için eşleştirme reddedildi.",
            'talep_guncellendi' => "'{$this->talep->baslik}' talebi güncellendi ve yeni eşleştirmeler aranıyor.",
            default => 'Yeni bir bildirim var.'
        };
    }

    /**
     * Yeni eşleştirme email içeriği
     */
    protected function yeniEslestirmeEmail(MailMessage $message): MailMessage
    {
        return $message
            ->line("'{$this->talep->baslik}' talebi için yeni bir portföy eşleştirmesi bulundu.")
            ->line("Müşteri: {$this->talep->musteri->ad} {$this->talep->musteri->soyad}")
            ->line("Eşleştirme Sayısı: " . ($this->ekstraBilgi['eslestirme_sayisi'] ?? 1))
            ->action('Eşleştirmeleri Görüntüle', url("/talepler/{$this->talep->id}/eslestirmeler"))
            ->line('Lütfen eşleştirmeleri inceleyip müşteriye sunum yapın.');
    }

    /**
     * Yüksek skorlu eşleştirme email içeriği
     */
    protected function yuksekSkorluEslestirmeEmail(MailMessage $message): MailMessage
    {
        $skor = number_format($this->eslestirme->eslestirme_skoru * 100, 1);
        
        return $message
            ->line("'{$this->talep->baslik}' talebi için yüksek skorlu bir eşleştirme bulundu!")
            ->line("Müşteri: {$this->talep->musteri->ad} {$this->talep->musteri->soyad}")
            ->line("Eşleştirme Skoru: %{$skor}")
            ->line("Mülk: {$this->eslestirme->mulk->baslik}")
            ->action('Eşleştirmeyi Görüntüle', url("/eslestirmeler/{$this->eslestirme->id}"))
            ->line('Bu yüksek skorlu eşleştirmeyi öncelikli olarak değerlendirin.');
    }

    /**
     * Eşleştirme sunuldu email içeriği
     */
    protected function eslestirmeSunulduEmail(MailMessage $message): MailMessage
    {
        return $message
            ->line("'{$this->talep->baslik}' talebi için bir portföy müşteriye sunuldu.")
            ->line("Müşteri: {$this->talep->musteri->ad} {$this->talep->musteri->soyad}")
            ->line("Sunan Personel: {$this->eslestirme->sunanPersonel->ad} {$this->eslestirme->sunanPersonel->soyad}")
            ->line("Sunum Tarihi: " . $this->eslestirme->sunum_tarihi->format('d.m.Y H:i'))
            ->action('Detayları Görüntüle', url("/eslestirmeler/{$this->eslestirme->id}"))
            ->line('Müşteri geri bildirimini takip edin.');
    }

    /**
     * Eşleştirme kabul email içeriği
     */
    protected function eslestirmeKabulEmail(MailMessage $message): MailMessage
    {
        return $message
            ->line("Harika haber! '{$this->talep->baslik}' talebi için eşleştirme kabul edildi.")
            ->line("Müşteri: {$this->talep->musteri->ad} {$this->talep->musteri->soyad}")
            ->line("Kabul Edilen Mülk: {$this->eslestirme->mulk->baslik}")
            ->action('Detayları Görüntüle', url("/eslestirmeler/{$this->eslestirme->id}"))
            ->line('Sözleşme sürecini başlatabilirsiniz.');
    }

    /**
     * Eşleştirme red email içeriği
     */
    protected function eslestirmeRedEmail(MailMessage $message): MailMessage
    {
        return $message
            ->line("'{$this->talep->baslik}' talebi için eşleştirme reddedildi.")
            ->line("Müşteri: {$this->talep->musteri->ad} {$this->talep->musteri->soyad}")
            ->line("Reddedilen Mülk: {$this->eslestirme->mulk->baslik}")
            ->when($this->eslestirme->musteri_geri_bildirimi, function ($message) {
                return $message->line("Müşteri Geri Bildirimi: {$this->eslestirme->musteri_geri_bildirimi}");
            })
            ->action('Detayları Görüntüle', url("/eslestirmeler/{$this->eslestirme->id}"))
            ->line('Alternatif seçenekleri değerlendirin.');
    }

    /**
     * Talep güncellendi email içeriği
     */
    protected function talepGuncellendiEmail(MailMessage $message): MailMessage
    {
        return $message
            ->line("'{$this->talep->baslik}' talebi güncellendi.")
            ->line("Müşteri: {$this->talep->musteri->ad} {$this->talep->musteri->soyad}")
            ->line("Güncellenen Alanlar: " . implode(', ', $this->ekstraBilgi['guncellenen_alanlar'] ?? []))
            ->action('Talebi Görüntüle', url("/talepler/{$this->talep->id}"))
            ->line('Yeni kriterlere göre eşleştirme aranıyor.');
    }
}