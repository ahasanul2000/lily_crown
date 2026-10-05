<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('order', 'OrderCrudController');
    Route::crud('product', 'ProductCrudController');
    Route::crud('category', 'CategoryCrudController');
    Route::crud('coupon', 'CouponCrudController');
    Route::crud('customer', 'CustomerCrudController');
    Route::crud('hero-slide', 'HeroSlideCrudController');
    Route::crud('lookbook', 'LookbookCrudController');
    Route::crud('testimonial', 'TestimonialCrudController');
    Route::crud('feature', 'FeatureCrudController');
    Route::crud('instagram-post', 'InstagramPostCrudController');
    Route::crud('blog', 'BlogCrudController');
    Route::crud('site-setting', 'SiteSettingCrudController');
    Route::crud('subscriber', 'SubscriberCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
