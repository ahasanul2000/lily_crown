<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class TestimonialCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Testimonial::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/testimonial');
        CRUD::setEntityNameStrings('testimonial', 'testimonials');
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
        CRUD::column('content')->limit(80);
        CRUD::column('rating');
        CRUD::column('is_active')->type('boolean')->label('Active');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'image' => ValidUpload::field('nullable')->file('image|max:2048'),
        ]);

        CRUD::field('name')->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('role')->type('text')->label('Location / title')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('content')->type('textarea')->label('Review');
        CRUD::field('rating')->type('select_from_array')->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★'])->default(5)->allows_null(false);
        CRUD::field('image')->type('upload')->label('Photo')->withFiles(['disk' => 'public', 'path' => 'testimonials']);
        CRUD::field('sort_order')->type('number')->default(0);
        CRUD::field('is_active')->type('switch')->label('Active')->default(true);
    }
}
