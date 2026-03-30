@if (in_array(strtolower(trim($entry->estado ?? '')), ['probable','no evaluado'], true))
    <a href="{{ backpack_url('programa-caso/' . $entry->getKey() . '/accept') }}" class="btn btn-sm btn-success" title="Confirmado">
        <i class="la la-check"></i>
    </a>
@endif
