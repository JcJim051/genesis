@if ($entry->estado === 'No evaluado')
    <a href="{{ backpack_url('programa-caso/' . $entry->getKey() . '/probable') }}"
       class="btn btn-sm"
       style="background-color:#f59e0b;color:#fff;border-color:#f59e0b;"
       title="Marcar como Probable">
        <i class="la la-exclamation-triangle"></i>
    </a>
@endif
