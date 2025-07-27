<?php

namespace Tests\Unit\Enums;

use Tests\TestCase;
use App\Enums\HatirlatmaTipi;

class HatirlatmaTipiTest extends TestCase
{
    /** @test */
    public function it_has_correct_values()
    {
        $this->assertEquals('arama', HatirlatmaTipi::ARAMA->value);
        $this->assertEquals('toplanti', HatirlatmaTipi::TOPLANTI->value);
        $this->assertEquals('email', HatirlatmaTipi::EMAIL->value);
        $this->assertEquals('ziyaret', HatirlatmaTipi::ZIYARET->value);
        $this->assertEquals('sms', HatirlatmaTipi::SMS->value);
        $this->assertEquals('gorev', HatirlatmaTipi::GOREV->value);
        $this->assertEquals('diger', HatirlatmaTipi::DIGER->value);
    }

    /** @test */
    public function it_returns_correct_labels()
    {
        $this->assertEquals('Telefon Araması', HatirlatmaTipi::ARAMA->label());
        $this->assertEquals('Toplantı', HatirlatmaTipi::TOPLANTI->label());
        $this->assertEquals('E-posta', HatirlatmaTipi::EMAIL->label());
        $this->assertEquals('Ziyaret', HatirlatmaTipi::ZIYARET->label());
        $this->assertEquals('SMS', HatirlatmaTipi::SMS->label());
        $this->assertEquals('Görev', HatirlatmaTipi::GOREV->label());
        $this->assertEquals('Diğer', HatirlatmaTipi::DIGER->label());
    }

    /** @test */
    public function it_returns_correct_descriptions()
    {
        $this->assertStringContainsString('telefon', HatirlatmaTipi::ARAMA->description());
        $this->assertStringContainsString('toplantı', HatirlatmaTipi::TOPLANTI->description());
        $this->assertStringContainsString('E-posta', HatirlatmaTipi::EMAIL->description());
        $this->assertStringContainsString('ziyaret', HatirlatmaTipi::ZIYARET->description());
    }

    /** @test */
    public function it_returns_correct_colors()
    {
        $this->assertEquals('green', HatirlatmaTipi::ARAMA->color());
        $this->assertEquals('blue', HatirlatmaTipi::TOPLANTI->color());
        $this->assertEquals('purple', HatirlatmaTipi::EMAIL->color());
        $this->assertEquals('orange', HatirlatmaTipi::ZIYARET->color());
        $this->assertEquals('yellow', HatirlatmaTipi::SMS->color());
        $this->assertEquals('red', HatirlatmaTipi::GOREV->color());
        $this->assertEquals('gray', HatirlatmaTipi::DIGER->color());
    }

    /** @test */
    public function it_returns_correct_icons()
    {
        $this->assertEquals('phone', HatirlatmaTipi::ARAMA->icon());
        $this->assertEquals('users', HatirlatmaTipi::TOPLANTI->icon());
        $this->assertEquals('envelope', HatirlatmaTipi::EMAIL->icon());
        $this->assertEquals('map-pin', HatirlatmaTipi::ZIYARET->icon());
        $this->assertEquals('chat-bubble-left', HatirlatmaTipi::SMS->icon());
        $this->assertEquals('clipboard-document-check', HatirlatmaTipi::GOREV->icon());
        $this->assertEquals('bell', HatirlatmaTipi::DIGER->icon());
    }

    /** @test */
    public function it_returns_correct_default_durations()
    {
        $this->assertEquals(15, HatirlatmaTipi::ARAMA->defaultDuration());
        $this->assertEquals(60, HatirlatmaTipi::TOPLANTI->defaultDuration());
        $this->assertEquals(5, HatirlatmaTipi::EMAIL->defaultDuration());
        $this->assertEquals(120, HatirlatmaTipi::ZIYARET->defaultDuration());
        $this->assertEquals(2, HatirlatmaTipi::SMS->defaultDuration());
        $this->assertEquals(30, HatirlatmaTipi::GOREV->defaultDuration());
        $this->assertEquals(15, HatirlatmaTipi::DIGER->defaultDuration());
    }

    /** @test */
    public function it_returns_correct_priorities()
    {
        $this->assertEquals(10, HatirlatmaTipi::TOPLANTI->priority());
        $this->assertEquals(9, HatirlatmaTipi::ZIYARET->priority());
        $this->assertEquals(8, HatirlatmaTipi::ARAMA->priority());
        $this->assertEquals(7, HatirlatmaTipi::GOREV->priority());
        $this->assertEquals(6, HatirlatmaTipi::EMAIL->priority());
        $this->assertEquals(5, HatirlatmaTipi::SMS->priority());
        $this->assertEquals(4, HatirlatmaTipi::DIGER->priority());
    }

