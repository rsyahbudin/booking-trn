<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\SeatingSpot;
use App\Models\User;
use App\Models\VariantOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create seating spots
        $spots = [
            ['name' => 'Indoor AC', 'description' => 'Area dalam ruangan dengan AC', 'capacity' => 50],
            ['name' => 'Outdoor Garden', 'description' => 'Area taman terbuka dengan suasana segar', 'capacity' => 30],
            ['name' => 'VIP Room', 'description' => 'Ruangan privat untuk acara eksklusif', 'capacity' => 20],
            ['name' => 'Rooftop', 'description' => 'Area rooftop dengan pemandangan kota', 'capacity' => 25],
        ];

        foreach ($spots as $spot) {
            SeatingSpot::create($spot);
        }

        // Create categories
        $categories = [
            ['name' => 'Paket Buka Puasa', 'description' => 'Paket lengkap untuk buka puasa', 'sort_order' => 1],
            ['name' => 'Makanan Utama', 'description' => 'Hidangan utama pilihan', 'sort_order' => 2],
            ['name' => 'Minuman', 'description' => 'Berbagai minuman segar', 'sort_order' => 3],
            ['name' => 'Dessert', 'description' => 'Hidangan penutup manis', 'sort_order' => 4],
            ['name' => 'Snack', 'description' => 'Camilan ringan', 'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        // Create menus with variants
        $paketCategory = Category::where('name', 'Paket Buka Puasa')->first();
        
        // Paket A
        $paketA = Menu::create([
            'category_id' => $paketCategory->id,
            'name' => 'Paket Hemat A',
            'price' => 45000,
            'description' => 'Nasi + Ayam Goreng + Es Teh Manis + Kurma',
        ]);

        $variantAyam = MenuVariant::create([
            'menu_id' => $paketA->id,
            'name' => 'Pilihan Ayam',
            'is_required' => true,
        ]);
        VariantOption::create(['menu_variant_id' => $variantAyam->id, 'name' => 'Paha', 'price_adjustment' => 0]);
        VariantOption::create(['menu_variant_id' => $variantAyam->id, 'name' => 'Dada', 'price_adjustment' => 5000]);
        VariantOption::create(['menu_variant_id' => $variantAyam->id, 'name' => 'Sayap', 'price_adjustment' => 0]);

        $variantSambal = MenuVariant::create([
            'menu_id' => $paketA->id,
            'name' => 'Pilihan Sambal',
            'is_required' => true,
        ]);
        VariantOption::create(['menu_variant_id' => $variantSambal->id, 'name' => 'Sambal Korek', 'price_adjustment' => 0]);
        VariantOption::create(['menu_variant_id' => $variantSambal->id, 'name' => 'Sambal Matah', 'price_adjustment' => 0]);
        VariantOption::create(['menu_variant_id' => $variantSambal->id, 'name' => 'Sambal Terasi', 'price_adjustment' => 0]);

        // Paket B
        $paketB = Menu::create([
            'category_id' => $paketCategory->id,
            'name' => 'Paket Premium B',
            'price' => 75000,
            'description' => 'Nasi + Iga Bakar + Es Jeruk + Kolak + Kurma',
        ]);

        $variantIga = MenuVariant::create([
            'menu_id' => $paketB->id,
            'name' => 'Tingkat Kematangan',
            'is_required' => true,
        ]);
        VariantOption::create(['menu_variant_id' => $variantIga->id, 'name' => 'Medium', 'price_adjustment' => 0]);
        VariantOption::create(['menu_variant_id' => $variantIga->id, 'name' => 'Well Done', 'price_adjustment' => 0]);

        // Paket C
        $paketC = Menu::create([
            'category_id' => $paketCategory->id,
            'name' => 'Paket Keluarga C',
            'price' => 250000,
            'description' => 'Untuk 4 orang: Nasi 4 porsi + Ayam Goreng 4 pcs + Ikan Bakar + Sambal + Es Teh 4 gelas + Kolak',
        ]);

        // Makanan utama
        $makananCategory = Category::where('name', 'Makanan Utama')->first();
        
        Menu::create([
            'category_id' => $makananCategory->id,
            'name' => 'Nasi Goreng Spesial',
            'price' => 35000,
            'description' => 'Nasi goreng dengan telur, ayam, dan sayuran',
        ]);

        Menu::create([
            'category_id' => $makananCategory->id,
            'name' => 'Soto Ayam',
            'price' => 28000,
            'description' => 'Soto ayam dengan kuah bening dan pelengkap',
        ]);

        Menu::create([
            'category_id' => $makananCategory->id,
            'name' => 'Gado-Gado',
            'price' => 25000,
            'description' => 'Sayuran segar dengan bumbu kacang',
        ]);

        // Minuman
        $minumanCategory = Category::where('name', 'Minuman')->first();
        
        Menu::create([
            'category_id' => $minumanCategory->id,
            'name' => 'Es Teh Manis',
            'price' => 8000,
            'description' => 'Teh manis dingin yang menyegarkan',
        ]);

        Menu::create([
            'category_id' => $minumanCategory->id,
            'name' => 'Es Jeruk',
            'price' => 12000,
            'description' => 'Jeruk peras segar dengan es',
        ]);

        Menu::create([
            'category_id' => $minumanCategory->id,
            'name' => 'Es Kelapa Muda',
            'price' => 15000,
            'description' => 'Kelapa muda segar',
        ]);

        // Dessert
        $dessertCategory = Category::where('name', 'Dessert')->first();
        
        Menu::create([
            'category_id' => $dessertCategory->id,
            'name' => 'Kolak Pisang',
            'price' => 15000,
            'description' => 'Kolak pisang dengan santan gurih',
        ]);

        Menu::create([
            'category_id' => $dessertCategory->id,
            'name' => 'Es Buah',
            'price' => 18000,
            'description' => 'Campuran buah segar dengan sirup',
        ]);

        Menu::create([
            'category_id' => $dessertCategory->id,
            'name' => 'Puding Caramel',
            'price' => 12000,
            'description' => 'Puding lembut dengan saus caramel',
        ]);

        // Snack
        $snackCategory = Category::where('name', 'Snack')->first();
        
        Menu::create([
            'category_id' => $snackCategory->id,
            'name' => 'Gorengan Mix',
            'price' => 15000,
            'description' => 'Pisang goreng, tahu, tempe, dan bakwan',
        ]);

        Menu::create([
            'category_id' => $snackCategory->id,
            'name' => 'Kurma Premium',
            'price' => 20000,
            'description' => 'Kurma pilihan untuk berbuka',
        ]);
    }
}
