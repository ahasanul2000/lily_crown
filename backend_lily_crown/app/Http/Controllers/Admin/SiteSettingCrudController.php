<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SiteSettingRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class SiteSettingCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SiteSettingCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\SiteSetting::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/site-setting');
        CRUD::setEntityNameStrings('site setting', 'site settings');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('key');
        CRUD::column('value');
        CRUD::column('type');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(SiteSettingRequest::class);

        CRUD::field('key')->type('text');
        CRUD::field('type')->type('select_from_array')->options([
            'text' => 'Text',
            'textarea' => 'Textarea',
            'email' => 'Email',
            'url' => 'URL',
            'number' => 'Number',
        ]);
        CRUD::field('value')->type('textarea')->hint('Checkout uses shipping_inside_dhaka, shipping_outside_dhaka and free_shipping_threshold (0 turns free delivery off).');
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
