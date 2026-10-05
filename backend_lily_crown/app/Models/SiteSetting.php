<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use CrudTrait;

    protected $fillable = ['key', 'value', 'type'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $value = static::where('key', $key)->value('value');

        return $value === null || $value === '' ? $default : $value;
    }
}
