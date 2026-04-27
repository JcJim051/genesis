@if (($entry->programa?->slug ?? null) === 'osteomuscular')
    <a href="{{ backpack_url('programa-caso/' . $entry->getKey() . '/ipt/create-initial') }}" class="btn btn-sm btn-outline-primary" title="Crear IPT inicial">
        <i class="la la-clipboard-list"></i>
    </a>
@endif
