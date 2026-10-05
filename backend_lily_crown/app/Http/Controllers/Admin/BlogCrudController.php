<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BlogRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class BlogCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BlogCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Blog::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/blog');
        CRUD::setEntityNameStrings('blog', 'blogs');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('thumbnail')->type('thumb')->label('');
        CRUD::column('title');
        CRUD::column('slug');
        CRUD::column('created_at');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blogs,slug,'.(CRUD::getCurrentEntryId() ?: 'NULL'),
            'content' => 'required|string',
            'thumbnail' => \Backpack\CRUD\app\Library\Validation\Rules\ValidUpload::field('nullable')->file('image|max:4096'),
        ]);

        CRUD::field('title')->type('text');
        CRUD::field('slug')->type('text')->hint('Will be automatically generated if left empty');
        CRUD::field('content')->type('summernote');
        CRUD::field('thumbnail')->type('upload')->withFiles(['disk' => 'public', 'path' => 'blog']);
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
