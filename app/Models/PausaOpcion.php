<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class PausaOpcion extends Model
{
    use CrudTrait;

    protected $table = 'pausa_opciones';

    protected $fillable = [
        'pregunta_id',
        'texto',
        'valor',
        'orden',
    ];

    public function pregunta()
    {
        return $this->belongsTo(PausaPregunta::class, 'pregunta_id');
    }
}
