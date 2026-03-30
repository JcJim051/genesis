@if (in_array(strtolower(trim($entry->estado ?? '')), ['probable','no evaluado'], true))
    <a href="{{ backpack_url('programa-caso/' . $entry->getKey() . '/reject') }}" class="btn btn-sm btn-danger" title="No caso">
        <i class="la la-times"></i>
    </a>
@endif
