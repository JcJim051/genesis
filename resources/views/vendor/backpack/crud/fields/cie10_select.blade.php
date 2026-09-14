{{-- CIE10 searchable Select2 AJAX with auto-fill --}}
@php
    $field['wrapper']['data-init-function'] = $field['wrapper']['data-init-function'] ?? 'bpFieldInitCie10Select';
    $current_value = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';
    $ajax_url = $field['data_source'] ?? backpack_url('cie10/fetch');
    $selected_entry = null;
    $selected_text = '';
    if ($current_value !== '' && $current_value !== null) {
        $selected_entry = \App\Models\Cie10::query()->find($current_value);
        if ($selected_entry) {
            $selected_text = $selected_entry->selectLabel();
        }
    }
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <select
        name="{{ $field['name'] }}"
        data-ajax-url="{{ $ajax_url }}"
        data-minimum-input-length="1"
        data-placeholder="{{ $field['placeholder'] ?? 'Buscar CIE10 por código o diagnóstico...' }}"
        data-no-results="No se encontraron códigos CIE10"
        data-cie10-lookup-url="{{ $field['lookup_url'] ?? '' }}"
        data-target-codigo="{{ $field['target_codigo'] ?? 'codigo_cie10' }}"
        data-target-diagnostico="{{ $field['target_diagnostico'] ?? 'diagnostico_texto' }}"
        @include('crud::fields.inc.attributes', ['default_class' => 'form-control form-select'])
    >
        <option value="">-</option>
        @if ($selected_entry)
            <option value="{{ $selected_entry->getKey() }}" selected>{{ $selected_text }}</option>
        @endif
    </select>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
    @bassetBlock('backpack/crud/fields/select2-field.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css">
    <style>
        .select2-container { width: 100% !important; }
    </style>
    @endBassetBlock
@endpush

@push('crud_fields_scripts')
    @bassetBlock('backpack/crud/fields/cie10-select-ajax-field.js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        function bpFieldInitCie10Select(element) {
            var $select = element.find('select');
            if ($select.data('cie10-init')) {
                return;
            }
            $select.data('cie10-init', true);

            if (!$select.data('select2')) {
                $select.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $select.data('placeholder') || '',
                    minimumInputLength: parseInt($select.attr('data-minimum-input-length') || '1', 10),
                    language: {
                        inputTooShort: function (args) {
                            var remaining = args.minimum - args.input.length;
                            return remaining === 1
                                ? 'Escribe 1 carácter más para buscar'
                                : 'Escribe ' + remaining + ' caracteres más para buscar';
                        },
                        searching: function () { return 'Buscando...'; },
                        noResults: function () {
                            return $select.attr('data-no-results') || 'No se encontraron códigos CIE10';
                        },
                        errorLoading: function () { return 'No se pudieron cargar los resultados'; },
                        loadingMore: function () { return 'Cargando más resultados...'; }
                    },
                    ajax: {
                        url: $select.attr('data-ajax-url'),
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term, page: params.page || 1 };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results || [],
                                pagination: { more: !!(data.pagination && data.pagination.more) }
                            };
                        }
                    }
                });
            }

            $select.on('change', function() {
                var id = $(this).val();
                var lookupUrl = $(this).data('cie10-lookup-url');
                var codigoField = $('[name="'+$select.data('target-codigo')+'"]');
                var diagnosticoField = $('[name="'+$select.data('target-diagnostico')+'"]');

                if (!id || !lookupUrl) {
                    if (diagnosticoField.length) {
                        diagnosticoField.prop('readonly', false);
                    }
                    return;
                }

                $.getJSON(lookupUrl.replace('__ID__', id), function(data) {
                    if (codigoField.length) {
                        codigoField.val(data.codigo).trigger('change');
                    }
                    if (diagnosticoField.length) {
                        diagnosticoField.val(data.diagnostico).trigger('change');
                        diagnosticoField.prop('readonly', true);
                    }
                });
            });

            if ($select.val()) {
                $select.trigger('change');
            }
        }
    </script>
    @endBassetBlock
@endpush
