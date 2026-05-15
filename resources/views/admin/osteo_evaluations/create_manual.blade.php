@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-lg-8 col-xl-7">
        <div class="card p-4">
            <h4 class="mb-3">Nueva Valoración Osteomuscular</h4>
            <form method="POST" action="{{ backpack_url('osteo-evaluation/create-manual') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Persona</label>
                    <select class="form-control" name="empleado_id" required>
                        <option value="">Selecciona persona</option>
                        @foreach($empleados as $empleado)
                            <option value="{{ $empleado->id }}">
                                {{ $empleado->nombre }} · {{ $empleado->cedula }} · {{ $empleado->cliente?->nombre ?? 'SIN EMPRESA' }}{{ $empleado->sucursal?->nombre ? (' / ' . $empleado->sucursal->nombre) : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Continuar</button>
                    <a class="btn btn-link" href="{{ backpack_url('osteo-evaluation') }}">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

