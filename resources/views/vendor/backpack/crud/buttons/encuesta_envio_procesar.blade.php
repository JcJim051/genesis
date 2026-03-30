@php
    $procesado = $entry->procesado_en ?? null;
    $respuestasCount = ($entry->respuestas ?? collect())->count();
@endphp

@if (! $procesado || $respuestasCount === 0)
    <a href="{{ backpack_url('encuesta-envio/' . $entry->getKey() . '/procesar') }}"
       class="btn btn-sm btn-link"
       title="Generar links">
        <i class="la la-bolt"></i> Generar links
    </a>
@endif
