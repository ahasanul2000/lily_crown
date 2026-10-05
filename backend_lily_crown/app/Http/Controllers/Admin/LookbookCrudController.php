<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class LookbookCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Lookbook::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/lookbook');
        CRUD::setEntityNameStrings('lookbook item', 'lookbook');
        CRUD::orderBy('sort_order');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupListOperation()
    {
        CRUD::column('image')->type('thumb')->label('');
        CRUD::column('title');
        CRUD::column('product_id')->type('select')->entity('product')->attribute('name')->model(\App\Models\Product::class)->label('Linked product');
        CRUD::column('sort_order')->label('Order');
        CRUD::column('is_active')->type('boolean')->label('Active');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'title' => 'required|string|max:255',
            'image' => ValidUpload::field('required')->file('image|max:6144'),
        ]);

        CRUD::field('title')->type('text');
        CRUD::field('image')->type('upload')->withFiles(['disk' => 'public', 'path' => 'lookbook']);
        CRUD::field('product_id')->type('select')->entity('product')->attribute('name')->model(\App\Models\Product::class)->label('Linked product')->allows_null(true);
        CRUD::field('category_name')->type('text')->hint('Optional label when no product is linked.');
        CRUD::field('sort_order')->type('number')->default(0);
        CRUD::field('is_active')->type('switch')->label('Active')->default(true);
    }
}
