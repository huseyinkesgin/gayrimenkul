<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Musteri\MusteriKategori;

class MusteriKategorileriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Enum değerlerinden kategorileri oluştur
        MusteriKategori::createFromEnum();
    }
}