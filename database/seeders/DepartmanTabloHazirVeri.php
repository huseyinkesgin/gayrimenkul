<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use App\Models\Kisi\Departman;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DepartmanTabloHazirVeri extends Seeder
{
    public function run(): void
    {
        Departman::insert([
            [
                'id' => Str::uuid(),
                'ad' => 'Danışman',
                'olusturma_tarihi' => now(),
                'guncelleme_tarihi' => now(),
            ],
            [
                'id' => Str::uuid(),
                'ad' => 'Broker',
                'olusturma_tarihi' => now(),
                'guncelleme_tarihi' => now(),
            ],
        ]);
    }
}
