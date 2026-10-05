<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/auth/register', array_merge([
            'name' => 'Rahima',
            'phone' => '+880 1712-345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], $overrides));
    }

    public function test_register_login_and_profile(): void
    {
        $this->register()->assertCreated()
            ->assertJsonPath('data.phone', '01712345678')
            ->assertJsonStructure(['token']);

        $this->register(['email' => 'x@example.com'])->assertUnprocessable()->assertJsonValidationErrors('phone');

        $token = $this->postJson('/api/auth/login', ['login' => '01712345678', 'password' => 'secret123'])
            ->assertOk()->json('token');
        $this->postJson('/api/auth/login', ['login' => '01712345678', 'password' => 'wrong'])
            ->assertUnprocessable();

        $this->withToken($token)->putJson('/api/account', [
            'name' => 'Rahima Begum', 'phone' => '01712345678', 'address' => 'Road 2', 'city' => 'Dhaka', 'shipping_area' => 'inside_dhaka',
        ])->assertOk()->assertJsonPath('data.city', 'Dhaka');

        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->getJson('/api/account')->assertUnauthorized();
    }

    public function test_orders_placed_while_logged_in_appear_in_history(): void
    {
        $token = $this->register()->json('token');
        $category = Category::create(['name' => 'Sarees', 'slug' => 'sarees']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Saree', 'price' => 1000, 'stock' => 5]);
        $order = [
            'customer_name' => 'Rahima', 'customer_phone' => '01712345678', 'shipping_address' => 'Road 2',
            'city' => 'Dhaka', 'shipping_area' => 'inside_dhaka',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $this->withToken($token)->postJson('/api/orders', $order)->assertCreated();
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => ''])->postJson('/api/orders', $order)->assertCreated(); // guest

        $this->withToken($token)->getJson('/api/account/orders')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.order_number', 'LC000001');
    }

    public function test_customers_cannot_use_the_admin_panel(): void
    {
        $customer = User::create(['name' => 'C', 'phone' => '01812345678', 'email' => 'c@example.com', 'password' => 'secret123']);
        $admin = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret123']);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin, 'backpack')->get('/admin/dashboard')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->actingAs($customer, 'backpack')->get('/admin/dashboard')->assertRedirect();

        // And staff can't sign in to the storefront API.
        $this->postJson('/api/auth/login', ['login' => 'a@example.com', 'password' => 'secret123'])->assertUnprocessable();
    }

    public function test_customer_cannot_mass_assign_admin_flag(): void
    {
        $this->register(['is_admin' => true])->assertCreated();

        $this->assertFalse(User::where('phone', '01712345678')->first()->is_admin);
    }
}
