{{-- select2 from array (custom, no Pro) --}}
@php
    $field['wrapper']['data-init-function'] = $field['wrapper']['data-init-function'] ?? 'bpFieldInitSelect2Element';
    $field['allows_null'] = $field['allows_null'] ?? true;
    $current_value = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <select
        name="{{ $field['name'] }}"
        @include('crud::fields.inc.attributes', ['default_class' => 'form-control form-select'])
    >
        @if ($field['allows_null'])
            <option value="">-</option>
        @endif

        @foreach ($field['options'] as $key => $value)
            <option value="{{ $key }}" @if($current_value == $key) selected @endif>{{ $value }}</option>
        @endforeach
    </select>

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
    @bassetBlock('backpack/crud/fields/select2-field.js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        function bpFieldInitSelect2Element(element) {
            var $select = element.find('select');
            if ($select.data('select2')) {
                return;
            }
            $select.select2({
                width: 'resolve',
                placeholder: $select.attr('placeholder') || ''
            });
        }
    </script>
    @endBassetBlock
@endpush
