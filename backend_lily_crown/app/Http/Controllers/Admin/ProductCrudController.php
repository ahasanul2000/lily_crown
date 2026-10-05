<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUploadMultiple;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProductCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Product::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/product');
        CRUD::setEntityNameStrings('product', 'products');
        CRUD::orderBy('id', 'desc');
    }

    protected function setupListOperation()
    {
        CRUD::column('image')->type('thumb')->label('');
        CRUD::column('name');
        CRUD::column('sku')->label('SKU');
        CRUD::column('category_id')->type('select')->entity('category')->attribute('name')->model(\App\Models\Category::class)->label('Category');
        CRUD::column('price')->type('number')->prefix('৳ ')->decimals(0);
        CRUD::column('sale_price')->type('number')->prefix('৳ ')->decimals(0)->label('Sale price');
        CRUD::column('total_stock')->type('closure')->label('Stock')->function(function (Product $p) {
            $stock = $p->total_stock;
            $class = $stock === 0 ? 'bg-danger' : ($stock <= 5 ? 'bg-warning' : 'bg-success');
            $sizes = $p->hasVariants() ? collect($p->variants)->map(fn ($v) => "{$v['size']}:{$v['stock']}")->implode(' ') : '';

            return "<span class=\"badge {$class} text-white\">{$stock}</span> <small class=\"text-muted\">{$sizes}</small>";
        })->escaped(false);
        CRUD::column('is_active')->type('boolean')->label('Active');
        CRUD::column('is_featured')->type('boolean')->label('Featured');
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
        CRUD::column('slug');
        CRUD::column('description')->type('textarea');
        CRUD::column('badge');
        CRUD::column('colors')->type('closure')->function(fn (Product $p) => collect($p->colors)->pluck('name')->implode(', '));
        CRUD::column('is_trending')->type('boolean')->label('Trending');
        CRUD::column('created_at');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,'.(CRUD::getCurrentEntryId() ?: 'NULL'),
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'stock' => 'nullable|integer|min:0',
            'image' => ValidUpload::field('required')->file('image|max:4096'),
            'gallery' => ValidUploadMultiple::field('nullable')->file('image|max:4096'),
            'variants.*.size' => 'nullable|string|max:50',
            'variants.*.stock' => 'nullable|integer|min:0',
        ], [
            'sale_price.lt' => 'Sale price must be lower than the regular price.',
        ]);

        // Basic
        CRUD::field('name')->type('text')->tab('Basic');
        CRUD::field('slug')->type('text')->hint('Leave empty to generate from the name.')->tab('Basic');
        CRUD::field('category_id')->type('select')->entity('category')->attribute('name')->model(\App\Models\Category::class)->label('Category')->tab('Basic');
        CRUD::field('sku')->type('text')->label('SKU / Product code')->tab('Basic');
        CRUD::field('description')->type('textarea')->attributes(['rows' => 5])->tab('Basic');
        CRUD::field('details')->type('rows')->columns(['info' => 'Detail (fabric, care, fit...)'])
            ->add_label('Add detail')->tab('Basic');

        // Price & stock
        CRUD::field('price')->type('number')->prefix('৳')->attributes(['step' => '0.01'])->label('Regular price')
            ->wrapper(['class' => 'form-group col-md-6'])->tab('Price & Stock');
        CRUD::field('sale_price')->type('number')->prefix('৳')->attributes(['step' => '0.01'])
            ->hint('Optional discounted price. Leave empty for no discount.')
            ->wrapper(['class' => 'form-group col-md-6'])->tab('Price & Stock');
        CRUD::field('variants')->type('rows')->label('Sizes & stock')
            ->columns(['size' => 'Size (e.g. S, M, 38, Free)', 'stock' => ['label' => 'Stock', 'type' => 'number']])
            ->add_label('Add size')
            ->hint('Add sizes if this product comes in sizes — stock is tracked per size. Leave empty for one-size products.')
            ->tab('Price & Stock');
        CRUD::field('stock')->type('number')->label('Stock (one-size products only)')->default(0)
            ->hint('Ignored when sizes are added above.')->tab('Price & Stock');
        CRUD::field('colors')->type('rows')->columns(['name' => 'Colour name'])->add_label('Add colour')
            ->hint('Optional — customers pick a colour when ordering.')->tab('Price & Stock');

        // Images
        CRUD::field('image')->type('upload')->label('Main image')
            ->withFiles(['disk' => 'public', 'path' => 'products'])->tab('Images');
        CRUD::field('gallery')->type('upload_multiple')->label('Gallery images')
            ->withFiles(['disk' => 'public', 'path' => 'products'])
            ->hint('Extra photos shown on the product page. You can select several files at once.')->tab('Images');

        // Display
        CRUD::field('badge')->type('text')->hint('Small label on the product card, e.g. New, Sale, Bestseller.')->tab('Display');
        CRUD::field('is_active')->type('switch')->label('Visible on website')->default(true)->tab('Display');
        CRUD::field('is_featured')->type('switch')->label('Featured on homepage')->tab('Display');
        CRUD::field('is_trending')->type('switch')->label('Show in Trending')->tab('Display');
        CRUD::field('sort_order')->type('number')->default(0)->hint('Lower numbers appear first.')->tab('Display');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
