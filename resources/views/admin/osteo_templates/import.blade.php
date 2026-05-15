@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-lg-7 col-xl-6">
        <div class="card p-4">
            <h4 class="mb-3">Importar Plantilla Osteomuscular</h4>
            <p class="text-muted">Sube un archivo `.json` exportado desde Genesis.</p>
            <form method="POST" action="{{ backpack_url('osteo-template/import') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Empresa destino</label>
                    <select class="form-control" name="cliente_id" required>
                        <option value="">Seleccionar empresa</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Archivo de plantilla</label>
                    <input type="file" class="form-control" name="template_file" accept=".json" required>
                </div>
                <div class="mb-3">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="replace" value="1">
                        <span class="form-check-label">Reemplazar si ya existe una plantilla con mismo nombre/código</span>
                    </label>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Importar</button>
                    <a class="btn btn-link" href="{{ backpack_url('osteo-template') }}">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

