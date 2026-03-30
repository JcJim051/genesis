@if (strtolower(trim($entry->estado ?? '')) === 'confirmado')
    <a href="{{ backpack_url('programa-caso/' . $entry->getKey() . '/retirar') }}" class="btn btn-sm btn-warning" title="Retirar">
        <i class="la la-user-times"></i>
    </a>
@endif
