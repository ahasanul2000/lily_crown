<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Product $kurti;

    private Product $saree;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Kurtis', 'slug' => 'kurtis']);
        $this->kurti = Product::create([
            'category_id' => $category->id, 'name' => 'Kurti', 'price' => 1000,
            'variants' => [['size' => 'M', 'stock' => 3], ['size' => 'L', 'stock' => 0]],
        ]);
        $this->saree = Product::create([
            'category_id' => $category->id, 'name' => 'Saree', 'price' => 5000, 'sale_price' => 4000, 'stock' => 2,
            'colors' => ['Red'],
        ]);

        SiteSetting::create(['key' => 'shipping_inside_dhaka', 'value' => '70']);
        SiteSetting::create(['key' => 'shipping_outside_dhaka', 'value' => '130']);
        SiteSetting::create(['key' => 'free_shipping_threshold', 'value' => '5000']);
    }

    private function customer(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Rahima',
            'customer_phone' => '01712345678',
            'shipping_address' => 'House 1, Road 2',
            'city' => 'Dhaka',
            'shipping_area' => 'outside_dhaka',
        ], $overrides);
    }

    public function test_order_is_priced_on_the_server_and_stock_is_reserved(): void
    {
        $response = $this->postJson('/api/orders', $this->customer([
            'total' => 1,
            'items' => [
                ['product_id' => $this->kurti->id, 'size' => 'M', 'quantity' => 2, 'price' => 1],
            ],
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 2000)
            ->assertJsonPath('data.shipping_cost', 130)
            ->assertJsonPath('data.total', 2130)
            ->assertJsonPath('data.payment_method', 'cod');

        $this->assertSame(1, $this->kurti->fresh()->stockFor('M'));
        $this->assertSame('LC000001', Order::first()->order_number);
    }

    public function test_sale_price_and_free_shipping_threshold_apply(): void
    {
        $this->postJson('/api/checkout/quote', [
            'items' => [['product_id' => $this->saree->id, 'quantity' => 2]],
            'shipping_area' => 'inside_dhaka',
        ])
            ->assertJsonPath('data.subtotal', 8000)
            ->assertJsonPath('data.shipping_cost', 0)
            ->assertJsonPath('data.total', 8000);
    }

    public function test_cannot_order_more_than_stock_or_without_a_size(): void
    {
        $this->postJson('/api/orders', $this->customer([
            'items' => [['product_id' => $this->kurti->id, 'size' => 'M', 'quantity' => 4]],
        ]))->assertUnprocessable()->assertJsonValidationErrors('items.0');

        $this->postJson('/api/orders', $this->customer([
            'items' => [['product_id' => $this->kurti->id, 'size' => 'L', 'quantity' => 1]],
        ]))->assertUnprocessable();

        $this->postJson('/api/orders', $this->customer([
            'items' => [['product_id' => $this->kurti->id, 'quantity' => 1]],
        ]))->assertUnprocessable();

        // Two lines for the same size can't jointly exceed stock either.
        $this->postJson('/api/orders', $this->customer([
            'items' => [
                ['product_id' => $this->kurti->id, 'size' => 'M', 'quantity' => 2],
                ['product_id' => $this->kurti->id, 'size' => 'M', 'quantity' => 2],
            ],
        ]))->assertUnprocessable();

        $this->assertSame(0, Order::count());
        $this->assertSame(3, $this->kurti->fresh()->stockFor('M'));
    }

    public function test_coupon_discount_minimum_and_usage_limit(): void
    {
        Coupon::create(['code' => 'save10', 'type' => 'percent', 'value' => 10, 'min_order_amount' => 1500, 'max_discount' => 150, 'usage_limit' => 1]);

        $this->postJson('/api/checkout/quote', [
            'items' => [['product_id' => $this->kurti->id, 'size' => 'M', 'quantity' => 1]],
            'coupon_code' => 'SAVE10',
        ])->assertJsonPath('data.discount', 0)->assertJsonPath('data.coupon_code', null);

        $this->postJson('/api/orders', $this->customer([
            'coupon_code' => 'save10',
            'items' => [['product_id' => $this->kurti->id, 'size' => 'M', 'quantity' => 2]],
        ]))->assertCreated()->assertJsonPath('data.discount', 150)->assertJsonPath('data.total', 1980);

        $this->postJson('/api/orders', $this->customer([
            'coupon_code' => 'SAVE10',
            'items' => [['product_id' => $this->saree->id, 'quantity' => 1]],
        ]))->assertUnprocessable()->assertJsonValidationErrors('coupon_code');
    }

    public function test_cancelling_an_order_restores_stock(): void
    {
        $this->postJson('/api/orders', $this->customer([
            'items' => [
                ['product_id' => $this->kurti->id, 'size' => 'M', 'quantity' => 1],
                ['product_id' => $this->saree->id, 'color' => 'Red', 'quantity' => 2],
            ],
        ]))->assertCreated();

        $this->assertSame(0, $this->saree->fresh()->stock);

        Order::first()->update(['status' => 'cancelled']);

        $this->assertSame(2, $this->saree->fresh()->stock);
        $this->assertSame(3, $this->kurti->fresh()->stockFor('M'));
    }

    public function test_order_tracking_requires_matching_phone(): void
    {
        $this->postJson('/api/orders', $this->customer([
            'items' => [['product_id' => $this->kurti->id, 'size' => 'M', 'quantity' => 1]],
        ]))->assertCreated();

        $this->getJson('/api/orders/track?order_number=lc000001&phone=%2B8801712345678')
            ->assertOk()->assertJsonPath('data.status', 'pending');

        $this->getJson('/api/orders/track?order_number=LC000001&phone=01811111111')->assertNotFound();
    }

    public function test_price_filter_uses_the_sale_price(): void
    {
        $this->getJson('/api/products?min_price=0')->assertJsonCount(2, 'data');
        $this->getJson('/api/products?max_price=4500')->assertJsonCount(2, 'data');
        $this->getJson('/api/products?min_price=2000&max_price=4500')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Saree');
    }

    public function test_inactive_products_are_hidden(): void
    {
        $this->saree->update(['is_active' => false]);

        $this->getJson('/api/products')->assertJsonCount(1, 'data');
        $this->getJson('/api/products/'.$this->saree->id)->assertNotFound();
    }
}
