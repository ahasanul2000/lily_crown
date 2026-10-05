<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use CrudTrait;

    protected $fillable = ['title', 'slug', 'content', 'thumbnail'];

    protected static function booted(): void
    {
        static::saving(function (Blog $blog) {
            if (blank($blog->slug)) {
                $blog->slug = \Illuminate\Support\Str::slug($blog->title);
            }
        });
    }
}
