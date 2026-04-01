<?php

namespace App\Http\Controllers\Admin;

use App\Models\Empleado;
use App\Models\Reincorporacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Storage;

class ReincorporacionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Reincorporacion::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/reincorporacion');
        CRUD::setEntityNameStrings('reincorporación', 'reincorporaciones');
        $this->applyAccessRules();
    }

    protected function setupListOperation(): void
    {
        $this->applyListScope();

        CRUD::addButtonFromView('line', 'reincorporacion_acta_pdf', 'reincorporacion_acta_pdf', 'beginning');

        CRUD::addColumn([
            'name' => 'empleado',
            'type' => 'closure',
            'label' => 'Persona',
            'function' => fn ($entry) => optional($entry->empleado)->nombre,
        ]);
        CRUD::column('estado');
        CRUD::column('origen');
        CRUD::column('fecha_ingreso');
    }

    protected function setupShowOperation(): void
    {
        $this->applyListScope();
        $this->crud->setShowView('admin.reincorporaciones.show');

        CRUD::addColumn([
            'name' => 'empleado',
            'type' => 'closure',
            'label' => 'Persona',
            'function' => fn ($entry) => optional($entry->empleado)->nombre,
        ]);
        CRUD::column('estado');
        CRUD::column('origen');
        CRUD::column('recomendacion_medica')->type('textarea')->label('Recomendación médico-laboral');
        CRUD::column('fecha_ingreso')->label('Fecha ingreso');
        CRUD::column('acta_pdf_path')->label('Acta PDF');
        CRUD::column('evidencia_pdf_path')->label('Evidencia PDF');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::addField([
            'name' => 'empleado_id',
            'type' => 'hidden',
        ]);

        CRUD::addField([
            'name' => 'autofill_helper',
            'type' => 'empleado_autofill',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-6 col-md-12'],
        ]);

        CRUD::field('evidencia_pdf_path')
            ->type('upload_local')
            ->label('Evidencia (PDF firmado)')
            ->upload(true)
            ->disk('public')
            ->prefix('reincorporaciones/evidencias')
            ->hint('Cargue aquí el PDF firmado para confirmar la reincorporación.')
            ->wrapper(['class' => 'form-group col-lg-3 col-md-6']);

        $this->addActaFields();
    }

    protected function setupUpdateOperation(): void
    {
        CRUD::addField([
            'name' => 'empleado_id',
            'type' => 'hidden',
        ]);

        CRUD::field('evidencia_pdf_path')
            ->type('upload_local')
            ->label('Evidencia (PDF firmado)')
            ->upload(true)
            ->disk('public')
            ->prefix('reincorporaciones/evidencias')
            ->hint('Cargue aquí el PDF firmado para confirmar la reincorporación.')
            ->wrapper(['class' => 'form-group col-lg-3 col-md-6']);

        $this->addActaFields();
    }

    public function store()
    {
        $this->crud->validateRequest();
        $this->syncDerivedFields();
        $response = $this->traitStore();
        $this->generateActaPdf($this->crud->entry);
        return $response;
    }

    public function update()
    {
        $this->crud->validateRequest();
        $this->syncDerivedFields();
        $response = $this->traitUpdate();
        $this->generateActaPdf($this->crud->entry);
        return $response;
    }

    private function applyAccessRules(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general', 'Coordinador de planta'])) {
            $this->crud->denyAccess(['delete']);
            return;
        }

        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
    }

    private function applyListScope(): void
    {
        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general'])) {
            $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
            $empleadoIds = Empleado::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id');
            $this->crud->addClause('whereIn', 'empleado_id', $empleadoIds);
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta'])) {
            $plantaIds = backpack_user()->plantas()->pluck('sucursals.id')->all();
            $empleadoIds = Empleado::whereIn('sucursal_id', $plantaIds ?: [0])->pluck('id');
            $this->crud->addClause('whereIn', 'empleado_id', $empleadoIds);
            return;
        }

        $this->crud->addClause('whereRaw', '1 = 0');
    }

    private function addActaFields(): void
    {
        $entry = $this->crud->getCurrentEntry();
        if (! $entry || is_bool($entry)) {
            $entry = null;
        }
        $empleado = $entry?->empleado;
        $payload = is_array($entry?->acta_payload) ? $entry->acta_payload : [];

        $val = function (string $key, $fallback = '') use ($payload) {
            $value = $payload[$key] ?? null;
            if ($value === null || $value === '') {
                return $fallback;
            }
            return $value;
        };
        $fmtDate = function ($value) {
            if ($value instanceof \Carbon\CarbonInterface) {
                return $value->format('Y-m-d');
            }
            if (is_string($value)) {
                return $value;
            }
            return '';
        };

        CRUD::addField([
            'name' => 'fecha_acta',
            'label' => 'Fecha',
            'type' => 'date',
            'default' => now()->format('Y-m-d'),
            'value' => $val('fecha_acta', $fmtDate($entry?->fecha_ingreso) ?: now()->format('Y-m-d')),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'nombre_completo',
            'label' => 'Nombre completo',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('nombre_completo', $empleado?->nombre ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'identificacion',
            'label' => 'Identificación',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('identificacion', $empleado?->cedula ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'fecha_nacimiento',
            'label' => 'Fecha nacimiento',
            'type' => 'date',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('fecha_nacimiento', $fmtDate($empleado?->fecha_nacimiento)),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'edad',
            'label' => 'Edad',
            'type' => 'number',
            'attributes' => ['min' => 0, 'readonly' => 'readonly'],
            'value' => $val('edad', $empleado?->edad ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'genero',
            'label' => 'Género',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('genero', $empleado?->genero ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'lateralidad',
            'label' => 'Lateralidad',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('lateralidad', $empleado?->lateralidad ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'eps',
            'label' => 'EPS',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('eps', $empleado?->eps ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'arl',
            'label' => 'ARL',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('arl', $empleado?->arl ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'fondo_pensiones',
            'label' => 'Fondo de pensiones',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('fondo_pensiones', $empleado?->fondo_pensiones ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'telefono_contacto',
            'label' => 'Teléfono contacto',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('telefono_contacto', $empleado?->telefono ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'correo_electronico',
            'label' => 'Correo electrónico',
            'type' => 'email',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('correo_electronico', $empleado?->correo_electronico ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'direccion_residencia',
            'label' => 'Dirección residencia',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('direccion_residencia', $empleado?->direccion ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'fecha_ingreso_empresa',
            'label' => 'Fecha ingreso a la empresa',
            'type' => 'date',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('fecha_ingreso_empresa', $fmtDate($empleado?->fecha_ingreso)),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'cargo_actual',
            'label' => 'Cargo actual',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('cargo_actual', $empleado?->getCargoActual() ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'antiguedad_cargo',
            'label' => 'Antigüedad en el cargo',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('antiguedad_cargo', $empleado?->getAntiguedadCargoActual() ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'origen_evento',
            'label' => 'Origen del evento',
            'type' => 'select_from_array',
            'options' => [
                'Común' => 'Común',
                'Accidente de Trabajo' => 'Accidente de Trabajo',
                'Enfermedad Laboral' => 'Enfermedad Laboral',
            ],
            'allows_null' => true,
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'vigencia_fecha_inicial',
            'label' => 'Vigencia recomendaciones (fecha inicial)',
            'type' => 'date',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'vigencia_fecha_final',
            'label' => 'Vigencia recomendaciones (fecha final)',
            'type' => 'date',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'recomendaciones_medicas',
            'label' => 'Recomendaciones médicas',
            'type' => 'textarea',
            'attributes' => ['rows' => 4],
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-6 col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'actividades_asignadas',
            'label' => 'Actividades laborales asignadas teniendo en cuenta recomendaciones',
            'type' => 'textarea',
            'attributes' => ['rows' => 4],
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-6 col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'decision_adoptada',
            'label' => 'Decisión adoptada',
            'type' => 'select_from_array',
            'options' => [
                'Reincorporación sin modificaciones' => 'Reincorporación sin modificaciones, cargo habitual sin ningún cambio',
                'Reincorporación con modificaciones' => 'Reincorporación con modificaciones dentro su cargo habitual',
                'Reubicación temporal' => 'Reubicación laboral temporal',
                'Reubicación permanente' => 'Reubicación laboral permanente',
                'Reconversión mano de obra' => 'Reconversión mano de obra',
            ],
            'allows_null' => true,
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-6 col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'ajuste_puesto',
            'label' => '¿Se ajustaron las condiciones del puesto de trabajo?',
            'type' => 'select_from_array',
            'options' => ['SI' => 'SI', 'NO' => 'NO'],
            'allows_null' => true,
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'ajuste_descripcion',
            'label' => 'Si marcó SI, describa los cambios',
            'type' => 'textarea',
            'attributes' => ['rows' => 3],
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-6 col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'cambio_cargo',
            'label' => '¿Se cambió el cargo?',
            'type' => 'select_from_array',
            'options' => ['SI' => 'SI', 'NO' => 'NO'],
            'allows_null' => true,
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'nuevo_cargo',
            'label' => 'Nuevo cargo asignado (si aplica)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'area_reintegra',
            'label' => 'Área donde se reintegra',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('area_reintegra', $empleado?->getAreaActual() ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'observaciones',
            'label' => 'Observaciones',
            'type' => 'textarea',
            'attributes' => ['rows' => 3],
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-6 col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'fecha_proximo_seguimiento',
            'label' => 'Fecha del próximo seguimiento',
            'type' => 'date',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'asistente_jefe_nombre',
            'label' => 'Jefe inmediato (nombre)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);
        CRUD::addField([
            'name' => 'asistente_jefe_cedula',
            'label' => 'Jefe inmediato (cédula)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);
        CRUD::addField([
            'name' => 'asistente_jefe_cargo',
            'label' => 'Jefe inmediato (cargo)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'asistente_sst_nombre',
            'label' => 'Responsable SST (nombre)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);
        CRUD::addField([
            'name' => 'asistente_sst_cedula',
            'label' => 'Responsable SST (cédula)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);
        CRUD::addField([
            'name' => 'asistente_sst_cargo',
            'label' => 'Responsable SST (cargo)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'asistente_trabajador_nombre',
            'label' => 'Trabajador (nombre)',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('asistente_trabajador_nombre', $empleado?->nombre ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);
        CRUD::addField([
            'name' => 'asistente_trabajador_cedula',
            'label' => 'Trabajador (cédula)',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('asistente_trabajador_cedula', $empleado?->cedula ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);
        CRUD::addField([
            'name' => 'asistente_trabajador_cargo',
            'label' => 'Trabajador (cargo)',
            'type' => 'text',
            'attributes' => ['readonly' => 'readonly'],
            'value' => $val('asistente_trabajador_cargo', $empleado?->getCargoActual() ?? ''),
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'asistente_otro_nombre',
            'label' => 'Otro (nombre)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);
        CRUD::addField([
            'name' => 'asistente_otro_cedula',
            'label' => 'Otro (cédula)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);
        CRUD::addField([
            'name' => 'asistente_otro_cargo',
            'label' => 'Otro (cargo)',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'acta_payload',
            'wrapper' => ['class' => 'form-group col-lg-3 col-md-6'],
        ]);
    }

    private function syncDerivedFields(): void
    {
        $request = $this->crud->getRequest();
        $origen = $request->get('origen_evento');
        $recomendaciones = $request->get('recomendaciones_medicas');
        $fechaActa = $request->get('fecha_acta');

        if ($origen) {
            $request->request->set('origen', $origen);
        }

        if ($recomendaciones) {
            $request->request->set('recomendacion_medica', $recomendaciones);
        }

        if ($fechaActa) {
            $request->request->set('fecha_ingreso', $fechaActa);
        }

        $hasEvidence = $request->hasFile('evidencia_pdf_path') || $request->get('evidencia_pdf_path');
        $request->request->set('estado', $hasEvidence ? 'Confirmado' : 'Generado');
    }

    private function generateActaPdf(Reincorporacion $reincorporacion): void
    {
        $reincorporacion->loadMissing('empleado');
        $pdf = Pdf::loadView('actas.reincorporacion_pdf', [
            'reincorporacion' => $reincorporacion,
            'empleado' => $reincorporacion->empleado,
            'acta' => $reincorporacion->acta_payload ?? [],
        ]);

        $path = 'reincorporaciones/actas/acta_reincorporacion_' . $reincorporacion->id . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        $reincorporacion->acta_pdf_path = $path;
        $reincorporacion->save();
    }

    public function pdf($id)
    {
        $reincorporacion = Reincorporacion::with('empleado')->findOrFail($id);
        if (! $reincorporacion->acta_pdf_path || ! Storage::disk('public')->exists($reincorporacion->acta_pdf_path)) {
            $this->generateActaPdf($reincorporacion);
        }

        $filename = 'acta_reincorporacion_' . $reincorporacion->id . '.pdf';
        return Storage::disk('public')->download($reincorporacion->acta_pdf_path, $filename);
    }

    public function acta($id)
    {
        $reincorporacion = Reincorporacion::with('empleado')->findOrFail($id);
        return view('admin.reincorporaciones.acta', [
            'entry' => $reincorporacion,
            'crud' => $this->crud,
        ]);
    }

    public function evidencia($id)
    {
        $reincorporacion = Reincorporacion::findOrFail($id);
        if (! $reincorporacion->evidencia_pdf_path) {
            abort(404);
        }
        $path = $reincorporacion->evidencia_pdf_path;
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }
        $filename = 'evidencia_reincorporacion_' . $reincorporacion->id . '.pdf';
        return Storage::disk('public')->response($path, $filename, [
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
