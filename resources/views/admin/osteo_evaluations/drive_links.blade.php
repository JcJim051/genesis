@extends(backpack_view('blank'))

@section('content')
<div class="card p-4">
    <h4 class="mb-1">Matrices Osteomuscular en Drive</h4>
    <div class="text-muted mb-3">Alcance actual: {{ $scopeLabel }}</div>
    <div class="list-group">
        @foreach($items as $item)
            <a href="{{ $item['url'] }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span>{{ $item['empresa'] }}</span>
                <span class="badge bg-primary">Abrir</span>
            </a>
        @endforeach
    </div>
</div>
@endsection

