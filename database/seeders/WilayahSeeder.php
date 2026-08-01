<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Kota Malang' => [
                'Blimbing',
                'Kedungkandang',
                'Klojen',
                'Lowokwaru',
                'Sukun',
            ],

            'Kabupaten Malang' => [
                'Ampelgading',
                'Bantur',
                'Bululawang',
                'Dampit',
                'Dau',
                'Donomulyo',
                'Gedangan',
                'Gondanglegi',
                'Jabung',
                'Kalipare',
                'Karangploso',
                'Kasembon',
                'Kepanjen',
                'Kromengan',
                'Lawang',
                'Ngajum',
                'Ngantang',
                'Pagak',
                'Pagelaran',
                'Pakis',
                'Pakisaji',
                'Poncokusumo',
                'Pujon',
                'Singosari',
                'Sumbermanjing Wetan',
                'Sumberpucung',
                'Tajinan',
                'Tirtoyudo',
                'Tumpang',
                'Turen',
                'Wagir',
                'Wajak',
                'Wonosari',
            ],

            'Kabupaten Pasuruan' => [
                'Bangil',
                'Beji',
                'Gempol',
                'Gondang Wetan',
                'Grati',
                'Kejayan',
                'Kraton',
                'Lekok',
                'Lumbang',
                'Nguling',
                'Pandaan',
                'Pasrepan',
                'Prigen',
                'Pohjentrek',
                'Purwodadi',
                'Purwosari',
                'Puspo',
                'Rejoso',
                'Rembang',
                'Sukorejo',
                'Tosari',
                'Tutur',
                'Winongan',
                'Wonorejo',
            ],

            'Kota Pasuruan' => [
                'Bugulkidul',
                'Gadingrejo',
                'Panggungrejo',
                'Purworejo',
            ],
        ];

        foreach ($data as $kota => $kecamatans) {
            foreach ($kecamatans as $kecamatan) {
                Wilayah::firstOrCreate([
                    'kota' => $kota,
                    'kecamatan' => $kecamatan,
                ]);
            }
        }
    }
}
