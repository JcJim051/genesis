{{-- select2 multiple (custom, no Pro) --}}
@php
    $field['wrapper']['data-init-function'] = $field['wrapper']['data-init-function'] ?? 'bpFieldInitSelect2Element';
    if (!isset($field['options'])) {
        $options = $field['model']::all();
    } else {
        $options = call_user_func($field['options'], $field['model']::query());
    }
    $field['allows_null'] = $field['allows_null'] ?? true;

    $field['value'] = old_empty_or_null($field['name'], collect()) ??  $field['value'] ?? $field['default'] ?? collect();

    if (is_a($field['value'], \Illuminate\Support\Collection::class)) {
        $field['value'] = $field['value']->pluck(app($field['model'])->getKeyName())->toArray();
    }
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    <input type="hidden" name="{{ $field['name'] }}" value="" @if(in_array('disabled', $field['attributes'] ?? [])) disabled @endif />
    <select
        name="{{ $field['name'] }}[]"
        @include('crud::fields.inc.attributes', ['default_class' => 'form-control form-select'])
        multiple
    >
        @if (count($options))
            @foreach ($options as $option)
                @if(in_array($option->getKey(), $field['value']))
                    <option value="{{ $option->getKey() }}" selected>{{ $option->{$field['attribute']} }}</option>
                @else
                    <option value="{{ $option->getKey() }}">{{ $option->{$field['attribute']} }}</option>
                @endif
            @endforeach
        @endif
    </select>

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
