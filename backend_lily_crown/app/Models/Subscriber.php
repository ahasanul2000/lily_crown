<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use CrudTrait;

    protected $fillable = ['email'];
}
