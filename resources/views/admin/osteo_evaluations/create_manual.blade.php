@extends(backpack_view('blank'))

@push('after_styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
    <div class="col-lg-8 col-xl-7">
        <div class="card p-4">
            <h4 class="mb-3">Nueva Valoración Osteomuscular</h4>
            <p class="text-muted mb-3">Selecciona la persona. Se respetan las empresas/plantas de tu vista actual.</p>
            <form method="POST" action="{{ backpack_url('osteo-evaluation/create-manual') }}">
                @csrf
                <div class="mb-3">
                    @include('admin.partials.empleado_select2_ajax', [
                        'name' => 'empleado_id',
                        'required' => true,
                        'ajaxUrl' => backpack_url('osteo-evaluation/fetch/empleado'),
                    ])
                    <div class="form-text">Escribe el nombre o la cédula para buscar.</div>
                    @error('empleado_id')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
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

@push('after_scripts')
    @include('admin.partials.empleado_select2_ajax_assets')
@endpush
