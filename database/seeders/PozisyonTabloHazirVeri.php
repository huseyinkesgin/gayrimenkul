<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use App\Models\Kisi\Pozisyon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PozisyonTabloHazirVeri extends Seeder
{
    public function run(): void
    {
        Pozisyon::insert([
            ['id' => Str::uuid(), 'ad' => 'Müdür', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
            ['id' => Str::uuid(), 'ad' => 'Sorumlu', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
            ['id' => Str::uuid(), 'ad' => 'Uzman', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
            ['id' => Str::uuid(), 'ad' => 'Asistan', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
        ]);
    }
}
