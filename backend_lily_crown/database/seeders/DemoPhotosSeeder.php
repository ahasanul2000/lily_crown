<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\InstagramPost;
use App\Models\Lookbook;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Replaces the demo placeholders with real clothing photos from Pexels (free for commercial use,
 * https://www.pexels.com/license/). Photos are downloaded once into storage/app/public/demo so the
 * site doesn't hotlink. Replace them with your own product photos from the admin panel.
 */
class DemoPhotosSeeder extends Seeder
{
    /** product slug => [main photo, ...gallery] (Pexels photo ids) */
    private const PRODUCTS = [
        'monarch-dhakai-jamdani' => [30004204, 7516956],
        'royal-red-jamdani-saree' => [20161456, 37880205, 11629757],
        'ivory-muslin-saree' => [37054322, 14205210, 29172782],
        'emerald-cotton-three-piece' => [20690508, 20690513, 20690516],
        'blush-lawn-three-piece' => [20690519, 20407233, 36634909],
        'indigo-block-print-kurti' => [20791992, 20407198],
        'mustard-embroidered-kurti' => [33824984, 20777176],
        'bridal-maroon-lehenga' => [13022612, 12792004, 34345410],
        'pastel-party-lehenga' => [5721528, 16125951, 37628608],
    ];

    private const CATEGORIES = [
        'sarees' => 17113983,
        'three-piece' => 20690539,
        'kurtis' => 34933700,
        'lehengas' => 13022537,
    ];

    /** hero slide sort_order => photo */
    private const HERO = [0 => 28943474, 1 => 22064201, 2 => 12791932];

    private const LOOKBOOK = [16651974, 12062663, 28058186, 34368242];

    private const INSTAGRAM = [11629757, 28316406, 7326221, 20690539, 37628619, 13661845];

    public function run(): void
    {
        foreach (self::PRODUCTS as $slug => $ids) {
            $photos = array_map(fn ($id) => $this->photo($id), $ids);
            Product::where('slug', $slug)->first()?->update([
                'image' => $photos[0],
                'gallery' => $photos,
            ]);
        }

        // The original sample product lived in "Dresses"; it's a saree.
        $sarees = Category::where('slug', 'sarees')->first();
        if ($sarees) {
            Product::where('slug', 'monarch-dhakai-jamdani')->update(['category_id' => $sarees->id]);
        }

        foreach (self::CATEGORIES as $slug => $id) {
            Category::where('slug', $slug)->update(['image' => $this->photo($id)]);
        }

        foreach (self::HERO as $order => $id) {
            HeroSlide::where('sort_order', $order)->update(['image' => $this->photo($id, 1400)]);
        }

        foreach (Lookbook::orderBy('sort_order')->orderBy('id')->get() as $i => $look) {
            $look->update(['image' => $this->photo(self::LOOKBOOK[$i % count(self::LOOKBOOK)])]);
        }

        foreach (InstagramPost::orderBy('sort_order')->orderBy('id')->get() as $i => $post) {
            $post->update(['image' => $this->photo(self::INSTAGRAM[$i % count(self::INSTAGRAM)], 800)]);
        }
    }

    /** Download a Pexels photo into the public disk (once) and return its storage path. */
    private function photo(int $id, int $width = 1200): string
    {
        $path = "demo/pexels-{$id}-{$width}.jpg";

        if (! Storage::disk('public')->exists($path)) {
            $response = Http::timeout(60)->retry(2, 1000)
                ->get("https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg", [
                    'auto' => 'compress', 'cs' => 'tinysrgb', 'w' => $width,
                ]);
            $response->throw();
            Storage::disk('public')->put($path, $response->body());
        }

        return $path;
    }
}
