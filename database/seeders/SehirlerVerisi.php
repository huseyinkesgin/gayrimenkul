<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SehirlerVerisi extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Şehir verilerini ekle
        DB::table('sehirler')->insert([
            ['id' => Str::uuid(), 'ad' => 'İstanbul', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
            ['id' => Str::uuid(), 'ad' => 'Ankara', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
            ['id' => Str::uuid(), 'ad' => 'İzmir', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
            // Diğer şehirler...
        ]);

        $this->command->info('Şehirler tablosuna veriler eklendi.');
    }
}

