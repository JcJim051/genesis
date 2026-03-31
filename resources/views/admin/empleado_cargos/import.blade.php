@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card p-4">
            <h4 class="mb-3">Importar Cargos</h4>
            <p class="text-muted">Sube la plantilla con CÉDULA, CARGO y FECHA_INICIO.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ backpack_url('empleado-cargo/import') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Archivo (.xlsx/.csv)</label>
                    <input type="file" name="archivo" class="form-control" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="la la-upload"></i> Importar</button>
                    <a href="{{ backpack_url('empleado-cargo/template') }}" class="btn btn-outline-secondary"><i class="la la-download"></i> Descargar plantilla</a>
                    <a href="{{ backpack_url('empleado-cargo') }}" class="btn btn-link">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
