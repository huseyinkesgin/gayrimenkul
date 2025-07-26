<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use App\Models\Kisi\Departman;
use App\Models\Kisi\PersonelRol;
use Faker\Provider\ar_EG\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PersonelRoluTabloHazirVeri extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Personel rolleri verilerini ekle
        PersonelRol::insert([
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
        $this->command->info('Personel rolleri tablosuna veriler eklendi.');}
}
