<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Storefront customer accounts (read-only; customers manage their own profile).
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CustomerCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(User::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/customer');
        CRUD::setEntityNameStrings('customer', 'customers');
        CRUD::addBaseClause('where', 'is_admin', false);
        CRUD::orderBy('id', 'desc');
    }

    protected function setupListOperation()
    {
        CRUD::column('name');
        CRUD::column('phone');
        CRUD::column('email');
        CRUD::column('city');
        CRUD::column('orders')->type('relationship_count')->label('Orders')->suffix(' orders');
        CRUD::column('created_at')->type('datetime')->label('Joined');
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
        CRUD::column('address');
        CRUD::column('order_history')->type('closure')->escaped(false)->label('Recent orders')->function(function (User $user) {
            return $user->orders()->latest('id')->limit(20)->get()->map(fn ($o) => sprintf(
                '<a href="%s">%s</a> — ৳ %s — %s',
                backpack_url('order/'.$o->id.'/show'), e($o->order_number), number_format($o->total), e($o->status_label)
            ))->implode('<br>') ?: '—';
        });
    }
}
