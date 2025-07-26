<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IlTabloHazirVeri extends Seeder
{

    public function run(): void
    {
        $veriler = [
            ['id' => 1, 'il_ad' => 'Adana', 'plaka_kodu' => 1],
            ['id' => 2, 'il_ad' => 'Adıyaman', 'plaka_kodu' => 2],
            ['id' => 3, 'il_ad' => 'Afyonkarahisar', 'plaka_kodu' => 3],
            ['id' => 4, 'il_ad' => 'Ağrı', 'plaka_kodu' => 4],
            ['id' => 5, 'il_ad' => 'Amasya', 'plaka_kodu' => 5],
            ['id' => 6, 'il_ad' => 'Ankara', 'plaka_kodu' => 6],
            ['id' => 7, 'il_ad' => 'Antalya', 'plaka_kodu' => 7],
            ['id' => 8, 'il_ad' => 'Artvin', 'plaka_kodu' => 8],
            ['id' => 9, 'il_ad' => 'Aydın', 'plaka_kodu' => 9],
            ['id' => 10, 'il_ad' => 'Balıkesir', 'plaka_kodu' => 10],
            ['id' => 11, 'il_ad' => 'Bilecik', 'plaka_kodu' => 11],
            ['id' => 12, 'il_ad' => 'Bingöl', 'plaka_kodu' => 12],
            ['id' => 13, 'il_ad' => 'Bitlis', 'plaka_kodu' => 13],
            ['id' => 14, 'il_ad' => 'Bolu', 'plaka_kodu' => 14],
            ['id' => 15, 'il_ad' => 'Burdur', 'plaka_kodu' => 15],
            ['id' => 16, 'il_ad' => 'Bursa', 'plaka_kodu' => 16],
            ['id' => 17, 'il_ad' => 'Çanakkale', 'plaka_kodu' => 17],
            ['id' => 18, 'il_ad' => 'Çankırı', 'plaka_kodu' => 18],
            ['id' => 19, 'il_ad' => 'Çorum', 'plaka_kodu' => 19],
            ['id' => 20, 'il_ad' => 'Denizli', 'plaka_kodu' => 20],
            ['id' => 21, 'il_ad' => 'Diyarbakır', 'plaka_kodu' => 21],
            ['id' => 22, 'il_ad' => 'Edirne', 'plaka_kodu' => 22],
            ['id' => 23, 'il_ad' => 'Elazığ', 'plaka_kodu' => 23],
            ['id' => 24, 'il_ad' => 'Erzincan', 'plaka_kodu' => 24],
            ['id' => 25, 'il_ad' => 'Erzurum', 'plaka_kodu' => 25],
            ['id' => 26, 'il_ad' => 'Eskişehir', 'plaka_kodu' => 26],
            ['id' => 27, 'il_ad' => 'Gaziantep', 'plaka_kodu' => 27],
            ['id' => 28, 'il_ad' => 'Giresun', 'plaka_kodu' => 28],
            ['id' => 29, 'il_ad' => 'Gümüşhane', 'plaka_kodu' => 29],
            ['id' => 30, 'il_ad' => 'Hakkari', 'plaka_kodu' => 30],
            ['id' => 31, 'il_ad' => 'Hatay', 'plaka_kodu' => 31],
            ['id' => 32, 'il_ad' => 'Isparta', 'plaka_kodu' => 32],
            ['id' => 33, 'il_ad' => 'Mersin', 'plaka_kodu' => 33],
            ['id' => 34, 'il_ad' => 'İstanbul', 'plaka_kodu' => 34],
            ['id' => 35, 'il_ad' => 'İzmir', 'plaka_kodu' => 35],
            ['id' => 36, 'il_ad' => 'Kars', 'plaka_kodu' => 36],
            ['id' => 37, 'il_ad' => 'Kastamonu', 'plaka_kodu' => 37],
            ['id' => 38, 'il_ad' => 'Kayseri', 'plaka_kodu' => 38],
            ['id' => 39, 'il_ad' => 'Kırklareli', 'plaka_kodu' => 39],
            ['id' => 40, 'il_ad' => 'Kırşehir', 'plaka_kodu' => 40],
            ['id' => 41, 'il_ad' => 'Kocaeli', 'plaka_kodu' => 41],
            ['id' => 42, 'il_ad' => 'Konya', 'plaka_kodu' => 42],
            ['id' => 43, 'il_ad' => 'Kütahya', 'plaka_kodu' => 43],
            ['id' => 44, 'il_ad' => 'Malatya', 'plaka_kodu' => 44],
            ['id' => 45, 'il_ad' => 'Manisa', 'plaka_kodu' => 45],
            ['id' => 46, 'il_ad' => 'Kahramanmaraş', 'plaka_kodu' => 46],
            ['id' => 47, 'il_ad' => 'Mardin', 'plaka_kodu' => 47],
            ['id' => 48, 'il_ad' => 'Muğla', 'plaka_kodu' => 48],
            ['id' => 49, 'il_ad' => 'Muş', 'plaka_kodu' => 49],
            ['id' => 50, 'il_ad' => 'Nevşehir', 'plaka_kodu' => 50],
            ['id' => 51, 'il_ad' => 'Niğde', 'plaka_kodu' => 51],
            ['id' => 52, 'il_ad' => 'Ordu', 'plaka_kodu' => 52],
            ['id' => 53, 'il_ad' => 'Rize', 'plaka_kodu' => 53],
            ['id' => 54, 'il_ad' => 'Sakarya', 'plaka_kodu' => 54],
            ['id' => 55, 'il_ad' => 'Samsun', 'plaka_kodu' => 55],
            ['id' => 56, 'il_ad' => 'Siirt', 'plaka_kodu' => 56],
            ['id' => 57, 'il_ad' => 'Sinop', 'plaka_kodu' => 57],
            ['id' => 58, 'il_ad' => 'Sivas', 'plaka_kodu' => 58],
            ['id' => 59, 'il_ad' => 'Tekirdağ', 'plaka_kodu' => 59],
            ['id' => 60, 'il_ad' => 'Tokat', 'plaka_kodu' => 60],
            ['id' => 61, 'il_ad' => 'Trabzon', 'plaka_kodu' => 61],
            ['id' => 62, 'il_ad' => 'Tunceli', 'plaka_kodu' => 62],
            ['id' => 63, 'il_ad' => 'Şanlıurfa', 'plaka_kodu' => 63],
            ['id' => 64, 'il_ad' => 'Uşak', 'plaka_kodu' => 64],
            ['id' => 65, 'il_ad' => 'Van', 'plaka_kodu' => 65],
            ['id' => 66, 'il_ad' => 'Yozgat', 'plaka_kodu' => 66],
            ['id' => 67, 'il_ad' => 'Zonguldak', 'plaka_kodu' => 67],
            ['id' => 68, 'il_ad' => 'Aksaray', 'plaka_kodu' => 68],
            ['id' => 69, 'il_ad' => 'Bayburt', 'plaka_kodu' => 69],
            ['id' => 70, 'il_ad' => 'Karaman', 'plaka_kodu' => 70],
            ['id' => 71, 'il_ad' => 'Kırıkkale', 'plaka_kodu' => 71],
            ['id' => 72, 'il_ad' => 'Batman', 'plaka_kodu' => 72],
            ['id' => 73, 'il_ad' => 'Şırnak', 'plaka_kodu' => 73],
            ['id' => 74, 'il_ad' => 'Bartın', 'plaka_kodu' => 74],
            ['id' => 75, 'il_ad' => 'Ardahan', 'plaka_kodu' => 75],
            ['id' => 76, 'il_ad' => 'Iğdır', 'plaka_kodu' => 76],
            ['id' => 77, 'il_ad' => 'Yalova', 'plaka_kodu' => 77],
            ['id' => 78, 'il_ad' => 'Karabük', 'plaka_kodu' => 78],
            ['id' => 79, 'il_ad' => 'Kilis', 'plaka_kodu' => 79],
            ['id' => 80, 'il_ad' => 'Osmaniye', 'plaka_kodu' => 80],
            ['id' => 81, 'il_ad' => 'Düzce', 'plaka_kodu' => 81],

        ];

        DB::table('tablo_il')->insert($veriler);
    }
}
