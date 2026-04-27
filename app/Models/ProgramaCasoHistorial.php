<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class ProgramaCasoHistorial extends Model
{
    use CrudTrait;

    protected $table = 'programa_caso_historials';

    protected $fillable = [
        'programa_caso_id',
        'estado_anterior',
        'estado_nuevo',
        'observacion',
        'user_id',
    ];

    public function programaCaso()
    {
        return $this->belongsTo(ProgramaCaso::class, 'programa_caso_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
