<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CategoryCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Category::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/category');
        CRUD::setEntityNameStrings('category', 'categories');
        CRUD::orderBy('sort_order');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupListOperation()
    {
        CRUD::column('image')->type('thumb')->label('');
        CRUD::column('name');
        CRUD::column('slug');
        CRUD::column('products')->type('relationship_count')->label('Products')->suffix(' products');
        CRUD::column('sort_order')->label('Order');
        CRUD::column('is_active')->type('boolean')->label('Active');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,'.(CRUD::getCurrentEntryId() ?: 'NULL'),
            'image' => ValidUpload::field('nullable')->file('image|max:4096'),
        ]);

        CRUD::field('name')->type('text');
        CRUD::field('slug')->type('text')->hint('Used in the URL, e.g. /shop?category=sarees. Leave empty to generate.');
        CRUD::field('description')->type('textarea');
        CRUD::field('image')->type('upload')->withFiles(['disk' => 'public', 'path' => 'categories'])->hint('Shown on the homepage category grid.');
        CRUD::field('icon')->type('text')->hint('Optional Font Awesome class, e.g. fa-person-dress');
        CRUD::field('sort_order')->type('number')->default(0)->hint('Lower numbers appear first.');
        CRUD::field('is_active')->type('switch')->label('Visible on website')->default(true);
    }
}
