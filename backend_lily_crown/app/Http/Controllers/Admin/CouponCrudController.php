<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CouponCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Coupon::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/coupon');
        CRUD::setEntityNameStrings('coupon', 'coupons');
        CRUD::orderBy('id');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('id', 'desc');
        CRUD::column('code');
        CRUD::column('value')->type('closure')->label('Discount')->function(
            fn ($c) => $c->type === 'fixed' ? '৳ '.number_format($c->value) : rtrim(rtrim(number_format($c->value, 2), '0'), '.').'%'
        );
        CRUD::column('min_order_amount')->type('number')->prefix('৳ ')->decimals(0)->label('Min order');
        CRUD::column('used_count')->type('closure')->label('Used')->function(fn ($c) => $c->used_count.($c->usage_limit ? ' / '.$c->usage_limit : ''));
        CRUD::column('expires_at')->type('datetime')->label('Expires');
        CRUD::column('is_active')->type('boolean')->label('Active');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'code' => 'required|string|max:50|alpha_dash|unique:coupons,code,'.(CRUD::getCurrentEntryId() ?: 'NULL'),
            'type' => 'required|in:percent,fixed',
            'value' => ['required', 'numeric', 'min:0.01', request('type') === 'percent' ? 'max:100' : 'max:10000000'],
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        CRUD::field('code')->type('text')->hint('Customers type this at checkout, e.g. EID20. Saved in capitals.');
        CRUD::field('type')->type('select_from_array')->options(['percent' => 'Percentage (%)', 'fixed' => 'Fixed amount (৳)'])
            ->allows_null(false)->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('value')->type('number')->attributes(['step' => '0.01'])->label('Discount value')
            ->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('min_order_amount')->type('number')->prefix('৳')->default(0)->label('Minimum order amount')
            ->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('max_discount')->type('number')->prefix('৳')->label('Maximum discount')
            ->hint('Optional cap for percentage coupons.')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('usage_limit')->type('number')->hint('Total number of orders that can use it. Leave empty for unlimited.')
            ->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('used_count')->type('number')->default(0)->label('Times used')->attributes(['readonly' => 'readonly'])
            ->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('starts_at')->type('datetime')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('expires_at')->type('datetime')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('is_active')->type('switch')->label('Active')->default(true);
    }
}
