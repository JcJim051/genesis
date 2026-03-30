@php
    $procesado = $entry->procesado_en ?? null;
    $count = ($entry->participaciones ?? collect())->count();
@endphp

@if (! $procesado || $count === 0)
    <a href="{{ backpack_url('pausa-envio/' . $entry->getKey() . '/procesar') }}"
       class="btn btn-sm btn-link"
       title="Generar links y enviar WhatsApp">
        <i class="la la-bolt"></i> Generar links
    </a>
@endif
