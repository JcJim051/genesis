{{-- CIE10 select with auto-fill --}}
@php
    $field['wrapper']['data-init-function'] = $field['wrapper']['data-init-function'] ?? 'bpFieldInitCie10Select';
    $current_value = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';
    $options = $field['options'] ?? [];
    if (is_callable($options)) {
        $options = $options();
    }
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <select
        name="{{ $field['name'] }}"
        data-cie10-lookup-url="{{ $field['lookup_url'] ?? '' }}"
        data-target-codigo="{{ $field['target_codigo'] ?? 'codigo_cie10' }}"
        data-target-diagnostico="{{ $field['target_diagnostico'] ?? 'diagnostico_texto' }}"
        @include('crud::fields.inc.attributes', ['default_class' => 'form-control form-select'])
    >
        <option value="">-</option>
        @foreach ($options as $key => $value)
            <option value="{{ $key }}" @if($current_value == $key) selected @endif>{{ $value }}</option>
        @endforeach
    </select>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    <script>
        function bpFieldInitCie10Select(element) {
            var $select = element.find('select');
            if ($select.data('cie10-init')) {
                return;
            }
            $select.data('cie10-init', true);

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
@endpush
