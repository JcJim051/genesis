@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card p-4">
            <h4 class="mb-3">Constructor de Encuesta</h4>

            <form method="POST" action="{{ $encuesta->exists ? backpack_url('encuesta/' . $encuesta->id . '/builder') : backpack_url('encuesta/builder') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="titulo" value="{{ old('titulo', $encuesta->titulo) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Programa</label>
                        <select class="form-control" name="programa_id" required>
                            <option value="">Seleccione</option>
                            @foreach ($programas as $prog)
                                <option value="{{ $prog->id }}" @selected(old('programa_id', $encuesta->programa_id) == $prog->id)>{{ $prog->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Umbral puntaje</label>
                        <input type="number" class="form-control" name="umbral_puntaje" value="{{ old('umbral_puntaje', $encuesta->umbral_puntaje) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Empresa (alcance)</label>
                        <select class="form-control" name="cliente_id">
                            <option value="">Todas</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected(old('cliente_id', $encuesta->cliente_id) == $cliente->id)>{{ $cliente->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Planta (alcance)</label>
                        <select class="form-control" name="sucursal_id">
                            <option value="">Todas</option>
                            @foreach ($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $encuesta->sucursal_id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="activa" value="1" @checked(old('activa', $encuesta->activa))>
                            <span class="form-check-label">Activa</span>
                        </label>
                    </div>
                </div>

                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Preguntas</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-root-question">+ Agregar pregunta</button>
                </div>

                <div id="questions-container"></div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar encuesta</button>
                    <a href="{{ backpack_url('encuesta') }}" class="btn btn-link">Volver</a>
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
            <input type="hidden" class="question-parent-key" />
            <input type="hidden" class="question-parent-option" />

            <div class="row">
                <div class="col-md-8 mb-2">
                    <label class="form-label">Texto</label>
                    <input type="text" class="form-control question-text" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Orden</label>
                    <input type="number" class="form-control question-order" value="0">
                </div>
            </div>

            <div class="mt-2">
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
            <div class="col-md-5">
                <label class="form-label">Texto</label>
                <input type="text" class="form-control option-text" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Puntaje</label>
                <input type="number" class="form-control option-score" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label">Orden</label>
                <input type="number" class="form-control option-order" value="0">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary add-child">+ Subpregunta</button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-option">Eliminar</button>
            </div>
        </div>
        <input type="hidden" class="option-id" />
        <div class="child-questions ms-4 mt-2"></div>
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
        qEl.querySelector('.question-parent-key').setAttribute('name', `questions[${qKey}][parent_key]`);
        qEl.querySelector('.question-parent-option').setAttribute('name', `questions[${qKey}][parent_option_key]`);
        qEl.querySelector('.question-text').setAttribute('name', `questions[${qKey}][texto]`);
        qEl.querySelector('.question-order').setAttribute('name', `questions[${qKey}][orden]`);
    };

    const applyOptionNames = (optEl, qKey, oKey) => {
        optEl.querySelector('.option-id').setAttribute('name', `questions[${qKey}][options][${oKey}][id]`);
        optEl.querySelector('.option-text').setAttribute('name', `questions[${qKey}][options][${oKey}][texto]`);
        optEl.querySelector('.option-score').setAttribute('name', `questions[${qKey}][options][${oKey}][puntaje]`);
        optEl.querySelector('.option-order').setAttribute('name', `questions[${qKey}][options][${oKey}][orden]`);
    };

    const buildQuestion = (q = {}, parentKey = '', parentOptionKey = '') => {
        const qKey = q.key || newKey('q');
        const node = qTpl.content.firstElementChild.cloneNode(true);
        node.dataset.qkey = qKey;
        applyNames(node, qKey);
        node.querySelector('.question-id').value = q.id || '';
        node.querySelector('.question-parent-key').value = parentKey || '';
        node.querySelector('.question-parent-option').value = parentOptionKey || '';
        node.querySelector('.question-text').value = q.texto || '';
        node.querySelector('.question-order').value = q.orden ?? 0;

        node.querySelector('.remove-question').addEventListener('click', () => node.remove());

        const optionsContainer = node.querySelector('.options-container');
        node.querySelector('.add-option').addEventListener('click', () => {
            const opt = buildOption({}, qKey);
            optionsContainer.appendChild(opt);
        });

        (q.options || []).forEach(opt => {
            const optEl = buildOption(opt, qKey, qKey, opt.key);
            optionsContainer.appendChild(optEl);
        });

        return node;
    };

    const buildOption = (opt = {}, qKey, parentQKey, parentOptKey) => {
        const oKey = opt.key || newKey('o');
        const node = oTpl.content.firstElementChild.cloneNode(true);
        node.dataset.okey = oKey;
        applyOptionNames(node, qKey, oKey);
        node.querySelector('.option-id').value = opt.id || '';
        node.querySelector('.option-text').value = opt.texto || '';
        node.querySelector('.option-score').value = opt.puntaje ?? 0;
        node.querySelector('.option-order').value = opt.orden ?? 0;
        node.querySelector('.remove-option').addEventListener('click', () => node.remove());

        node.querySelector('.add-child').addEventListener('click', () => {
            const child = buildQuestion({}, qKey, oKey);
            node.querySelector('.child-questions').appendChild(child);
        });

        (opt.children || []).forEach(childQ => {
            const child = buildQuestion(childQ, qKey, oKey);
            node.querySelector('.child-questions').appendChild(child);
        });

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
