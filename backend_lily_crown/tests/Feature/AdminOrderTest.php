<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_render_and_quick_status_updates_order(): void
    {
        $admin = User::create(['name' => 'Admin User', 'email' => 'a@example.com', 'password' => 'secret123']);
        $admin->forceFill(['is_admin' => true])->save();

        $category = Category::create(['name' => 'Sarees', 'slug' => 'sarees']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Saree', 'price' => 1000, 'stock' => 3]);
        $this->postJson('/api/orders', [
            'customer_name' => 'Rahima', 'customer_phone' => '01712345678', 'shipping_address' => 'Road 2',
            'city' => 'Dhaka', 'shipping_area' => 'inside_dhaka',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated();
        $order = Order::first();

        $this->actingAs($admin, 'backpack');
        foreach (['customer', 'dashboard', 'order', 'order?status=pending', "order/{$order->id}/show", 'product'] as $page) {
            // Backpack keeps the CRUD panel in a scoped binding; reset it like a fresh HTTP request would.
            $this->app->forgetScopedInstances();
            \Illuminate\Support\Facades\Facade::clearResolvedInstances();
            $this->get('/admin/'.$page)->assertOk();
        }

        $this->post("/admin/order/{$order->id}/status", ['status' => 'confirmed'])->assertRedirect();
        $this->assertSame('confirmed', $order->fresh()->status);

        $this->post("/admin/order/{$order->id}/status", ['payment_status' => 'paid'])->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh()->status);

        $this->post("/admin/order/{$order->id}/status", ['status' => 'cancelled']);
        $this->assertSame(3, $product->fresh()->stock);

        $this->post("/admin/order/{$order->id}/status", ['status' => 'bogus'])->assertSessionHasErrors('status');
    }
}
