<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Reincorporacion extends Model
{
    use CrudTrait;

    protected $table = 'reincorporaciones';

    protected $fillable = [
        'empleado_id',
        'estado',
        'origen',
        'recomendacion_medica',
        'fecha_ingreso',
        'acta_payload',
        'acta_pdf_path',
        'evidencia_pdf_path',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'acta_payload' => 'array',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function actasIngreso()
    {
        return $this->hasMany(ActaIngreso::class, 'reincorporacion_id');
    }

    public function actasSeguimiento()
    {
        return $this->hasMany(ActaSeguimiento::class, 'reincorporacion_id');
    }

    public function setEvidenciaPdfPathAttribute($value): void
    {
        if ($value instanceof \Illuminate\Http\UploadedFile) {
            $path = $value->store('reincorporaciones/evidencias', 'public');
            $this->attributes['evidencia_pdf_path'] = $path;
            return;
        }

        if (is_string($value)) {
            // Ignore temp upload paths (they won't be publicly accessible).
            if (str_contains($value, '/var/folders') || str_contains($value, '/private/var')) {
                return;
            }
            $this->attributes['evidencia_pdf_path'] = $value;
            return;
        }

        $this->attributes['evidencia_pdf_path'] = $value;
    }

    public function getEvidenciaPdfPathAttribute($value)
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        $path = str_replace('\\', '/', $value);
        $path = preg_replace('#^/storage/#', '', $path);
        $path = str_replace('evidenciasreincorporaciones', 'evidencias/reincorporaciones', $path);
        $path = preg_replace('#(reincorporaciones/evidencias)+#', 'reincorporaciones/evidencias', $path);
        $path = preg_replace('#(reincorporaciones/evidencias)(/reincorporaciones/evidencias)+#', '$1', $path);
        $path = preg_replace('#/+#', '/', $path);

        return ltrim($path, '/');
    }
}
