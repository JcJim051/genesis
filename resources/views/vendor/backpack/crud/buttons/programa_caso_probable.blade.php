@if ($entry->estado === 'No evaluado')
    <a href="#"
       data-programa-decision-url="{{ backpack_url('programa-caso/' . $entry->getKey() . '/probable') }}"
       data-programa-decision-label="Probable"
       class="btn btn-sm"
       style="background-color:#f59e0b;color:#fff;border-color:#f59e0b;"
       title="Marcar como Probable">
        <i class="la la-exclamation-triangle"></i>
    </a>
@endif
