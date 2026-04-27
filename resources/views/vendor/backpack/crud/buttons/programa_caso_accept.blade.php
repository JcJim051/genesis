@if (in_array(strtolower(trim($entry->estado ?? '')), ['probable','no evaluado'], true))
    <a href="#"
       data-programa-decision-url="{{ backpack_url('programa-caso/' . $entry->getKey() . '/accept') }}"
       data-programa-decision-label="Confirmado"
       class="btn btn-sm btn-success"
       title="Confirmado">
        <i class="la la-check"></i>
    </a>
@endif
