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
                    @include('admin.partials.empleado_select2_ajax', [
                        'name' => 'empleado_id',
                        'required' => true,
                        'ajaxUrl' => backpack_url('ipt-inspection/fetch/empleado'),
                    ])
                    <div class="form-text">Escribe el nombre o la cédula para buscar.</div>
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
    @include('admin.partials.empleado_select2_ajax_assets')
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

            const selectedClienteId = () => {
                const data = ($empleado.select2('data') || [])[0] || {};
                if (data.cliente_id) {
                    return parseInt(data.cliente_id, 10);
                }
                const option = $empleado.find('option:selected');
                return parseInt(option.attr('data-cliente-id') || '0', 10);
            };

            const refreshTemplatesByEmployee = () => {
                const clienteId = selectedClienteId();
                const current = String($template.val() || '');

                const filtered = originalTemplateOptions.filter((opt) => {
                    if (!opt.value) return true;
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
                $template.val(hasCurrent ? current : '');
                $template.trigger('change.select2');
            };

            $template.select2({
                width: '100%',
                placeholder: 'Selecciona plantilla IPT...',
                allowClear: true
            });

            $empleado.on('change select2:select select2:clear', refreshTemplatesByEmployee);
            refreshTemplatesByEmployee();
        });
    </script>
@endpush
