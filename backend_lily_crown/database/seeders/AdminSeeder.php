<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Default Admin User
        User::updateOrCreate(
            ['email' => 'admin@lilycrown.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        )->forceFill(['is_admin' => true])->save();

        // Initial Categories
        $categories = [
            ['name' => 'Dresses', 'slug' => 'dresses', 'icon' => 'fa-female'],
            ['name' => 'Jewelry', 'slug' => 'jewelry', 'icon' => 'fa-gem'],
            ['name' => 'Bags', 'slug' => 'bags', 'icon' => 'fa-shopping-bag'],
            ['name' => 'Shoes', 'slug' => 'shoes', 'icon' => 'fa-shoe-prints'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Initial Site Settings
        $settings = [
            ['key' => 'brand_name', 'value' => 'Lily Crown', 'type' => 'text'],
            ['key' => 'contact_email', 'value' => 'concierge@lilycrown.com', 'type' => 'email'],
            ['key' => 'whatsapp_number', 'value' => '+880123456789', 'type' => 'text'],
            ['key' => 'facebook_link', 'value' => 'https://facebook.com/LilyCrown', 'type' => 'url'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // Sample Product
        $dressCat = Category::where('slug', 'dresses')->first();
        if ($dressCat) {
            Product::create([
                'category_id' => $dressCat->id,
                'name' => 'Monarch Dhakai Jamdani',
                'slug' => 'monarch-dhakai-jamdani',
                'description' => 'The Shera (Best) Jamdani from the weavers of Sonargaon.',
                'price' => 850.00,
                'badge' => 'Heritage',
                'image' => 'https://anvicouture.com/cdn/shop/files/4_9_202511_07_21PM.jpg?v=1744319972',
                'gallery' => [['url' => 'https://anvicouture.com/cdn/shop/files/4_9_202511_07_21PM.jpg?v=1744319972']],
                'details' => [['info' => 'Hand-woven'], ['info' => 'Pure Cotton']],
            ]);
        }
    }
}
