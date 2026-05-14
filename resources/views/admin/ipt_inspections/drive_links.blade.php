@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card p-4">
            <h4 class="mb-1">Matrices en Drive</h4>
            <p class="text-muted mb-3">Alcance actual: {{ $scopeLabel }}</p>

            <div class="list-group">
                @foreach($items as $item)
                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>{{ $item['empresa'] }}</span>
                        <span class="badge bg-primary">Abrir</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
