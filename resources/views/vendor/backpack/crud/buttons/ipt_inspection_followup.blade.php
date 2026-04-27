@if (($entry->tipo ?? null) === 'initial')
    <a href="{{ backpack_url('ipt/' . $entry->getKey() . '/create-followup') }}" class="btn btn-sm btn-outline-success">
        <i class="la la-plus"></i> Seguimiento
    </a>
@endif
