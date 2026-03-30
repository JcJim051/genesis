<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use CrudTrait;

    protected $fillable = [
        'nombre',
        'codigo',
        'direccion',
        'ciudad',
        'telefono',
        'encargado',
    ];

    public function sucursales()
    {
        return $this->hasMany(Sucursal::class, 'cliente_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'cliente_user');
    }
}
