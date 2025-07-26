<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Semt;
use App\Models\Lokasyon\Sehir;
use Illuminate\Database\Seeder;
use App\Models\Lokasyon\Mahalle;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LokasyonExcelSeeder extends Seeder
{
    protected $sehirler = [];
    protected $ilceler = [];
    protected $semtler = [];


    public function run()
    {
        $path = public_path('lokasyon.xlsx');

        // Excel verilerini al
        $rows = Excel::toArray([], $path)[0];

        // Başlık satırını kaldır
        array_shift($rows);

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $sehirAdi = trim($row[0]); // İl
                $ilceAdi = trim($row[1]); // İlçe
                $semtAdi = trim($row[2]); // Semt
                $mahalleAdi = trim($row[3]); // Mahalle
                $postaKodu = trim($row[4]); // Posta Kodu

                // Şehir
                if (!isset($this->sehirler[$sehirAdi])) {
                    $sehir = Sehir::firstOrCreate(
                        ['ad' => $sehirAdi],
                        ['aktif_mi' => true]
                    );
                    $this->sehirler[$sehirAdi] = $sehir->id;
                    $this->command->info("Şehir eklendi: {$sehirAdi}");
                }

                // İlçe
                $ilceKey = $sehirAdi . '-' . $ilceAdi;
                if (!isset($this->ilceler[$ilceKey])) {
                    $ilce = Ilce::firstOrCreate(
                        [
                            'sehir_id' => $this->sehirler[$sehirAdi],
                            'ad' => $ilceAdi
                        ],
                        ['aktif_mi' => true]
                    );
                    $this->ilceler[$ilceKey] = $ilce->id;
                    $this->command->info("İlçe eklendi: {$ilceAdi}");
                }

                // Semt
                $semtKey = $ilceKey . '-' . $semtAdi;
                if (!isset($this->semtler[$semtKey])) {
                    $semt = Semt::firstOrCreate(
                        [
                            'ilce_id' => $this->ilceler[$ilceKey],
                            'ad' => $semtAdi
                        ],
                        ['aktif_mi' => true]
                    );
                    $this->semtler[$semtKey] = $semt->id;
                    $this->command->info("Semt eklendi: {$semtAdi}");
                }

                // Mahalle
                Mahalle::firstOrCreate(
                    [
                        'semt_id' => $this->semtler[$semtKey],
                        'ad' => $mahalleAdi,
                    ],
                    [
                        'posta_kodu' => $postaKodu,
                        'aktif_mi' => true
                    ]
                );
            }
        });

        $this->command->info('Tüm lokasyon verileri başarıyla eklendi!');
    }
}
