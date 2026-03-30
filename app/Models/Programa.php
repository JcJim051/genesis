<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    use CrudTrait;

    protected $fillable = [
        'nombre',
        'slug',
        'tipo',
    ];

    public function casos()
    {
        return $this->hasMany(ProgramaCaso::class, 'programa_id');
    }
}
