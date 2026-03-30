<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Cie10 extends Model
{
    use CrudTrait;

    protected $table = 'cie10s';

    protected $fillable = [
        'codigo',
        'diagnostico',
    ];
}
