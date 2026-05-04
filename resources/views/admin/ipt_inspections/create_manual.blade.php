@extends(backpack_view('blank'))

@push('after_styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single {
            min-height: 38px;
            border: 1px solid #d1d5db;
            border-radius: .375rem;
            padding-top: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 6px;
        }
    </style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-8 col-xl-7">
        <div class="card p-4">
            <h4 class="mb-2">Crear inspección IPT</h4>
            <p class="text-muted mb-3">Selecciona la persona. Se respetan las empresas/plantas de tu vista actual.</p>

            <form method="POST" action="{{ backpack_url('ipt-inspection/create-manual') }}">
                @csrf

                <div class="mb-3">
                    <label for="empleado_id" class="form-label">Persona</label>
                    <select id="empleado_id" name="empleado_id" class="form-control" required>
                        <option value="">Selecciona una persona...</option>
                        @foreach($empleados as $empleado)
                            <option value="{{ $empleado->id }}" data-cliente-id="{{ (int) $empleado->cliente_id }}" @selected(old('empleado_id') == $empleado->id)>
                                {{ $empleado->nombre }} · {{ $empleado->cedula }}
                                @if($empleado->cliente?->nombre)
                                    · {{ $empleado->cliente->nombre }}
                                @endif
                                @if($empleado->sucursal?->nombre)
                                    · {{ $empleado->sucursal->nombre }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('empleado_id')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="template_id" class="form-label">Plantilla IPT</label>
                    <select id="template_id" name="template_id" class="form-control" required>
                        <option value="">Selecciona una plantilla...</option>
                        @foreach($templatePool as $tpl)
                            <option
                                value="{{ $tpl->id }}"
                                data-cliente-id="{{ (int) $tpl->cliente_id }}"
                                @selected((int) old('template_id') === (int) $tpl->id)
                            >
                                {{ $tpl->nombre_publico }}{{ $tpl->segmento ? (' · ' . $tpl->segmento) : '' }}{{ $tpl->codigo ? (' [' . $tpl->codigo . ']') : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Solo se mostrarán plantillas de la empresa de la persona seleccionada.</div>
                    @error('template_id')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-arrow-right"></i> Continuar
                    </button>
                    <a href="{{ backpack_url('ipt-inspection') }}" class="btn btn-link">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('after_scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.jQuery || !jQuery.fn.select2) return;
            const $empleado = jQuery('#empleado_id');
            const $template = jQuery('#template_id');
            const originalTemplateOptions = $template.find('option').map(function () {
                const $opt = jQuery(this);
                return {
                    value: $opt.attr('value') || '',
                    text: $opt.text(),
                    clienteId: parseInt($opt.attr('data-cliente-id') || '0', 10),
                    selected: $opt.is(':selected'),
                };
            }).get();

            const refreshTemplatesByEmployee = () => {
                const empleadoId = parseInt($empleado.val() || '0', 10);
                const empleadoOption = $empleado.find('option[value="' + empleadoId + '"]');
                const clienteId = parseInt(empleadoOption.attr('data-cliente-id') || '0', 10);
                const current = String($template.val() || '');

                const filtered = originalTemplateOptions.filter((opt) => {
                    if (!opt.value) return true; // placeholder
                    if (!clienteId) return true;
                    return parseInt(opt.clienteId || 0, 10) === clienteId;
                });

                $template.empty();
                filtered.forEach((opt) => {
                    const option = new Option(opt.text, opt.value, false, false);
                    if (opt.clienteId) {
                        option.setAttribute('data-cliente-id', String(opt.clienteId));
                    }
                    $template.append(option);
                });

                const hasCurrent = filtered.some((opt) => opt.value === current);
                if (hasCurrent) {
                    $template.val(current);
                } else {
                    $template.val('');
                }
                $template.trigger('change.select2');
            };

            $empleado.select2({
                width: '100%',
                placeholder: 'Buscar persona por nombre, cédula, empresa o planta...',
                allowClear: true
            });

            $template.select2({
                width: '100%',
                placeholder: 'Selecciona plantilla IPT...',
                allowClear: true
            });

            $empleado.on('change', refreshTemplatesByEmployee);
            refreshTemplatesByEmployee();
        });
    </script>
@endpush
