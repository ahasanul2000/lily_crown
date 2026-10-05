<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Services\CheckoutService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Orders come from the storefront; admins review them and move them through statuses.
 * Changing status to cancelled/returned puts the stock back (see Order::booted()).
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OrderCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Order::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/order');
        CRUD::setEntityNameStrings('order', 'orders');
        CRUD::orderBy('id', 'desc');
    }

    protected function setupInvoiceRoutes($segment, $routeName, $controller)
    {
        Route::get($segment.'/{id}/invoice', [
            'as' => $routeName.'.invoice',
            'uses' => $controller.'@invoice',
            'operation' => 'invoice',
        ]);

        Route::post($segment.'/{id}/status', [
            'as' => $routeName.'.quick-status',
            'uses' => $controller.'@quickStatus',
            'operation' => 'invoice',
        ]);
    }

    protected function setupInvoiceDefaults()
    {
        CRUD::allowAccess('invoice');
        CRUD::operation(['list', 'show'], function () {
            CRUD::addButtonFromView('line', 'invoice', 'order_invoice', 'beginning');
        });
    }

    public function invoice($id)
    {
        CRUD::hasAccessOrFail('invoice');

        return view('admin.order_invoice', [
            'order' => Order::with('orderItems')->findOrFail($id),
            'areas' => CheckoutService::SHIPPING_AREAS,
        ]);
    }

    /** One-click status / payment change from the order page (fires the same restock logic as edit). */
    public function quickStatus(Request $request, $id)
    {
        CRUD::hasAccessOrFail('update');

        $data = $request->validate([
            'status' => 'nullable|in:'.implode(',', array_keys(Order::STATUSES)),
            'payment_status' => 'nullable|in:'.implode(',', array_keys(Order::PAYMENT_STATUSES)),
        ]);

        $order = Order::findOrFail($id);
        $order->update(array_filter($data));
        \Alert::success("Order {$order->order_number} updated.")->flash();

        return redirect()->back();
    }

    protected function setupListOperation()
    {
        Widget::add()->to('before_content')->type('view')->view('admin.order_status_tabs');

        // Simple status filter via ?status=pending (Backpack filters are a PRO add-on).
        if ($status = request('status')) {
            CRUD::addClause('where', 'status', $status);
        }

        CRUD::column('order_number')->label('Order #');
        CRUD::column('customer_name')->label('Customer');
        CRUD::column('customer_phone')->label('Phone');
        CRUD::column('city');
        CRUD::column('total')->type('number')->prefix('৳ ')->decimals(0);
        CRUD::column('status')->type('closure')->escaped(false)->function(fn (Order $o) => self::statusBadge($o->status))
            ->searchLogic(fn ($q, $col, $term) => $q->orWhere('status', 'like', "%$term%"));
        CRUD::column('payment_status')->type('select_from_array')->options(Order::PAYMENT_STATUSES)->label('Payment');
        CRUD::column('created_at')->type('datetime')->label('Placed at');
    }

    protected function setupShowOperation()
    {
        CRUD::setShowView('admin.order_show');

        CRUD::column('order_number')->label('Order #');
        CRUD::column('status')->type('closure')->escaped(false)->function(fn (Order $o) => self::statusBadge($o->status));
        CRUD::column('payment_method')->type('closure')->function(fn (Order $o) => $o->payment_method === 'cod' ? 'Cash on Delivery' : $o->payment_method);
        CRUD::column('payment_status')->type('select_from_array')->options(Order::PAYMENT_STATUSES);
        CRUD::column('customer_name')->label('Customer');
        CRUD::column('customer_phone')->label('Phone');
        CRUD::column('customer_email')->label('Email');
        CRUD::column('shipping_address')->label('Address');
        CRUD::column('city');
        CRUD::column('shipping_area')->type('select_from_array')->options(CheckoutService::SHIPPING_AREAS)->label('Delivery area');
        CRUD::column('note')->label('Customer note');
        CRUD::column('order_items')->type('view')->view('admin.order_items')->label('Items');
        CRUD::column('admin_note')->label('Admin note');
        CRUD::column('created_at')->type('datetime')->label('Placed at');
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation([
            'status' => 'required|in:'.implode(',', array_keys(Order::STATUSES)),
            'payment_status' => 'required|in:'.implode(',', array_keys(Order::PAYMENT_STATUSES)),
        ]);

        if ($entry = CRUD::getCurrentEntry()) {
            CRUD::field('order_summary')->type('custom_html')
                ->value('<label>'.e($entry->order_number).' — items</label>'.view('admin.order_items', ['entry' => $entry])->render());
        }
        CRUD::field('status')->type('select_from_array')->options(Order::STATUSES)->allows_null(false)
            ->hint('Setting Cancelled or Returned puts the items back in stock.')
            ->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('payment_status')->type('select_from_array')->options(Order::PAYMENT_STATUSES)->allows_null(false)
            ->hint('Mark as Paid once the courier hands over the cash.')
            ->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('customer_name')->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('customer_phone')->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('shipping_address')->type('textarea');
        CRUD::field('city')->type('text');
        CRUD::field('admin_note')->type('textarea')->hint('Internal only — e.g. courier tracking number.');
    }

    private static function statusBadge(?string $status): string
    {
        $colors = [
            'pending' => 'warning', 'confirmed' => 'info', 'processing' => 'primary', 'shipped' => 'azure',
            'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'secondary',
        ];
        $label = e(Order::STATUSES[$status] ?? $status);
        $color = $colors[$status] ?? 'secondary';

        return "<span class=\"badge bg-{$color} text-white\">{$label}</span>";
    }
}
