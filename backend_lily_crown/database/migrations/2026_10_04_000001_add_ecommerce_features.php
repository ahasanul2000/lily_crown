<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            // Used when the product has no size variants.
            $table->unsignedInteger('stock')->default(0);
            // [{size, stock}] — per-size inventory.
            $table->json('variants')->nullable();
            $table->json('colors')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number')->nullable()->unique();
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('city')->nullable();
            $table->string('shipping_area')->nullable();
            $table->text('note')->nullable();
            $table->text('admin_note')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->string('payment_method')->default('cod');
            $table->string('payment_status')->default('unpaid');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_email')->nullable()->change();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_image')->nullable();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percent'); // percent | fixed
            $table->decimal('value', 10, 2);
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('highlight')->nullable();
            $table->text('subtitle')->nullable();
            $table->string('image');
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable();
            $table->text('content');
            $table->string('image')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->default('gem');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lookbooks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->string('image');
            $table->string('link')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Existing data: flatten gallery [{url}] → [url], details [{info}] stay as-is,
        // and give existing products some stock so they remain purchasable.
        foreach (DB::table('products')->get() as $product) {
            $gallery = json_decode($product->gallery ?? '[]', true) ?: [];
            $gallery = array_values(array_filter(array_map(
                fn ($g) => is_array($g) ? ($g['url'] ?? null) : $g,
                $gallery
            )));
            DB::table('products')->where('id', $product->id)->update([
                'gallery' => json_encode($gallery),
                'stock' => 10,
            ]);
        }

        foreach (DB::table('orders')->whereNull('order_number')->get() as $order) {
            DB::table('orders')->where('id', $order->id)->update([
                'order_number' => 'LC'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                'subtotal' => $order->total,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
        Schema::dropIfExists('lookbooks');
        Schema::dropIfExists('features');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('hero_slides');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('order_items');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->dropColumn([
                'order_number', 'customer_phone', 'shipping_address', 'city', 'shipping_area',
                'note', 'admin_note', 'subtotal', 'discount', 'shipping_cost', 'coupon_code',
                'payment_method', 'payment_status',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sku', 'sale_price', 'stock', 'variants', 'colors',
                'is_active', 'is_featured', 'is_trending', 'sort_order',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['image', 'description', 'sort_order', 'is_active']);
        });
    }
};
