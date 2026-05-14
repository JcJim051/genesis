<form method="POST" action="{{ route('ipt-inspection.matrix-sync-drive') }}" style="display:inline-block; margin-right:6px;">
    @csrf
    <button type="submit" class="btn btn-sm btn-outline-primary">
        <i class="la la-google"></i> Generar/Actualizar en Drive
    </button>
</form>
