<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        // User::factory()->create([
        //     'uuid' => '00000000-0000-0000-0000-000000000001',
        //     'name' => 'Burada Yapı',
        //     'email' => 'info@buradayapi.com.tr',
        //     'email_verified_at' => now(),
        //     'password' => Hash::make('password'),
        //     'remember_token' => Str::random(10),
        // ]);

        //User::factory(75)->create();

        //$this->call(LokasyonExcelSeeder::class);
        //$this->call(DepartmanTabloHazirVeri::class);
       // $this->call(PozisyonTabloHazirVeri::class);
        //$this->call(PersonelRoluTabloHazirVeri::class);

        //$this->call(SehirlerVerisi::class);
        $this->call(GayrimenkulSystemSeeder::class);


    }
}
