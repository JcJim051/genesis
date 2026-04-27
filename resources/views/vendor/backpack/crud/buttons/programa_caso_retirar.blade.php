@if (strtolower(trim($entry->estado ?? '')) === 'confirmado')
    <a href="#"
       data-programa-decision-url="{{ backpack_url('programa-caso/' . $entry->getKey() . '/retirar') }}"
       data-programa-decision-label="Retirar"
       class="btn btn-sm btn-warning"
       title="Retirar">
        <i class="la la-user-times"></i>
    </a>
@endif
