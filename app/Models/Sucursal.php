<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use CrudTrait;

    protected $fillable = [
        'cliente_id',
        'nombre',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'sucursal_user');
    }

    public function getClienteNombre(): string
    {
        return (string) ($this->cliente?->nombre ?? '');
    }
}
