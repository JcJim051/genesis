@php $qs = request()->getQueryString(); @endphp
<a href="{{ backpack_url('pausa-participacion/export') }}{{ $qs ? ('?' . $qs) : '' }}" class="btn btn-sm btn-outline-primary">
    <i class="la la-file-excel"></i> Exportar
</a>
