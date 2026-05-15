<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ClienteCrudController;
use App\Http\Controllers\Admin\Cie10CrudController;
use App\Http\Controllers\Admin\Cie10LookupController;
use App\Http\Controllers\Admin\DiagnosticoProgramaMapCrudController;
use App\Http\Controllers\Admin\ColombiaHolidayCrudController;
use App\Http\Controllers\Admin\EncuestaAlertaCrudController;
use App\Http\Controllers\Admin\EncuestaCrudController;
use App\Http\Controllers\Admin\EncuestaEnvioCrudController;
use App\Http\Controllers\Admin\EncuestaOpcionCrudController;
use App\Http\Controllers\Admin\EncuestaPreguntaCrudController;
use App\Http\Controllers\Admin\EncuestaParticipacionCrudController;
use App\Http\Controllers\Admin\ExamenCrudController;
use App\Http\Controllers\Admin\GoogleDriveConfigController;
use App\Http\Controllers\Admin\EmpleadoAreaCrudController;
use App\Http\Controllers\Admin\EmpleadoCargoCrudController;
use App\Http\Controllers\Admin\EmpleadoCrudController;
use App\Http\Controllers\Admin\IncapacidadCrudController;
use App\Http\Controllers\Admin\IptInspectionCrudController;
use App\Http\Controllers\Admin\IptTemplateCrudController;
use App\Http\Controllers\Admin\OsteoEvaluationCrudController;
use App\Http\Controllers\Admin\OsteoTemplateCrudController;
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
use App\Http\Controllers\Admin\TenantScopeController;
use App\Http\Controllers\Admin\UserCrudController;

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
], function () {
    Route::crud('user', UserCrudController::class);
    Route::post('scope/select', [TenantScopeController::class, 'update']);
    Route::get('integraciones/google-drive', [GoogleDriveConfigController::class, 'edit'])->name('integraciones.google-drive.edit');
    Route::post('integraciones/google-drive', [GoogleDriveConfigController::class, 'update'])->name('integraciones.google-drive.update');
    Route::get('integraciones/google-drive/oauth/redirect', [GoogleDriveConfigController::class, 'oauthRedirect'])->name('integraciones.google-drive.oauth-redirect');
    Route::get('integraciones/google-drive/oauth/callback', [GoogleDriveConfigController::class, 'oauthCallback'])->name('integraciones.google-drive.oauth-callback');
    Route::post('integraciones/google-drive/oauth/disconnect', [GoogleDriveConfigController::class, 'oauthDisconnect'])->name('integraciones.google-drive.oauth-disconnect');
    Route::crud('role', RoleCrudController::class);
    Route::crud('permission', PermissionCrudController::class);
    Route::crud('cliente', ClienteCrudController::class);
    Route::crud('sucursal', SucursalCrudController::class);
    Route::crud('empleado', EmpleadoCrudController::class);
    Route::get('empleado/lookup', [EmpleadoCrudController::class, 'lookup']);
    Route::get('empleado/plantas', [EmpleadoCrudController::class, 'plantas']);
    Route::get('empleado/telegram-pendientes', [EmpleadoCrudController::class, 'telegramPendientes']);
    Route::get('empleado/telegram-pendientes-view', [EmpleadoCrudController::class, 'telegramPendientesView']);
    Route::post('empleado/telegram-email-pendientes', [EmpleadoCrudController::class, 'enviarTelegramEmailsPendientes']);
    Route::post('empleado/{id}/telegram-email', [EmpleadoCrudController::class, 'enviarTelegramEmail'])->whereNumber('id');
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
    Route::post('programa-caso/{id}/accept', [ProgramaCasoCrudController::class, 'accept']);
    Route::post('programa-caso/{id}/probable', [ProgramaCasoCrudController::class, 'probable']);
    Route::post('programa-caso/{id}/reject', [ProgramaCasoCrudController::class, 'reject']);
    Route::post('programa-caso/{id}/retirar', [ProgramaCasoCrudController::class, 'retirar']);
    Route::crud('diagnostico-programa', DiagnosticoProgramaMapCrudController::class);
    Route::get('diagnostico-programa/defaults', [DiagnosticoProgramaMapCrudController::class, 'defaults']);
    Route::crud('ipt-template', IptTemplateCrudController::class);
    Route::get('ipt-template/seed-vdt', [IptTemplateCrudController::class, 'seedVdt']);
    Route::get('ipt-template/builder', [IptTemplateCrudController::class, 'builder']);
    Route::post('ipt-template/builder', [IptTemplateCrudController::class, 'builderSave']);
    Route::get('ipt-template/{id}/builder', [IptTemplateCrudController::class, 'builder'])->whereNumber('id');
    Route::post('ipt-template/{id}/builder', [IptTemplateCrudController::class, 'builderSave'])->whereNumber('id');
    Route::crud('ipt-inspection', IptInspectionCrudController::class);
    Route::get('ipt-inspection/create-manual', [IptInspectionCrudController::class, 'createManual'])
        ->name('ipt-inspection.create-manual');
    Route::post('ipt-inspection/create-manual', [IptInspectionCrudController::class, 'storeManual'])
        ->name('ipt-inspection.store-manual');
    Route::get('programa-caso/{id}/ipt/create-initial', [IptInspectionCrudController::class, 'createInitialForCase'])->whereNumber('id');
    Route::post('programa-caso/{id}/ipt/create-initial', [IptInspectionCrudController::class, 'storeInitialForCase'])->whereNumber('id');
    Route::get('ipt/{id}/create-followup', [IptInspectionCrudController::class, 'createFollowup'])->whereNumber('id');
    Route::post('ipt/{id}/create-followup', [IptInspectionCrudController::class, 'storeFollowup'])->whereNumber('id');
    Route::get('ipt/{id}/edit', [IptInspectionCrudController::class, 'editForm'])->whereNumber('id');
    Route::post('ipt/{id}/edit', [IptInspectionCrudController::class, 'updateForm'])->whereNumber('id');
    Route::get('ipt-inspection/{id}/pdf', [IptInspectionCrudController::class, 'pdf'])->whereNumber('id');
    Route::get('ipt-inspection/matriz/download', [IptInspectionCrudController::class, 'downloadMatrix'])
        ->name('ipt-inspection.matrix-download');
    Route::post('ipt-inspection/matriz/sync-drive', [IptInspectionCrudController::class, 'syncMatrixToDrive'])
        ->name('ipt-inspection.matrix-sync-drive');
    Route::get('ipt-inspection/matriz/drive', [IptInspectionCrudController::class, 'openDriveMatrices'])
        ->name('ipt-inspection.matrix-open-drive');
    Route::crud('colombia-holiday', ColombiaHolidayCrudController::class);
    Route::crud('osteo-template', OsteoTemplateCrudController::class);
    Route::get('osteo-template/seed-base', [OsteoTemplateCrudController::class, 'seedBase']);
    Route::get('osteo-template/builder', [OsteoTemplateCrudController::class, 'builder']);
    Route::post('osteo-template/builder', [OsteoTemplateCrudController::class, 'builderSave']);
    Route::get('osteo-template/{id}/builder', [OsteoTemplateCrudController::class, 'builder'])->whereNumber('id');
    Route::post('osteo-template/{id}/builder', [OsteoTemplateCrudController::class, 'builderSave'])->whereNumber('id');
    Route::crud('osteo-evaluation', OsteoEvaluationCrudController::class);
    Route::get('osteo-evaluation/create-manual', [OsteoEvaluationCrudController::class, 'createManual'])->name('osteo-evaluation.create-manual');
    Route::post('osteo-evaluation/create-manual', [OsteoEvaluationCrudController::class, 'storeManual'])->name('osteo-evaluation.store-manual');
    Route::get('programa-caso/{id}/osteo-evaluation/create', [OsteoEvaluationCrudController::class, 'createForCase'])->whereNumber('id');
    Route::post('programa-caso/{id}/osteo-evaluation/create', [OsteoEvaluationCrudController::class, 'storeForCase'])->whereNumber('id');
    Route::get('osteo-evaluation/{id}/edit', [OsteoEvaluationCrudController::class, 'editForm'])->whereNumber('id');
    Route::post('osteo-evaluation/{id}/edit', [OsteoEvaluationCrudController::class, 'updateForm'])->whereNumber('id');
    Route::get('osteo-evaluation/{id}/pdf', [OsteoEvaluationCrudController::class, 'pdf'])->whereNumber('id');
    Route::post('osteo-evaluation/matriz/sync-drive', [OsteoEvaluationCrudController::class, 'syncMatrixToDrive'])->name('osteo-evaluation.matrix-sync-drive');
    Route::get('osteo-evaluation/matriz/drive', [OsteoEvaluationCrudController::class, 'openDriveMatrices'])->name('osteo-evaluation.matrix-open-drive');
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
