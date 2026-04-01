<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ClienteCrudController;
use App\Http\Controllers\Admin\Cie10CrudController;
use App\Http\Controllers\Admin\Cie10LookupController;
use App\Http\Controllers\Admin\DiagnosticoProgramaMapCrudController;
use App\Http\Controllers\Admin\EncuestaAlertaCrudController;
use App\Http\Controllers\Admin\EncuestaCrudController;
use App\Http\Controllers\Admin\EncuestaEnvioCrudController;
use App\Http\Controllers\Admin\EncuestaOpcionCrudController;
use App\Http\Controllers\Admin\EncuestaPreguntaCrudController;
use App\Http\Controllers\Admin\EncuestaParticipacionCrudController;
use App\Http\Controllers\Admin\ExamenCrudController;
use App\Http\Controllers\Admin\EmpleadoAreaCrudController;
use App\Http\Controllers\Admin\EmpleadoCargoCrudController;
use App\Http\Controllers\Admin\EmpleadoCrudController;
use App\Http\Controllers\Admin\IncapacidadCrudController;
use App\Http\Controllers\Admin\PermissionCrudController;
use App\Http\Controllers\Admin\ProgramaCasoCrudController;
use App\Http\Controllers\Admin\ProgramaCrudController;
use App\Http\Controllers\Admin\ReincorporacionCrudController;
use App\Http\Controllers\Admin\ActaIngresoCrudController;
use App\Http\Controllers\Admin\ActaSeguimientoCrudController;
use App\Http\Controllers\Admin\PausaCrudController;
use App\Http\Controllers\Admin\PausaPreguntaCrudController;
use App\Http\Controllers\Admin\PausaOpcionCrudController;
use App\Http\Controllers\Admin\PausaEnvioCrudController;
use App\Http\Controllers\Admin\PausaParticipacionCrudController;
use App\Http\Controllers\Admin\RoleCrudController;
use App\Http\Controllers\Admin\SucursalCrudController;
use App\Http\Controllers\Admin\UserCrudController;

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
], function () {
    Route::crud('user', UserCrudController::class);
    Route::crud('role', RoleCrudController::class);
    Route::crud('permission', PermissionCrudController::class);
    Route::crud('cliente', ClienteCrudController::class);
    Route::crud('sucursal', SucursalCrudController::class);
    Route::crud('empleado', EmpleadoCrudController::class);
    Route::get('empleado/lookup', [EmpleadoCrudController::class, 'lookup']);
    Route::get('empleado/telegram-pendientes', [EmpleadoCrudController::class, 'telegramPendientes']);
    Route::post('empleado/{id}/telegram-unlink', [EmpleadoCrudController::class, 'desvincularTelegram'])->whereNumber('id');
    Route::get('empleado/import', [EmpleadoCrudController::class, 'importForm']);
    Route::post('empleado/import', [EmpleadoCrudController::class, 'import']);
    Route::get('empleado/template', [EmpleadoCrudController::class, 'template']);
    Route::crud('empleado-cargo', EmpleadoCargoCrudController::class);
    Route::get('empleado-cargo/import', [EmpleadoCargoCrudController::class, 'importForm']);
    Route::post('empleado-cargo/import', [EmpleadoCargoCrudController::class, 'import']);
    Route::get('empleado-cargo/template', [EmpleadoCargoCrudController::class, 'template']);
    Route::crud('empleado-area', EmpleadoAreaCrudController::class);
    Route::crud('programa', ProgramaCrudController::class);
    Route::crud('cie10', Cie10CrudController::class);
    Route::get('cie10/import', [Cie10CrudController::class, 'importForm']);
    Route::post('cie10/import', [Cie10CrudController::class, 'import']);
    Route::crud('programa-caso', ProgramaCasoCrudController::class);
    Route::get('programa-caso/{id}/accept', [ProgramaCasoCrudController::class, 'accept']);
    Route::get('programa-caso/{id}/probable', [ProgramaCasoCrudController::class, 'probable']);
    Route::get('programa-caso/{id}/reject', [ProgramaCasoCrudController::class, 'reject']);
    Route::get('programa-caso/{id}/retirar', [ProgramaCasoCrudController::class, 'retirar']);
    Route::crud('diagnostico-programa', DiagnosticoProgramaMapCrudController::class);
    Route::get('diagnostico-programa/defaults', [DiagnosticoProgramaMapCrudController::class, 'defaults']);
    Route::crud('incapacidad', IncapacidadCrudController::class);
    Route::get('incapacidad/import', [IncapacidadCrudController::class, 'importForm']);
    Route::post('incapacidad/import', [IncapacidadCrudController::class, 'import']);
    Route::get('incapacidad/template', [IncapacidadCrudController::class, 'template']);
    Route::get('incapacidad/reprocess', [IncapacidadCrudController::class, 'reprocess']);
    Route::crud('examen', ExamenCrudController::class);
    Route::crud('encuesta', EncuestaCrudController::class);
    Route::get('encuesta/builder', [EncuestaCrudController::class, 'builder']);
    Route::post('encuesta/builder', [EncuestaCrudController::class, 'builderSave']);
    Route::get('encuesta/{id}/builder', [EncuestaCrudController::class, 'builder'])->whereNumber('id');
    Route::post('encuesta/{id}/builder', [EncuestaCrudController::class, 'builderSave'])->whereNumber('id');
    Route::crud('encuesta-pregunta', EncuestaPreguntaCrudController::class);
    Route::crud('encuesta-opcion', EncuestaOpcionCrudController::class);
    Route::crud('encuesta-envio', EncuestaEnvioCrudController::class);
    Route::get('encuesta-envio/{id}/procesar', [EncuestaEnvioCrudController::class, 'procesar'])->whereNumber('id');
    Route::post('encuesta-envio/{id}/procesar', [EncuestaEnvioCrudController::class, 'procesar'])->whereNumber('id');
    Route::crud('encuesta-participacion', EncuestaParticipacionCrudController::class);
    Route::get('encuesta-participacion/export', [EncuestaParticipacionCrudController::class, 'export']);
    Route::crud('encuesta-alerta', EncuestaAlertaCrudController::class);
    Route::crud('pausa', PausaCrudController::class);
    Route::get('pausa/builder', [PausaCrudController::class, 'builder']);
    Route::post('pausa/builder', [PausaCrudController::class, 'builderSave']);
    Route::get('pausa/{id}/builder', [PausaCrudController::class, 'builder'])->whereNumber('id');
    Route::post('pausa/{id}/builder', [PausaCrudController::class, 'builderSave'])->whereNumber('id');
    Route::crud('pausa-pregunta', PausaPreguntaCrudController::class);
    Route::crud('pausa-opcion', PausaOpcionCrudController::class);
    Route::crud('pausa-envio', PausaEnvioCrudController::class);
    Route::get('pausa-envio/{id}/procesar', [PausaEnvioCrudController::class, 'procesar'])->whereNumber('id');
    Route::post('pausa-envio/{id}/procesar', [PausaEnvioCrudController::class, 'procesar'])->whereNumber('id');
    Route::crud('pausa-participacion', PausaParticipacionCrudController::class);
    Route::get('pausa-participacion/export', [PausaParticipacionCrudController::class, 'export']);
    Route::crud('reincorporacion', ReincorporacionCrudController::class);
    Route::get('reincorporacion/{id}/acta', [ReincorporacionCrudController::class, 'acta'])->whereNumber('id');
    Route::get('reincorporacion/{id}/acta-pdf', [ReincorporacionCrudController::class, 'pdf']);
    Route::get('reincorporacion/{id}/evidencia', [ReincorporacionCrudController::class, 'evidencia'])->whereNumber('id');
    Route::crud('acta-ingreso', ActaIngresoCrudController::class);
    Route::crud('acta-seguimiento', ActaSeguimientoCrudController::class);
    Route::get('acta-ingreso/{id}/pdf', [ActaIngresoCrudController::class, 'pdf'])->whereNumber('id');
    Route::get('acta-seguimiento/{id}/pdf', [ActaSeguimientoCrudController::class, 'pdf'])->whereNumber('id');
    Route::get('cie10/{id}/lookup', [Cie10LookupController::class, 'show'])->whereNumber('id');
});

/**
 * DO NOT ADD ANYTHING HERE.
 */
