{{-- select2 from ajax (custom, no Pro) --}}
@php
    $field['wrapper']['data-init-function'] = $field['wrapper']['data-init-function'] ?? 'bpFieldInitSelect2FromAjaxElement';
    $field['allows_null'] = $field['allows_null'] ?? true;
    $current_value = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';

    if (is_object($current_value) && is_subclass_of(get_class($current_value), 'Illuminate\Database\Eloquent\Model')) {
        $current_value = $current_value->getKey();
    }

    $selected_entry = null;
    $selected_text = '';
    if ($current_value !== '' && $current_value !== null && isset($field['model'])) {
        $selected_entry = $field['model']::query()->find($current_value);
        if ($selected_entry) {
            $selected_text = method_exists($selected_entry, 'selectLabel')
                ? $selected_entry->selectLabel()
                : (string) $selected_entry->{$field['attribute']};
        }
    }

    $data_source = $field['data_source'] ?? '';
    $minimum_input_length = $field['minimum_input_length'] ?? 1;
    $placeholder = $field['placeholder'] ?? '';
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    @if(isset($field['prefix']) || isset($field['suffix'])) <div class="input-group"> @endif
        @if(isset($field['prefix'])) <span class="input-group-text">{!! $field['prefix'] !!}</span> @endif
        <select
            name="{{ $field['name'] }}"
            data-ajax-url="{{ $data_source }}"
            data-minimum-input-length="{{ $minimum_input_length }}"
            data-placeholder="{{ $placeholder }}"
            @include('crud::fields.inc.attributes', ['default_class' => 'form-control form-select'])
        >
            @if ($field['allows_null'])
                <option value="">-</option>
            @endif

            @if ($selected_entry)
                <option value="{{ $selected_entry->getKey() }}" selected>{{ $selected_text }}</option>
            @endif
        </select>
        @if(isset($field['suffix'])) <span class="input-group-text">{!! $field['suffix'] !!}</span> @endif
    @if(isset($field['prefix']) || isset($field['suffix'])) </div> @endif

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
    @bassetBlock('backpack/crud/fields/select2-field.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css">
    <style>
        .select2-container {
            width: 100% !important;
        }
    </style>
    @endBassetBlock
@endpush

@push('crud_fields_scripts')
    @bassetBlock('backpack/crud/fields/select2-from-ajax-field.js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        function bpFieldInitSelect2FromAjaxElement(element) {
            var $select = element.find('select');
            if ($select.data('select2')) {
                return;
            }

            $select.select2({
                width: '100%',
                allowClear: true,
                placeholder: $select.data('placeholder') || $select.attr('data-placeholder') || '',
                minimumInputLength: parseInt($select.attr('data-minimum-input-length') || '1', 10),
                language: {
                    inputTooShort: function (args) {
                        var remaining = args.minimum - args.input.length;
                        return remaining === 1
                            ? 'Escribe 1 carácter más para buscar'
                            : 'Escribe ' + remaining + ' caracteres más para buscar';
                    },
                    searching: function () {
                        return 'Buscando...';
                    },
                    noResults: function () {
                        return 'No se encontraron personas';
                    },
                    errorLoading: function () {
                        return 'No se pudieron cargar los resultados';
                    },
                    loadingMore: function () {
                        return 'Cargando más resultados...';
                    }
                },
                ajax: {
                    url: $select.attr('data-ajax-url'),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results || [],
                            pagination: {
                                more: !!(data.pagination && data.pagination.more)
                            }
                        };
                    }
                }
            });
        }
    </script>
    @endBassetBlock
@endpush
