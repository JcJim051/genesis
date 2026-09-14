@php
    $fieldName = $name ?? 'empleado_id';
    $fieldId = $id ?? $fieldName;
    $fieldLabel = $label ?? 'Persona';
    $required = $required ?? false;
    $placeholder = $placeholder ?? 'Buscar persona por nombre o cédula...';
    $ajaxUrl = $ajaxUrl ?? '';
    $selectedEmpleado = $selected ?? null;
    $oldId = old($fieldName);
    if (! $selectedEmpleado && $oldId) {
        $selectedEmpleado = \App\Models\Empleado::query()->with(['cliente', 'sucursal'])->find($oldId);
    }
@endphp

<label for="{{ $fieldId }}" class="form-label">{{ $fieldLabel }}</label>
<select
    id="{{ $fieldId }}"
    name="{{ $fieldName }}"
    class="form-control js-empleado-select2-ajax"
    data-ajax-url="{{ $ajaxUrl }}"
    data-minimum-input-length="1"
    data-placeholder="{{ $placeholder }}"
    data-no-results="No se encontraron personas"
    @if($required) required @endif
>
    <option value="">{{ $placeholder }}</option>
    @if($selectedEmpleado)
        <option value="{{ $selectedEmpleado->id }}" data-cliente-id="{{ (int) $selectedEmpleado->cliente_id }}" selected>
            {{ $selectedEmpleado->selectLabelWithScope() }}
        </option>
    @endif
</select>
