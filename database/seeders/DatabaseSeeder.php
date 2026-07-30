<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 2. Create 5 Marketer Users
        $marketers = [];
        for ($i = 1; $i <= 5; $i++) {
            $marketers[] = User::create([
                'name' => "Marketing $i",
                'email' => "marketing$i@example.com",
                'password' => bcrypt('password'),
                'role' => 'marketing',
            ]);
        }

        // 3. Create initial customers for the marketers
        $baseLat = -7.9797;
        $baseLng = 112.6304;

        // Marketing 1
        \App\Models\Customer::create([
            'user_id' => $marketers[0]->id,
            'name' => 'Toko Bangunan Sumber Makmur',
            'phone' => '0812-3456-7801',
            'address' => 'Jl. Soekarno Hatta 112, Malang',
            'latitude' => $baseLat + 0.0123,
            'longitude' => $baseLng - 0.0045,
            'pola' => 'Partai',
            'jenis' => 'Toko Bangunan',
            'status' => 'Lama',
        ]);

        \App\Models\Customer::create([
            'user_id' => $marketers[0]->id,
            'name' => 'Mebel Sumber Rejeki',
            'phone' => '0813-2211-9087',
            'address' => 'Jl. Kolonel Sugiono 45, Malang',
            'latitude' => $baseLat - 0.0089,
            'longitude' => $baseLng + 0.0112,
            'pola' => 'Eceran',
            'jenis' => 'Mebel',
            'status' => 'Lama',
        ]);

        // Marketing 2
        \App\Models\Customer::create([
            'user_id' => $marketers[1]->id,
            'name' => 'UD Cahaya Kayu',
            'phone' => '0857-4433-2201',
            'address' => 'Jl. Raya Blimbing 8, Malang',
            'latitude' => $baseLat + 0.0045,
            'longitude' => $baseLng + 0.0078,
            'pola' => 'Partai',
            'jenis' => 'Reseller',
            'status' => 'Baru',
        ]);

        \App\Models\Customer::create([
            'user_id' => $marketers[1]->id,
            'name' => 'Pabrik Furniture Nusantara',
            'phone' => '0821-9988-1200',
            'address' => 'Karangploso, Kab. Malang',
            'latitude' => $baseLat - 0.0156,
            'longitude' => $baseLng - 0.0134,
            'pola' => 'Partai',
            'jenis' => 'Pabrik Lain',
            'status' => 'Lama',
        ]);

        // 4. Create Initial Products
        $products = [
            ['name' => 'Plywood 3mm', 'unit' => 'lembar'],
            ['name' => 'Plywood 6mm', 'unit' => 'lembar'],
            ['name' => 'Plywood 9mm', 'unit' => 'lembar'],
            ['name' => 'Plywood 12mm', 'unit' => 'lembar'],
            ['name' => 'Plywood 18mm', 'unit' => 'lembar'],
            ['name' => 'Triplek Melamin', 'unit' => 'lembar'],
            ['name' => 'Blockboard', 'unit' => 'lembar'],
            ['name' => 'MDF 18mm', 'unit' => 'lembar'],
        ];

        foreach ($products as $p) {
            \App\Models\Product::create($p);
        }
    }
}
