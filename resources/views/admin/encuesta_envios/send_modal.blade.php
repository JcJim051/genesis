@push('after_scripts')
<div class="modal fade" id="encuesta-envio-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="#" class="modal-content" id="encuesta-envio-modal-form">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Enviar encuesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Enviar a</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="send_mode" id="encuesta-send-all" value="all" checked>
                        <label class="form-check-label" for="encuesta-send-all">Todos</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="send_mode" id="encuesta-send-pending" value="pending">
                        <label class="form-check-label" for="encuesta-send-pending">Solo pendientes</label>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="only_incomplete" id="encuesta-send-incomplete" value="1" checked>
                        <label class="form-check-label" for="encuesta-send-incomplete">Reenviar a no completados</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Programar envío (opcional)</label>
                    <input type="datetime-local" class="form-control" name="scheduled_at">
                    <small class="text-muted">Si eliges fecha/hora futura, el envío quedará programado.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Enviar / Programar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.encuesta-envio-send');
    if (!btn) return;
    e.preventDefault();

    const action = btn.getAttribute('data-action');
    const form = document.getElementById('encuesta-envio-modal-form');
    form.setAttribute('action', action);

    const modalEl = document.getElementById('encuesta-envio-modal');
    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
});
</script>
@endpush
