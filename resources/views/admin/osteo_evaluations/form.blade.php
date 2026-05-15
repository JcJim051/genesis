@extends(backpack_view('blank'))

@section('content')
@php
    $empleado = $programaCaso->empleado;
    $calcAntiguedad = function ($fechaIngreso) {
        if (!$fechaIngreso) return null;
        try {
            $d = \Carbon\Carbon::parse($fechaIngreso);
            return $d->diffForHumans(now(), ['parts' => 2, 'short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
        } catch (\Throwable $e) {
            return null;
        }
    };
    $normalizeFieldLabel = function (?string $text): string {
        $text = \Illuminate\Support\Str::of((string) $text)->ascii()->lower()->toString();
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';
        return trim($text);
    };
    $genero = trim((string)($empleado?->genero ?? ''));
    if ($genero !== '') {
        $g = mb_strtolower($genero);
        if (in_array($g, ['m', 'masculino', 'hombre'], true)) $genero = 'Masculino';
        if (in_array($g, ['f', 'femenino', 'mujer'], true)) $genero = 'Femenino';
    }

    $autoFieldMap = [];
    $autoFieldMap[$normalizeFieldLabel('nombre del trabajador')] = trim((string)($empleado?->nombre ?? ''));
    $autoFieldMap[$normalizeFieldLabel('documento de identificación')] = trim((string)($empleado?->cedula ?? ''));
    $autoFieldMap[$normalizeFieldLabel('documento de identificacion')] = trim((string)($empleado?->cedula ?? ''));
    $autoFieldMap[$normalizeFieldLabel('cedula')] = trim((string)($empleado?->cedula ?? ''));
    $autoFieldMap[$normalizeFieldLabel('cc')] = trim((string)($empleado?->cedula ?? ''));
    $autoFieldMap[$normalizeFieldLabel('sexo')] = $genero;
    $autoFieldMap[$normalizeFieldLabel('genero')] = $genero;
    $autoFieldMap[$normalizeFieldLabel('antigüedad en la entidad')] = trim((string)($calcAntiguedad($empleado?->fecha_ingreso) ?? ''));
    $autoFieldMap[$normalizeFieldLabel('antiguedad en la entidad')] = trim((string)($calcAntiguedad($empleado?->fecha_ingreso) ?? ''));
    $autoFieldMap[$normalizeFieldLabel('área funcional')] = trim((string)($empleado?->getAreaActual() ?? ''));
    $autoFieldMap[$normalizeFieldLabel('area funcional')] = trim((string)($empleado?->getAreaActual() ?? ''));
    // Estos se capturan arriba en encabezado del formulario.
    $autoFieldMap[$normalizeFieldLabel('evaluador')] = trim((string) old('evaluador', $evaluation->evaluador ?? ''));
    $autoFieldMap[$normalizeFieldLabel('n licencia')] = trim((string) old('licencia', $evaluation->licencia ?? ''));
    $autoFieldMap[$normalizeFieldLabel('no licencia')] = trim((string) old('licencia', $evaluation->licencia ?? ''));
    $autoFieldMap[$normalizeFieldLabel('numero licencia')] = trim((string) old('licencia', $evaluation->licencia ?? ''));
    $autoFieldMap[$normalizeFieldLabel('cargo profesional')] = trim((string) old('cargo_profesional', $evaluation->cargo_profesional ?? ''));
    $autoFieldMap[$normalizeFieldLabel('cargo profesional')] = trim((string) old('cargo_profesional', $evaluation->cargo_profesional ?? ''));
    $forceHideFieldLabels = [
        $normalizeFieldLabel('documento de identificación'),
        $normalizeFieldLabel('documento de identificacion'),
        $normalizeFieldLabel('cedula'),
        $normalizeFieldLabel('cc'),
        $normalizeFieldLabel('sexo'),
        $normalizeFieldLabel('genero'),
        $normalizeFieldLabel('femenino'),
        $normalizeFieldLabel('masculino'),
        $normalizeFieldLabel('evaluador'),
        $normalizeFieldLabel('n licencia'),
        $normalizeFieldLabel('no licencia'),
        $normalizeFieldLabel('numero licencia'),
        $normalizeFieldLabel('cargo profesional'),
    ];
@endphp
<div class="row">
    <div class="col-12">
        <div class="card p-4">
            <h4 class="mb-3">{{ $evaluation?->exists ? 'Editar' : 'Nueva' }} Valoración Osteomuscular</h4>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @php
                $action = $evaluation?->exists
                    ? backpack_url('osteo-evaluation/' . $evaluation->id . '/edit')
                    : backpack_url('programa-caso/' . $programaCaso->id . '/osteo-evaluation/create');
            @endphp

            <form method="POST" action="{{ $action }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Persona</label>
                        <input class="form-control" readonly value="{{ $programaCaso->empleado?->nombre }} · {{ $programaCaso->empleado?->cedula }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Empresa</label>
                        <input class="form-control" readonly value="{{ $programaCaso->empleado?->cliente?->nombre }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Planta</label>
                        <input class="form-control" readonly value="{{ $programaCaso->empleado?->sucursal?->nombre }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha</label>
                        <input class="form-control" type="date" name="fecha_valoracion" value="{{ old('fecha_valoracion', optional($evaluation?->fecha_valoracion)->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Plantilla</label>
                        @if($evaluation?->exists)
                            <input class="form-control" readonly value="{{ $template->nombre_publico }}">
                            <input type="hidden" name="template_id" value="{{ $template->id }}">
                        @else
                            <select class="form-control" name="template_id" id="template_id_selector" required>
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}" @selected((int) old('template_id', $template->id) === (int) $tpl->id)>{{ $tpl->nombre_publico }}{{ $tpl->segmento ? (' · ' . $tpl->segmento) : '' }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select class="form-control" name="estado">
                            <option value="borrador" @selected(old('estado', $evaluation->estado ?? 'borrador') === 'borrador')>Borrador</option>
                            <option value="final" @selected(old('estado', $evaluation->estado ?? '') === 'final')>Final</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Evaluador</label>
                        <input class="form-control" name="evaluador" value="{{ old('evaluador', $evaluation->evaluador ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cargo profesional</label>
                        <input class="form-control" name="cargo_profesional" value="{{ old('cargo_profesional', $evaluation->cargo_profesional ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Licencia</label>
                        <input class="form-control" name="licencia" value="{{ old('licencia', $evaluation->licencia ?? '') }}">
                    </div>
                </div>

                @foreach($template->sections->sortBy('orden') as $section)
                    @php
                        $isDatosGeneralesSection = str_contains($normalizeFieldLabel((string) $section->titulo), 'datos generales');
                    @endphp
                    <div class="card border p-3 mb-3">
                        <h6 class="mb-3">{{ $section->titulo }}</h6>
                        <div class="row g-3">
                        @foreach($section->fields->sortBy('orden') as $field)
                            @php
                                $type = $field->tipo ?? 'text';
                                $fId = (string)$field->id;
                                $autoKey = $normalizeFieldLabel((string)$field->label);
                                $autoValue = $autoFieldMap[$autoKey] ?? null;
                                $mustHide = in_array($autoKey, $forceHideFieldLabels, true);
                            @endphp
                            @if($mustHide)
                                <input type="hidden" name="answers[{{ $fId }}]" value="{{ old("answers.$fId", $autoValue ?? '') }}">
                                @continue
                            @endif
                            @if($autoValue !== null && $autoValue !== '')
                                <input type="hidden" name="answers[{{ $fId }}]" value="{{ old("answers.$fId", $answers[$fId]['valor'] ?? $autoValue) }}">
                                @continue
                            @endif
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ $field->label }}</label>
                                @if(in_array($type, ['laterality_pair','plus_minus_pair'], true))
                                    <div class="row g-2">
                                        @foreach(['D' => 'Derecha', 'I' => 'Izquierda'] as $side => $sideLabel)
                                            @php $k = $fId.':'.$side; $value = old("answers.$fId.$side", $answers[$k]['valor'] ?? ''); @endphp
                                            <div class="col-12 col-xl-6">
                                                <div class="input-group">
                                                    <span class="input-group-text">{{ $sideLabel }}</span>
                                                    <input class="form-control" name="answers[{{ $fId }}][{{ $side }}]" value="{{ $value }}" placeholder="{{ $type === 'plus_minus_pair' ? '+ / -' : 'Valor' }}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($type === 'textarea')
                                    <textarea class="form-control" rows="2" name="answers[{{ $fId }}]">{{ old("answers.$fId", $answers[$fId]['valor'] ?? '') }}</textarea>
                                @elseif($type === 'select')
                                    @php $opts = collect($field->options_json ?? [])->filter()->values(); @endphp
                                    <select class="form-control" name="answers[{{ $fId }}]">
                                        <option value="">Seleccionar...</option>
                                        @foreach($opts as $opt)
                                            <option value="{{ $opt }}" @selected(old("answers.$fId", $answers[$fId]['valor'] ?? '') === $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                @elseif($type === 'pain_scale_1_10')
                                    <select class="form-control" name="answers[{{ $fId }}]">
                                        <option value="">Escala 1-10</option>
                                        @for($i=1;$i<=10;$i++)
                                            <option value="{{ $i }}" @selected((string)old("answers.$fId", $answers[$fId]['valor'] ?? '') === (string)$i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                @else
                                    <input class="form-control" name="answers[{{ $fId }}]" value="{{ old("answers.$fId", $answers[$fId]['valor'] ?? '') }}">
                                @endif
                            </div>
                        @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="mb-3">
                    <label class="form-label">Observaciones generales</label>
                    <textarea class="form-control" rows="3" name="observaciones">{{ old('observaciones', $evaluation->observaciones ?? '') }}</textarea>
                </div>

                <div class="d-flex gap-2 position-sticky bottom-0 bg-white pt-2">
                    <button type="submit" class="btn btn-primary">Guardar valoración</button>
                    <a class="btn btn-link" href="{{ backpack_url('osteo-evaluation') }}">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@if(!($evaluation?->exists))
    @push('after_scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('template_id_selector');
        if (!select) return;
        select.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('template_id', this.value);
            window.location.href = url.toString();
        });
    });
    </script>
    @endpush
@endif
