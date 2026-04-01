@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card p-4">
            <h4 class="mb-3">Constructor de Pausa Activa</h4>

            <form method="POST" action="{{ $pausa->exists ? backpack_url('pausa/' . $pausa->id . '/builder') : backpack_url('pausa/builder') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" value="{{ old('nombre', $pausa->nombre) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Categoría</label>
                        <select class="form-control" name="categoria">
                            <option value="">Seleccione</option>
                            @foreach (['virtual' => 'Virtual','osteomuscular' => 'Osteomuscular','psicosocial' => 'Psicosocial','otros' => 'Otros'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('categoria', $pausa->categoria) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Video URL</label>
                        <input type="url" class="form-control" name="video_url" value="{{ old('video_url', $pausa->video_url) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tiempo mínimo (segundos)</label>
                        <input type="number" class="form-control" name="tiempo_minimo_segundos" value="{{ old('tiempo_minimo_segundos', $pausa->tiempo_minimo_segundos ?? 60) }}">
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="activa" value="1" @checked(old('activa', $pausa->activa))>
                            <span class="form-check-label">Activa</span>
                        </label>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="3">{{ old('descripcion', $pausa->descripcion) }}</textarea>
                    </div>
                </div>

                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Preguntas</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-root-question">+ Agregar pregunta</button>
                </div>

                <div id="questions-container"></div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar pausa</button>
                    <a href="{{ backpack_url('pausa') }}" class="btn btn-link">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="question-template">
    <div class="card border mb-3 question-item">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="question-title">Pregunta</strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-question">Eliminar</button>
            </div>
            <input type="hidden" class="question-id" />

            <div class="row">
                <div class="col-md-7 mb-2">
                    <label class="form-label">Texto</label>
                    <input type="text" class="form-control question-text" required>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">Tipo</label>
                    <select class="form-control question-type">
                        <option value="abierta">Abierta</option>
                        <option value="opcion">Opción múltiple</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Orden</label>
                    <input type="number" class="form-control question-order" value="0">
                </div>
            </div>

            <div class="mt-2 options-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Opciones</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-option">+ Opción</button>
                </div>
                <div class="options-container"></div>
            </div>
        </div>
    </div>
</template>

<template id="option-template">
    <div class="border rounded p-2 mb-2 option-item">
        <div class="row align-items-end">
            <div class="col-md-6">
                <label class="form-label">Texto</label>
                <input type="text" class="form-control option-text" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Valor</label>
                <input type="text" class="form-control option-value">
            </div>
            <div class="col-md-2">
                <label class="form-label">Orden</label>
                <input type="number" class="form-control option-order" value="0">
            </div>
            <div class="col-md-1 d-flex justify-content-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-option">Eliminar</button>
            </div>
        </div>
        <input type="hidden" class="option-id" />
    </div>
</template>

@push('after_scripts')
<script>
(() => {
    const data = @json($questions);
    const container = document.getElementById('questions-container');
    const qTpl = document.getElementById('question-template');
    const oTpl = document.getElementById('option-template');

    const newKey = (p) => `${p}${Math.random().toString(36).slice(2,8)}${Date.now()}`;

    const applyNames = (qEl, qKey) => {
        qEl.querySelector('.question-id').setAttribute('name', `questions[${qKey}][id]`);
        qEl.querySelector('.question-text').setAttribute('name', `questions[${qKey}][texto]`);
        qEl.querySelector('.question-type').setAttribute('name', `questions[${qKey}][tipo]`);
        qEl.querySelector('.question-order').setAttribute('name', `questions[${qKey}][orden]`);
    };

    const applyOptionNames = (optEl, qKey, oKey) => {
        optEl.querySelector('.option-id').setAttribute('name', `questions[${qKey}][options][${oKey}][id]`);
        optEl.querySelector('.option-text').setAttribute('name', `questions[${qKey}][options][${oKey}][texto]`);
        optEl.querySelector('.option-value').setAttribute('name', `questions[${qKey}][options][${oKey}][valor]`);
        optEl.querySelector('.option-order').setAttribute('name', `questions[${qKey}][options][${oKey}][orden]`);
    };

    const toggleOptions = (node, tipo) => {
        const wrap = node.querySelector('.options-wrapper');
        wrap.style.display = tipo === 'opcion' ? '' : 'none';
    };

    const buildQuestion = (q = {}) => {
        const qKey = q.key || newKey('q');
        const node = qTpl.content.firstElementChild.cloneNode(true);
        node.dataset.qkey = qKey;
        applyNames(node, qKey);
        node.querySelector('.question-id').value = q.id || '';
        node.querySelector('.question-text').value = q.texto || '';
        node.querySelector('.question-type').value = q.tipo || 'abierta';
        node.querySelector('.question-order').value = q.orden ?? 0;

        node.querySelector('.remove-question').addEventListener('click', () => node.remove());

        const optionsContainer = node.querySelector('.options-container');
        node.querySelector('.add-option').addEventListener('click', () => {
            optionsContainer.appendChild(buildOption({}, qKey));
        });

        node.querySelector('.question-type').addEventListener('change', (e) => {
            toggleOptions(node, e.target.value);
        });

        (q.options || []).forEach(opt => {
            optionsContainer.appendChild(buildOption(opt, qKey));
        });

        toggleOptions(node, q.tipo || 'abierta');
        return node;
    };

    const buildOption = (opt = {}, qKey) => {
        const oKey = opt.key || newKey('o');
        const node = oTpl.content.firstElementChild.cloneNode(true);
        node.dataset.okey = oKey;
        applyOptionNames(node, qKey, oKey);
        node.querySelector('.option-id').value = opt.id || '';
        node.querySelector('.option-text').value = opt.texto || '';
        node.querySelector('.option-value').value = opt.valor ?? '';
        node.querySelector('.option-order').value = opt.orden ?? 0;
        node.querySelector('.remove-option').addEventListener('click', () => node.remove());
        return node;
    };

    document.getElementById('add-root-question').addEventListener('click', () => {
        container.appendChild(buildQuestion());
    });

    (data || []).forEach(q => container.appendChild(buildQuestion(q)));
})();
</script>
@endpush
@endsection
