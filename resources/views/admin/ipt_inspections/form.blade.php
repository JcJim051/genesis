@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card p-4">
            <h4 class="mb-3">
                {{ $inspection?->exists ? 'Editar' : 'Nueva' }} Inspección IPT
                <small class="text-muted">({{ $tipo === 'followup' ? 'Seguimiento' : 'Inicial' }})</small>
            </h4>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Revisa los campos:</strong>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $baseAction = $tipo === 'followup'
                    ? backpack_url('ipt/' . ($initialInspection?->id ?? $inspection?->id) . '/create-followup')
                    : backpack_url('programa-caso/' . $programaCaso->id . '/ipt/create-initial');
                $action = $inspection?->exists ? backpack_url('ipt/' . $inspection->id . '/edit') : $baseAction;
            @endphp

            <form method="POST" action="{{ $action }}">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Persona</label>
                        <input class="form-control" value="{{ $programaCaso->empleado?->nombre }} · {{ $programaCaso->empleado?->cedula }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Empresa</label>
                        <input class="form-control" value="{{ $programaCaso->empleado?->cliente?->nombre }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Planta</label>
                        <input class="form-control" value="{{ $programaCaso->empleado?->sucursal?->nombre }}" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha inspección</label>
                        <input type="date" class="form-control" name="fecha_inspeccion" value="{{ old('fecha_inspeccion', optional($inspection?->fecha_inspeccion)->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Formulario IPT</label>
                        @if($inspection?->exists || $tipo === 'followup')
                            <input class="form-control" value="{{ $template->nombre_publico }}{{ $template->segmento ? (' · ' . $template->segmento) : '' }}" readonly>
                            <input type="hidden" name="template_id" value="{{ $template->id }}">
                        @else
                            <select class="form-control" name="template_id" required>
                                @foreach(($templates ?? collect())->sortByDesc('id') as $tpl)
                                    <option value="{{ $tpl->id }}" @selected((int) old('template_id', $template->id) === (int) $tpl->id)>
                                        {{ $tpl->nombre_publico }}{{ $tpl->segmento ? (' · ' . $tpl->segmento) : '' }}{{ $tpl->codigo ? (' [' . $tpl->codigo . ']') : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>

                @if($tipo === 'followup' && $initialInspection)
                    <div class="alert alert-info py-2">
                        Seguimiento de inspección inicial #{{ $initialInspection->id }} ({{ optional($initialInspection->fecha_inspeccion)->format('Y-m-d') }})
                    </div>
                @endif

                <div class="card border p-3 mb-3">
                    <h5 class="mb-3">{{ $template->nombre_publico }}</h5>

                    @foreach($template->sections->sortBy('orden') as $section)
                        <div class="mb-3">
                            <h6>{{ $section->titulo }}</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width:70%">Pregunta</th>
                                            <th>SI</th>
                                            <th>NO</th>
                                            <th>N/A</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($section->questions->sortBy('orden') as $question)
                                            @php $selected = old('answers.' . $question->id, $answers[$question->id] ?? null); @endphp
                                            <tr>
                                                <td>{{ $question->texto }}</td>
                                                <td><input type="radio" name="answers[{{ $question->id }}]" value="si" @checked($selected==='si')></td>
                                                <td><input type="radio" name="answers[{{ $question->id }}]" value="no" @checked($selected==='no')></td>
                                                <td><input type="radio" name="answers[{{ $question->id }}]" value="na" @checked($selected==='na')></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="card border p-3 mb-3">
                    <h6>Requerimientos estación de trabajo</h6>
                    <div class="row">
                        @foreach($template->requirements->where('activo', true)->sortBy('orden') as $requirement)
                            <div class="col-md-4">
                                <label class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" name="requirements[{{ $requirement->id }}]" value="1" @checked(old('requirements.' . $requirement->id, $requirements[$requirement->id] ?? false))>
                                    <span class="form-check-label">{{ $requirement->nombre }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Hallazgos / observaciones</label>
                        <textarea class="form-control" name="hallazgos" rows="3">{{ old('hallazgos', $inspection->hallazgos ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Recomendaciones</label>
                        <textarea class="form-control" name="recomendaciones" rows="3">{{ old('recomendaciones', $inspection->recomendaciones ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Acción</label>
                        <textarea class="form-control" name="accion" rows="2">{{ old('accion', $inspection->accion ?? '') }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Responsable</label>
                        <input class="form-control" name="responsable" value="{{ old('responsable', $inspection->responsable ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select class="form-control" name="estado">
                            @foreach(['abierto' => 'Abierto', 'cerrado' => 'Cerrado'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('estado', $inspection->estado ?? 'abierto') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($tipo === 'followup')
                        <div class="col-md-4">
                            <label class="form-label">¿Seguimiento exitoso?</label>
                            <select class="form-control" name="seguimiento_exitoso">
                                <option value="">Sin definir</option>
                                <option value="1" @selected((string) old('seguimiento_exitoso', $inspection->seguimiento_exitoso ?? null) === '1')>Sí</option>
                                <option value="0" @selected((string) old('seguimiento_exitoso', $inspection->seguimiento_exitoso ?? null) === '0')>No</option>
                            </select>
                        </div>
                    @endif
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar inspección</button>
                    <a class="btn btn-link" href="{{ backpack_url('ipt-inspection') }}">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
