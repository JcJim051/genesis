@once
    <style>
        #programaCasoDecisionModal.modal {
            z-index: 2000 !important;
        }
        .modal-backdrop.show {
            z-index: 1990 !important;
        }
    </style>
    <div class="modal fade" id="programaCasoDecisionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="programaCasoDecisionForm">
                    @csrf
                    <input type="hidden" name="return_url" id="programaCasoDecisionReturnUrl" value="">
                    <div class="modal-header">
                        <h5 class="modal-title">Justificación de decisión</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2" id="programaCasoDecisionText">Ingresa una breve justificación.</p>
                        <textarea
                            name="observacion"
                            id="programaCasoDecisionObservacion"
                            rows="4"
                            class="form-control"
                            placeholder="Describe brevemente por qué tomas esta decisión"
                            required
                            minlength="5"
                            maxlength="1000"
                        ></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar decisión</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endonce

@once
    @push('after_scripts')
        <script>
            (function () {
                const modalEl = document.getElementById('programaCasoDecisionModal');
                const formEl = document.getElementById('programaCasoDecisionForm');
                const textEl = document.getElementById('programaCasoDecisionText');
                const obsEl = document.getElementById('programaCasoDecisionObservacion');
                const returnEl = document.getElementById('programaCasoDecisionReturnUrl');
                if (!modalEl || !formEl || !obsEl || typeof bootstrap === 'undefined') return;

                // Si el modal se renderiza dentro de una celda de tabla/listado, lo movemos al body
                // para evitar problemas de z-index/overflow y permitir interacción normal.
                if (modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }

                const modal = new bootstrap.Modal(modalEl);

                document.addEventListener('click', function (event) {
                    const trigger = event.target.closest('[data-programa-decision-url]');
                    if (!trigger) return;

                    event.preventDefault();

                    formEl.action = trigger.getAttribute('data-programa-decision-url');
                    if (returnEl) {
                        returnEl.value = window.location.href;
                    }
                    const actionLabel = trigger.getAttribute('data-programa-decision-label') || 'esta decisión';
                    textEl.textContent = 'Vas a marcar el caso como "' + actionLabel + '". Escribe una breve justificación.';
                    obsEl.value = '';
                    modal.show();
                });
            })();
        </script>
    @endpush
@endonce
