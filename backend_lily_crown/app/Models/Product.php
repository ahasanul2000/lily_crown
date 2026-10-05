<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use CrudTrait;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'sale_price',
        'stock',
        'variants',
        'colors',
        'badge',
        'image',
        'gallery',
        'details',
        'is_active',
        'is_featured',
        'is_trending',
        'sort_order',
    ];

    protected $casts = [
        'gallery' => 'array',
        'details' => 'array',
        'variants' => 'array',
        'colors' => 'array',
        'price' => 'float',
        'sale_price' => 'float',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
    ];

    protected $appends = ['final_price', 'total_stock', 'in_stock'];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (blank($product->slug)) {
                $base = Str::slug($product->name);
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $product->id ?? 0)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $product->slug = $slug;
            }

            // Normalise row-field input from the admin panel (empty rows, strings, numbers as text).
            $product->variants = self::rows($product->variants)
                ->filter(fn ($v) => filled($v['size'] ?? null))
                ->map(fn ($v) => ['size' => trim($v['size']), 'stock' => max(0, (int) ($v['stock'] ?? 0))])
                ->unique('size')
                ->values()
                ->all();

            $product->colors = self::rows($product->colors, 'name')
                ->filter(fn ($c) => filled($c['name'] ?? null))
                ->map(fn ($c) => ['name' => trim($c['name'])])
                ->values()
                ->all();

            $product->details = self::rows($product->details, 'info')
                ->filter(fn ($d) => filled($d['info'] ?? null))
                ->map(fn ($d) => ['info' => trim($d['info'])])
                ->values()
                ->all();
        });
    }

    /** Coerce a JSON column value into a collection of associative rows. */
    private static function rows(mixed $value, ?string $scalarKey = null)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return collect(is_array($value) ? $value : [])
            ->map(fn ($row) => is_array($row) ? $row : ($scalarKey ? [$scalarKey => $row] : null))
            ->filter();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasVariants(): bool
    {
        return ! empty($this->variants);
    }

    public function getFinalPriceAttribute(): float
    {
        $sale = $this->sale_price;

        return ($sale !== null && $sale > 0 && $sale < $this->price) ? (float) $sale : (float) $this->price;
    }

    public function getTotalStockAttribute(): int
    {
        if ($this->hasVariants()) {
            return (int) collect($this->variants)->sum('stock');
        }

        return (int) $this->stock;
    }

    public function getInStockAttribute(): bool
    {
        return $this->total_stock > 0;
    }

    public function stockFor(?string $size): int
    {
        if (! $this->hasVariants()) {
            return (int) $this->stock;
        }

        $variant = collect($this->variants)->firstWhere('size', $size);

        return $variant ? (int) $variant['stock'] : 0;
    }

    /** Positive $delta adds stock, negative removes it. */
    public function adjustStock(?string $size, int $delta): void
    {
        if ($this->hasVariants()) {
            $this->variants = collect($this->variants)
                ->map(function ($v) use ($size, $delta) {
                    if ($v['size'] === $size) {
                        $v['stock'] = max(0, (int) $v['stock'] + $delta);
                    }

                    return $v;
                })
                ->all();
        } else {
            $this->stock = max(0, (int) $this->stock + $delta);
        }

        $this->save();
    }
}
