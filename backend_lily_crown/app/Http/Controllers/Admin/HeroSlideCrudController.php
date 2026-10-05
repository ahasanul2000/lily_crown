<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class HeroSlideCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\HeroSlide::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/hero-slide');
        CRUD::setEntityNameStrings('hero slide', 'hero slides');
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
        CRUD::column('button_link')->label('Link');
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
        CRUD::field('highlight')->type('text')->hint('A word from the title to show in gold, e.g. "Royalty".');
        CRUD::field('subtitle')->type('textarea');
        CRUD::field('image')->type('upload')->withFiles(['disk' => 'public', 'path' => 'banners'])->hint('Portrait image works best (e.g. 1200×1500).');
        CRUD::field('button_text')->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('button_link')->type('text')->hint('e.g. /shop?category=sarees')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('sort_order')->type('number')->default(0);
        CRUD::field('is_active')->type('switch')->label('Active')->default(true);
    }
}