    /** @test */
    public function it_correctly_identifies_auto_notification_types()
    {
        $this->assertTrue(HatirlatmaTipi::TOPLANTI->autoNotification());
        $this->assertTrue(HatirlatmaTipi::ZIYARET->autoNotification());
        $this->assertTrue(HatirlatmaTipi::ARAMA->autoNotification());
        $this->assertFalse(HatirlatmaTipi::EMAIL->autoNotification());
        $this->assertFalse(HatirlatmaTipi::SMS->autoNotification());
        $this->assertFalse(HatirlatmaTipi::GOREV->autoNotification());
        $this->assertFalse(HatirlatmaTipi::DIGER->autoNotification());
    }

    /** @test */
    public function it_returns_correct_notification_timing()
    {
        $toplantiTiming = HatirlatmaTipi::TOPLANTI->notificationTiming();
        $this->assertContains(1440, $toplantiTiming); // 1 gün
        $this->assertContains(60, $toplantiTiming); // 1 saat
        $this->assertContains(15, $toplantiTiming); // 15 dakika

        $ziyaretTiming = HatirlatmaTipi::ZIYARET->notificationTiming();
        $this->assertContains(1440, $ziyaretTiming); // 1 gün
        $this->assertContains(120, $ziyaretTiming); // 2 saat

        $aramaTiming = HatirlatmaTipi::ARAMA->notificationTiming();
        $this->assertContains(60, $aramaTiming); // 1 saat
        $this->assertContains(15, $aramaTiming); // 15 dakika
    }

    /** @test */
    public function it_returns_required_fields()
    {
        $aramaFields = HatirlatmaTipi::ARAMA->requiredFields();
        $this->assertContains('telefon_numarasi', $aramaFields);

        $toplantiFields = HatirlatmaTipi::TOPLANTI->requiredFields();
        $this->assertContains('konum', $toplantiFields);
        $this->assertContains('katilimcilar', $toplantiFields);

        $emailFields = HatirlatmaTipi::EMAIL->requiredFields();
        $this->assertContains('email_adresi', $emailFields);
        $this->assertContains('konu', $emailFields);

        $ziyaretFields = HatirlatmaTipi::ZIYARET->requiredFields();
        $this->assertContains('adres', $ziyaretFields);
        $this->assertContains('konum', $ziyaretFields);
    }

    /** @test */
    public function it_returns_template_messages()
    {
        $aramaTemplates = HatirlatmaTipi::ARAMA->templateMessages();
        $this->assertIsArray($aramaTemplates);
        $this->assertNotEmpty($aramaTemplates);
        $this->assertStringContainsString('{musteri_adi}', $aramaTemplates[0]);

        $toplantiTemplates = HatirlatmaTipi::TOPLANTI->templateMessages();
        $this->assertIsArray($toplantiTemplates);
        $this->assertNotEmpty($toplantiTemplates);
        $this->assertStringContainsString('{tarih}', $toplantiTemplates[0]);
    }

    /** @test */
    public function it_converts_to_array()
    {
        $array = HatirlatmaTipi::toArray();
        
        $this->assertIsArray($array);
        $this->assertCount(7, $array);
        
        $firstItem = $array[0];
        $this->assertArrayHasKey('value', $firstItem);
        $this->assertArrayHasKey('label', $firstItem);
        $this->assertArrayHasKey('description', $firstItem);
        $this->assertArrayHasKey('color', $firstItem);
        $this->assertArrayHasKey('defaultDuration', $firstItem);
        $this->assertArrayHasKey('priority', $firstItem);
        $this->assertArrayHasKey('autoNotification', $firstItem);
        $this->assertArrayHasKey('notificationTiming', $firstItem);
        $this->assertArrayHasKey('requiredFields', $firstItem);
        $this->assertArrayHasKey('templateMessages', $firstItem);
    }

    /** @test */
    public function it_returns_types_by_priority()
    {
        $byPriority = HatirlatmaTipi::byPriority();
        
        $this->assertIsArray($byPriority);
        $this->assertEquals(HatirlatmaTipi::TOPLANTI, $byPriority[0]); // En yüksek öncelik
        $this->assertEquals(HatirlatmaTipi::ZIYARET, $byPriority[1]);
        $this->assertEquals(HatirlatmaTipi::DIGER, end($byPriority)); // En düşük öncelik
    }

    /** @test */
    public function it_returns_auto_notification_types()
    {
        $autoTypes = HatirlatmaTipi::autoNotificationTypes();
        
        $this->assertIsArray($autoTypes);
        $this->assertCount(3, $autoTypes); // TOPLANTI, ZIYARET, ARAMA
        
        $autoValues = array_map(fn($type) => $type->value, $autoTypes);
        $this->assertContains('toplanti', $autoValues);
        $this->assertContains('ziyaret', $autoValues);
        $this->assertContains('arama', $autoValues);
        $this->assertNotContains('email', $autoValues);
    }

    /** @test */
    public function it_returns_random_template()
    {
        $template = HatirlatmaTipi::ARAMA->getRandomTemplate();
        
        $this->assertIsString($template);
        $this->assertNotEmpty($template);
        
        $templates = HatirlatmaTipi::ARAMA->templateMessages();
        $this->assertContains($template, $templates);
    }
}