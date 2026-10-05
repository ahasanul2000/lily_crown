<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Feature;
use App\Models\HeroSlide;
use App\Models\InstagramPost;
use App\Models\Lookbook;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Store settings plus demo catalogue/homepage content. Safe to re-run: every row is upserted.
 * Images start as placeholders; DemoPhotosSeeder (called at the end) swaps in real photos.
 */
class StoreSeeder extends Seeder
{
    private function img(string $seed, int $w = 800, int $h = 1067): string
    {
        return "https://picsum.photos/seed/lily-{$seed}/{$w}/{$h}";
    }

    public function run(): void
    {
        $settings = [
            ['key' => 'brand_name', 'value' => 'Lily Crown', 'type' => 'text'],
            ['key' => 'currency_symbol', 'value' => '৳', 'type' => 'text'],
            ['key' => 'shipping_inside_dhaka', 'value' => '70', 'type' => 'number'],
            ['key' => 'shipping_outside_dhaka', 'value' => '130', 'type' => 'number'],
            ['key' => 'free_shipping_threshold', 'value' => '5000', 'type' => 'number'],
            ['key' => 'contact_phone', 'value' => '+880 1234 567890', 'type' => 'text'],
            ['key' => 'contact_address', 'value' => 'Gulshan-2, Dhaka, Bangladesh', 'type' => 'text'],
            ['key' => 'announcement_text', 'value' => 'Cash on Delivery all over Bangladesh • Free delivery on orders over ৳5,000', 'type' => 'text'],
        ];
        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        Coupon::updateOrCreate(['code' => 'WELCOME10'], [
            'type' => 'percent', 'value' => 10, 'min_order_amount' => 1000, 'max_discount' => 500, 'is_active' => true,
        ]);
        Coupon::updateOrCreate(['code' => 'FLAT200'], [
            'type' => 'fixed', 'value' => 200, 'min_order_amount' => 2500, 'is_active' => true,
        ]);

        $categories = [
            ['name' => 'Sarees', 'slug' => 'sarees', 'icon' => 'fa-person-dress', 'sort_order' => 1],
            ['name' => 'Three Piece', 'slug' => 'three-piece', 'icon' => 'fa-shirt', 'sort_order' => 2],
            ['name' => 'Kurtis', 'slug' => 'kurtis', 'icon' => 'fa-vest', 'sort_order' => 3],
            ['name' => 'Lehengas', 'slug' => 'lehengas', 'icon' => 'fa-crown', 'sort_order' => 4],
        ];
        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat + ['image' => $this->img('cat-'.$cat['slug'], 800, 1000)]);
        }
        foreach (Category::whereNull('image')->get() as $cat) {
            $cat->update(['image' => $this->img('cat-'.$cat->slug, 800, 1000)]);
        }

        $sizes = fn (array $stock) => collect($stock)->map(fn ($s, $size) => ['size' => $size, 'stock' => $s])->values()->all();
        $products = [
            ['slug' => 'royal-red-jamdani-saree', 'category' => 'sarees', 'name' => 'Royal Red Jamdani Saree', 'price' => 6500, 'sale_price' => 5800, 'badge' => 'Bestseller', 'stock' => 8, 'is_featured' => true, 'is_trending' => true, 'colors' => ['Red', 'Maroon']],
            ['slug' => 'ivory-muslin-saree', 'category' => 'sarees', 'name' => 'Ivory Muslin Saree', 'price' => 9200, 'badge' => 'Heritage', 'stock' => 4, 'is_featured' => true],
            ['slug' => 'emerald-cotton-three-piece', 'category' => 'three-piece', 'name' => 'Emerald Cotton Three Piece', 'price' => 3200, 'sale_price' => 2750, 'badge' => 'New', 'variants' => ['S' => 5, 'M' => 8, 'L' => 6, 'XL' => 2], 'is_trending' => true, 'colors' => ['Emerald', 'Navy']],
            ['slug' => 'blush-lawn-three-piece', 'category' => 'three-piece', 'name' => 'Blush Lawn Three Piece', 'price' => 2800, 'variants' => ['S' => 3, 'M' => 0, 'L' => 4], 'is_featured' => true],
            ['slug' => 'indigo-block-print-kurti', 'category' => 'kurtis', 'name' => 'Indigo Block Print Kurti', 'price' => 1450, 'variants' => ['S' => 10, 'M' => 12, 'L' => 9, 'XL' => 5, 'XXL' => 3], 'is_trending' => true],
            ['slug' => 'mustard-embroidered-kurti', 'category' => 'kurtis', 'name' => 'Mustard Embroidered Kurti', 'price' => 1850, 'sale_price' => 1590, 'badge' => 'Sale', 'variants' => ['M' => 6, 'L' => 6, 'XL' => 4]],
            ['slug' => 'bridal-maroon-lehenga', 'category' => 'lehengas', 'name' => 'Bridal Maroon Lehenga', 'price' => 24500, 'badge' => 'Bridal', 'variants' => ['S' => 1, 'M' => 2, 'L' => 1], 'is_featured' => true, 'is_trending' => true],
            ['slug' => 'pastel-party-lehenga', 'category' => 'lehengas', 'name' => 'Pastel Party Lehenga', 'price' => 15800, 'sale_price' => 13900, 'variants' => ['S' => 2, 'M' => 3, 'L' => 2], 'colors' => ['Mint', 'Peach', 'Lilac']],
        ];

        foreach ($products as $i => $p) {
            $category = Category::where('slug', $p['category'])->first();
            Product::updateOrCreate(['slug' => $p['slug']], [
                'category_id' => $category->id,
                'name' => $p['name'],
                'sku' => 'LC-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'description' => "{$p['name']} — handcrafted by artisans of Bengal with fine finishing, breathable fabric and a timeless silhouette.",
                'price' => $p['price'],
                'sale_price' => $p['sale_price'] ?? null,
                'badge' => $p['badge'] ?? null,
                'stock' => $p['stock'] ?? 0,
                'variants' => isset($p['variants']) ? $sizes($p['variants']) : [],
                'colors' => $p['colors'] ?? [],
                'image' => $this->img($p['slug']),
                'gallery' => [$this->img($p['slug']), $this->img($p['slug'].'-2'), $this->img($p['slug'].'-3')],
                'details' => [['info' => 'Handcrafted in Bangladesh'], ['info' => 'Gentle hand wash / dry clean'], ['info' => 'Colour may vary slightly due to lighting']],
                'is_active' => true,
                'is_featured' => $p['is_featured'] ?? false,
                'is_trending' => $p['is_trending'] ?? false,
                'sort_order' => $i,
            ]);
        }

        $slides = [
            ['title' => 'Heirlooms Woven For Royalty', 'highlight' => 'Royalty', 'subtitle' => 'Handwoven Jamdani and Muslin from the looms of Bengal.', 'button_text' => 'Shop Sarees', 'button_link' => '/shop?category=sarees'],
            ['title' => 'The Festive Edit Has Arrived', 'highlight' => 'Festive', 'subtitle' => 'Three pieces, kurtis and lehengas for every celebration.', 'button_text' => 'Explore Collection', 'button_link' => '/shop'],
            ['title' => 'Bridal Couture Made To Treasure', 'highlight' => 'Bridal', 'subtitle' => 'Statement lehengas crafted for your most precious day.', 'button_text' => 'View Lehengas', 'button_link' => '/shop?category=lehengas'],
        ];
        foreach ($slides as $i => $slide) {
            HeroSlide::updateOrCreate(['title' => $slide['title']], $slide + ['image' => $this->img('hero-'.$i, 1200, 1500), 'sort_order' => $i, 'is_active' => true]);
        }

        $testimonials = [
            ['name' => 'Nusrat Jahan', 'role' => 'Dhaka', 'content' => 'The Jamdani I ordered was even more beautiful in person. Delivery was quick and I paid cash at my door.'],
            ['name' => 'Farhana Akter', 'role' => 'Chattogram', 'content' => 'Perfect fit on my three piece thanks to the size guide. Will definitely order again!'],
            ['name' => 'Sumaiya Rahman', 'role' => 'Sylhet', 'content' => 'My bridal lehenga was a dream. The team helped me pick the right size over WhatsApp.'],
        ];
        foreach ($testimonials as $i => $t) {
            Testimonial::updateOrCreate(['name' => $t['name']], $t + ['rating' => 5, 'sort_order' => $i, 'is_active' => true]);
        }

        $features = [
            ['title' => 'Authentic Craft', 'description' => 'Every piece is sourced directly from master weavers.', 'icon' => 'gem'],
            ['title' => 'Cash on Delivery', 'description' => 'Pay when your order arrives, anywhere in Bangladesh.', 'icon' => 'truck'],
            ['title' => 'Easy Exchange', 'description' => 'Hassle-free size exchange within 7 days.', 'icon' => 'shield'],
            ['title' => 'Premium Quality', 'description' => 'Quality checked and carefully packed for you.', 'icon' => 'crown'],
        ];
        foreach ($features as $i => $f) {
            Feature::updateOrCreate(['title' => $f['title']], $f + ['sort_order' => $i, 'is_active' => true]);
        }

        foreach (range(1, 6) as $i) {
            InstagramPost::updateOrCreate(['caption' => "Lily Crown style #$i"], [
                'image' => $this->img("insta-$i", 600, 600), 'link' => 'https://instagram.com/', 'sort_order' => $i, 'is_active' => true,
            ]);
        }

        foreach (Product::where('is_featured', true)->get() as $i => $product) {
            Lookbook::updateOrCreate(['title' => $product->name.' Look'], [
                'image' => $this->img('look-'.$product->slug, 800, 1100),
                'product_id' => $product->id,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        $this->call(DemoPhotosSeeder::class);
    }
}
