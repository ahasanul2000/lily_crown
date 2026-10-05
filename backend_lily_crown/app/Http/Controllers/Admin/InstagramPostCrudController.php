<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InstagramPostCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\InstagramPost::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/instagram-post');
        CRUD::setEntityNameStrings('instagram post', 'instagram posts');
        CRUD::orderBy('sort_order');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupListOperation()
    {
        CRUD::column('image')->type('thumb')->label('');
        CRUD::column('caption');
        CRUD::column('link')->type('url');
        CRUD::column('is_active')->type('boolean')->label('Active');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'image' => ValidUpload::field('required')->file('image|max:4096'),
            'link' => 'nullable|url',
        ]);

        CRUD::field('image')->type('upload')->withFiles(['disk' => 'public', 'path' => 'instagram']);
        CRUD::field('caption')->type('text');
        CRUD::field('link')->type('url')->hint('Link to the Instagram post.');
        CRUD::field('sort_order')->type('number')->default(0);
        CRUD::field('is_active')->type('switch')->label('Active')->default(true);
    }
}
