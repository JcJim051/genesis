@extends('crud::list')

@section('before_content')
    @php
        $clienteId = request('cliente_id');
        $sucursalId = request('sucursal_id');
    @endphp
    <div class="card p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1">Empresa</label>
                <select class="form-control" name="cliente_id">
                    <option value="">Todas</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" @selected((string)$clienteId === (string)$empresa->id)>{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Planta</label>
                <select class="form-control" name="sucursal_id">
                    <option value="">Todas</option>
                    @foreach ($plantas as $planta)
                        <option value="{{ $planta->id }}" @selected((string)$sucursalId === (string)$planta->id)>{{ $planta->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ backpack_url('encuesta-participacion') }}" class="btn btn-link">Limpiar</a>
            </div>
        </form>
    </div>
@endsection
