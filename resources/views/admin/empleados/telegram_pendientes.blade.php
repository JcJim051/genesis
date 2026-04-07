@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card p-4 mb-3">
            <h4 class="mb-2">Telegram pendientes de activación</h4>
            <div class="d-flex flex-wrap gap-3 text-muted">
                <span>Total: {{ $total }}</span>
                <span>Con correo: {{ $conCorreo }}</span>
                <span>Sin correo: {{ $sinCorreo }}</span>
            </div>
            <div class="mt-3">
                <input type="text" class="form-control" id="telegram-search" placeholder="Buscar por nombre, cédula o correo...">
            </div>
            <div class="mt-3 d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" href="{{ backpack_url('empleado/telegram-pendientes') }}">
                    <i class="la la-download"></i> Descargar CSV
                </a>
                <form method="POST" action="{{ backpack_url('empleado/telegram-email-pendientes') }}">
                    @csrf
                    <button class="btn btn-sm btn-primary" type="submit">
                        <i class="la la-envelope"></i> Enviar correos a pendientes
                    </button>
                </form>
            </div>
        </div>

        <div class="card p-4">
            @if ($empleados->isEmpty())
                <div class="text-muted">No hay personas pendientes de activación.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped mb-0" id="telegram-pendientes-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>Empresa</th>
                                <th>Planta</th>
                                <th>Correo</th>
                                <th>Link</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($empleados as $empleado)
                                <tr>
                                    <td>{{ $empleado->nombre }}</td>
                                    <td>{{ $empleado->cedula }}</td>
                                    <td>{{ $empleado->cliente?->nombre }}</td>
                                    <td>{{ $empleado->sucursal?->nombre }}</td>
                                    <td>{{ $empleado->correo_electronico }}</td>
                                    <td>
                                        @php $link = $empleado->getTelegramActivationUrl(); @endphp
                                        <a href="{{ $link }}" target="_blank">Ver link</a>
                                    </td>
                                    <td class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-success" type="button" data-copy-link="{{ $link }}">Copiar link</button>
                                        @if ($empleado->correo_electronico)
                                            <form method="POST" action="{{ backpack_url('empleado/' . $empleado->id . '/telegram-email') }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Enviar correo</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $empleados->links() }}</div>
            @endif
        </div>
    </div>
</div>

@push('after_scripts')
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-copy-link]');
    if (!btn) return;
    const link = btn.getAttribute('data-copy-link');

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(link);
    } else {
        const input = document.createElement('input');
        input.value = link;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
    }
});

document.getElementById('telegram-search')?.addEventListener('input', function (e) {
    const term = (e.target.value || '').toLowerCase();
    const rows = document.querySelectorAll('#telegram-pendientes-table tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});
</script>
@endpush
@endsection
