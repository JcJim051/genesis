@extends(backpack_view('blank'))

@section('content')
    <div class="container-fluid">
        <h2>Importar CIE10</h2>
        <p>Sube un archivo con columnas: <strong>codigo</strong>, <strong>diagnostico</strong>.</p>

        <form method="post" action="{{ backpack_url('cie10/import') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label">Archivo (.xlsx o .csv)</label>
                <input type="file" name="archivo" class="form-control" required>
                @error('archivo')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>
            <button class="btn btn-primary">Importar</button>
            <a href="{{ backpack_url('cie10') }}" class="btn btn-link">Volver</a>
        </form>
    </div>
@endsection
