@if (in_array(strtolower(trim($entry->estado ?? '')), ['probable','no evaluado'], true))
    <a href="#"
       data-programa-decision-url="{{ backpack_url('programa-caso/' . $entry->getKey() . '/reject') }}"
       data-programa-decision-label="No caso"
       class="btn btn-sm btn-danger"
       title="No caso">
        <i class="la la-times"></i>
    </a>
@endif
