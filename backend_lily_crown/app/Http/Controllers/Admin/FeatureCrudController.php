<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class FeatureCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Feature::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/feature');
        CRUD::setEntityNameStrings('feature', 'features');
        CRUD::orderBy('sort_order');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupListOperation()
    {
        CRUD::column('title');
        CRUD::column('icon');
        CRUD::column('description')->limit(80);
        CRUD::column('is_active')->type('boolean')->label('Active');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(['title' => 'required|string|max:255']);

        CRUD::field('title')->type('text');
        CRUD::field('description')->type('textarea');
        CRUD::field('icon')->type('select_from_array')->options(['gem' => 'Gem', 'crown' => 'Crown', 'shield' => 'Shield', 'truck' => 'Truck'])->allows_null(false);
        CRUD::field('sort_order')->type('number')->default(0);
        CRUD::field('is_active')->type('switch')->label('Active')->default(true);
    }
}
