@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card p-4">
            <h4 class="mb-3">Constructor de Plantilla IPT</h4>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Revisa los campos:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $template->exists ? backpack_url('ipt-template/' . $template->id . '/builder') : backpack_url('ipt-template/builder') }}">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Empresa(s)</label>
                        <select class="form-control form-control-sm js-select2-companies" name="cliente_ids[]" multiple required>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected(in_array((int) $cliente->id, collect(old('cliente_ids', $selectedClienteIds ?? [$template->cliente_id]))->map(fn($v) => (int) $v)->all(), true))>{{ $cliente->nombre }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Selecciona una o varias empresas. Puedes quitar cada selección con la “x”.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nombre público</label>
                        <input class="form-control" name="nombre_publico" value="{{ old('nombre_publico', $template->nombre_publico) }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Código</label>
                        <input class="form-control" name="codigo" value="{{ old('codigo', $template->codigo) }}" placeholder="VDT-ADM">
                    </div>
                    <div class="col-md-10">
                        <label class="form-label">Segmento / población</label>
                        <input class="form-control" name="segmento" value="{{ old('segmento', $template->segmento) }}" placeholder="Administrativo VDT / Operativo">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="activo" value="1" @checked(old('activo', $template->activo ?? true))>
                            <span class="form-check-label">Activa</span>
                        </label>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Secciones y preguntas</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-section">+ Agregar sección</button>
                </div>
                <div id="sections-container"></div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Reglas de riesgo</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-risk-rule">+ Regla</button>
                </div>
                <div id="risk-rules-container"></div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Requerimientos de estación</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-requirement">+ Requerimiento</button>
                </div>
                <div id="requirements-container"></div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar plantilla</button>
                    <a class="btn btn-link" href="{{ backpack_url('ipt-template') }}">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .ipt-collapsible > summary {
        cursor: pointer;
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem 1rem;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
    }
    .ipt-collapsible > summary::-webkit-details-marker {
        display: none;
    }
    .ipt-collapsible > summary::after {
        content: '▸';
        font-size: 14px;
        transition: transform .2s ease;
        color: #6c757d;
    }
    .ipt-collapsible[open] > summary::after {
        transform: rotate(90deg);
    }
    .ipt-collapsible:not([open]) > summary {
        border-bottom: 0;
    }
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        border-radius: .375rem;
        min-height: 38px;
        padding: 2px 6px;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #86b7fe;
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .15);
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #e9f5ff;
        border: 1px solid #c7e2ff;
        color: #0f3d70;
        border-radius: 999px;
        padding: 2px 10px 2px 8px;
        margin-top: 4px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #0f3d70;
        margin-right: 6px;
    }
    .select2-dropdown {
        border-color: #ced4da;
    }
</style>

<template id="section-template">
    <div class="card border mb-3 section-item">
        <details class="ipt-collapsible">
            <summary>
                <span class="section-summary-title">Sección sin título</span>
            </summary>
            <div class="card-body">
                <div class="d-flex justify-content-end align-items-center mb-2">
                    <button class="btn btn-sm btn-outline-danger remove-section" type="button">Eliminar sección</button>
                </div>
                <input type="hidden" class="section-id">
                <div class="row g-2 mb-3">
                    <div class="col-md-10">
                        <label class="form-label">Título</label>
                        <input class="form-control section-title" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Orden</label>
                        <input type="number" class="form-control section-order" value="0">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Preguntas</span>
                    <button class="btn btn-sm btn-outline-secondary add-question" type="button">+ Pregunta</button>
                </div>
                <div class="questions-container"></div>
            </div>
        </details>
    </div>
</template>

<template id="question-template">
    <details class="ipt-collapsible border rounded p-2 mb-2 question-item">
        <summary>
            <span class="question-summary-text">Pregunta nueva</span>
        </summary>
        <div class="pt-2">
        <input type="hidden" class="question-id">
        <div class="row g-2">
            <div class="col-md-5">
                <label class="form-label">Pregunta</label>
                <input class="form-control question-text" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Orden</label>
                <input type="number" class="form-control question-order" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label">Puntaje</label>
                <input type="number" class="form-control question-score" value="1" min="0">
            </div>
            <div class="col-md-2">
                <label class="form-label">Puntúa con</label>
                <select class="form-control question-score-on-answer">
                    <option value="si">SI</option>
                    <option value="no">NO</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-sm btn-outline-danger remove-question" type="button">X</button>
            </div>
            <div class="col-12">
                <label class="form-check mt-2">
                    <input type="checkbox" class="form-check-input question-scorable" checked>
                    <span class="form-check-label">Scorable</span>
                </label>
            </div>
        </div>
        </div>
    </details>
</template>

<template id="risk-rule-template">
    <details class="ipt-collapsible border rounded p-2 mb-2 risk-rule-item">
        <summary>
            <span class="risk-summary-text">Regla de riesgo</span>
        </summary>
        <div class="pt-2">
        <input type="hidden" class="risk-id">
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label">Nivel</label>
                <select class="form-control risk-level">
                    <option value="bajo">bajo</option>
                    <option value="medio">medio</option>
                    <option value="alto">alto</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Min</label>
                <input type="number" class="form-control risk-min" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label">Max</label>
                <input type="number" class="form-control risk-max" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Meses seguimiento</label>
                <input type="number" class="form-control risk-months" value="6" min="1">
            </div>
            <div class="col-md-2">
                <label class="form-label">Orden</label>
                <input type="number" class="form-control risk-order" value="0">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-risk">X</button>
            </div>
        </div>
        </div>
    </details>
</template>

<template id="requirement-template">
    <details class="ipt-collapsible border rounded p-2 mb-2 requirement-item">
        <summary>
            <span class="requirement-summary-text">Requerimiento</span>
        </summary>
        <div class="pt-2">
        <input type="hidden" class="requirement-id">
        <div class="row g-2">
            <div class="col-md-8">
                <label class="form-label">Nombre</label>
                <input class="form-control requirement-name" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Orden</label>
                <input type="number" class="form-control requirement-order" value="0">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <label class="form-check mb-1">
                    <input type="checkbox" class="form-check-input requirement-active" checked>
                    <span class="form-check-label">Activo</span>
                </label>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-requirement">X</button>
            </div>
        </div>
        </div>
    </details>
</template>

@push('after_scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(() => {
    const companySelect = document.querySelector('.js-select2-companies');
    if (companySelect && window.jQuery && jQuery.fn.select2) {
        jQuery(companySelect).select2({
            width: '100%',
            placeholder: 'Selecciona empresa(s)',
            closeOnSelect: false,
            allowClear: false,
        });
    }

    const sections = @json($sections);
    const riskRules = @json($riskRules);
    const requirements = @json($requirements);

    const sectionTpl = document.getElementById('section-template');
    const questionTpl = document.getElementById('question-template');
    const riskTpl = document.getElementById('risk-rule-template');
    const requirementTpl = document.getElementById('requirement-template');

    const sectionsContainer = document.getElementById('sections-container');
    const riskContainer = document.getElementById('risk-rules-container');
    const requirementsContainer = document.getElementById('requirements-container');

    const key = (prefix) => `${prefix}${Math.random().toString(36).slice(2,8)}${Date.now()}`;

    function applySectionNames(node, sectionKey) {
        node.querySelector('.section-id').name = `sections[${sectionKey}][id]`;
        node.querySelector('.section-title').name = `sections[${sectionKey}][titulo]`;
        node.querySelector('.section-order').name = `sections[${sectionKey}][orden]`;
    }

    function applyQuestionNames(node, sectionKey, questionKey) {
        node.querySelector('.question-id').name = `sections[${sectionKey}][questions][${questionKey}][id]`;
        node.querySelector('.question-text').name = `sections[${sectionKey}][questions][${questionKey}][texto]`;
        node.querySelector('.question-order').name = `sections[${sectionKey}][questions][${questionKey}][orden]`;
        node.querySelector('.question-score').name = `sections[${sectionKey}][questions][${questionKey}][si_score]`;
        node.querySelector('.question-score-on-answer').name = `sections[${sectionKey}][questions][${questionKey}][score_on_answer]`;
        node.querySelector('.question-scorable').name = `sections[${sectionKey}][questions][${questionKey}][scorable]`;
        node.querySelector('.question-scorable').value = 1;
    }

    function applyRiskNames(node, ruleKey) {
        node.querySelector('.risk-id').name = `risk_rules[${ruleKey}][id]`;
        node.querySelector('.risk-level').name = `risk_rules[${ruleKey}][nivel]`;
        node.querySelector('.risk-min').name = `risk_rules[${ruleKey}][min_score]`;
        node.querySelector('.risk-max').name = `risk_rules[${ruleKey}][max_score]`;
        node.querySelector('.risk-months').name = `risk_rules[${ruleKey}][followup_months]`;
        node.querySelector('.risk-order').name = `risk_rules[${ruleKey}][orden]`;
    }

    function applyRequirementNames(node, reqKey) {
        node.querySelector('.requirement-id').name = `requirements[${reqKey}][id]`;
        node.querySelector('.requirement-name').name = `requirements[${reqKey}][nombre]`;
        node.querySelector('.requirement-order').name = `requirements[${reqKey}][orden]`;
        node.querySelector('.requirement-active').name = `requirements[${reqKey}][activo]`;
        node.querySelector('.requirement-active').value = 1;
    }

    function buildQuestion(question = {}, sectionKey, onChange = null) {
        const qKey = question.key || key('q');
        const node = questionTpl.content.firstElementChild.cloneNode(true);
        applyQuestionNames(node, sectionKey, qKey);

        const updateQuestionSummary = () => {
            const text = (node.querySelector('.question-text').value || '').trim();
            const order = node.querySelector('.question-order').value || 0;
            const scoreOn = node.querySelector('.question-score-on-answer').value === 'no' ? 'NO' : 'SI';
            node.querySelector('.question-summary-text').textContent = `Pregunta ${order}: ${text || 'sin texto'} (puntúa ${scoreOn})`;
        };

        node.querySelector('.question-id').value = question.id || '';
        node.querySelector('.question-text').value = question.texto || '';
        node.querySelector('.question-order').value = question.orden ?? 0;
        node.querySelector('.question-score').value = question.si_score ?? 1;
        node.querySelector('.question-score-on-answer').value = question.score_on_answer ?? 'si';
        node.querySelector('.question-scorable').checked = question.scorable ?? true;
        node.querySelector('.question-text').addEventListener('input', () => {
            updateQuestionSummary();
            if (typeof onChange === 'function') onChange();
        });
        node.querySelector('.question-order').addEventListener('input', () => {
            updateQuestionSummary();
            if (typeof onChange === 'function') onChange();
        });
        node.querySelector('.question-score-on-answer').addEventListener('change', () => {
            updateQuestionSummary();
            if (typeof onChange === 'function') onChange();
        });
        updateQuestionSummary();

        node.querySelector('.remove-question').addEventListener('click', () => {
            node.remove();
            if (typeof onChange === 'function') onChange();
        });

        return node;
    }

    function buildSection(section = {}) {
        const sKey = section.key || key('s');
        const node = sectionTpl.content.firstElementChild.cloneNode(true);
        applySectionNames(node, sKey);

        const updateSectionSummary = () => {
            const title = (node.querySelector('.section-title').value || '').trim();
            const questionsCount = node.querySelectorAll('.question-item').length;
            node.querySelector('.section-summary-title').textContent = `${title || 'Sección sin título'} (${questionsCount} preguntas)`;
        };

        node.querySelector('.section-id').value = section.id || '';
        node.querySelector('.section-title').value = section.titulo || '';
        node.querySelector('.section-order').value = section.orden ?? 0;
        node.querySelector('.section-title').addEventListener('input', updateSectionSummary);

        const questionsContainer = node.querySelector('.questions-container');

        node.querySelector('.add-question').addEventListener('click', () => {
            questionsContainer.appendChild(buildQuestion({}, sKey, updateSectionSummary));
            updateSectionSummary();
        });

        node.querySelector('.remove-section').addEventListener('click', () => node.remove());

        (section.questions || []).forEach((question) => {
            questionsContainer.appendChild(buildQuestion(question, sKey, updateSectionSummary));
        });
        updateSectionSummary();

        return node;
    }

    function buildRiskRule(rule = {}) {
        const rKey = rule.key || key('r');
        const node = riskTpl.content.firstElementChild.cloneNode(true);
        applyRiskNames(node, rKey);

        const updateRiskSummary = () => {
            const nivel = (node.querySelector('.risk-level').value || 'bajo').toUpperCase();
            const min = node.querySelector('.risk-min').value || 0;
            const max = node.querySelector('.risk-max').value || 0;
            const months = node.querySelector('.risk-months').value || 0;
            node.querySelector('.risk-summary-text').textContent = `${nivel}: ${min}-${max} (${months} meses)`;
        };

        node.querySelector('.risk-id').value = rule.id || '';
        node.querySelector('.risk-level').value = rule.nivel || 'bajo';
        node.querySelector('.risk-min').value = rule.min_score ?? 0;
        node.querySelector('.risk-max').value = rule.max_score ?? 0;
        node.querySelector('.risk-months').value = rule.followup_months ?? 6;
        node.querySelector('.risk-order').value = rule.orden ?? 0;
        node.querySelector('.risk-level').addEventListener('change', updateRiskSummary);
        node.querySelector('.risk-min').addEventListener('input', updateRiskSummary);
        node.querySelector('.risk-max').addEventListener('input', updateRiskSummary);
        node.querySelector('.risk-months').addEventListener('input', updateRiskSummary);
        updateRiskSummary();

        node.querySelector('.remove-risk').addEventListener('click', () => node.remove());

        return node;
    }

    function buildRequirement(item = {}) {
        const mKey = item.key || key('m');
        const node = requirementTpl.content.firstElementChild.cloneNode(true);
        applyRequirementNames(node, mKey);

        const updateRequirementSummary = () => {
            const name = (node.querySelector('.requirement-name').value || '').trim();
            const active = node.querySelector('.requirement-active').checked ? 'activo' : 'inactivo';
            node.querySelector('.requirement-summary-text').textContent = `${name || 'Requerimiento sin nombre'} (${active})`;
        };

        node.querySelector('.requirement-id').value = item.id || '';
        node.querySelector('.requirement-name').value = item.nombre || '';
        node.querySelector('.requirement-order').value = item.orden ?? 0;
        node.querySelector('.requirement-active').checked = item.activo ?? true;
        node.querySelector('.requirement-name').addEventListener('input', updateRequirementSummary);
        node.querySelector('.requirement-active').addEventListener('change', updateRequirementSummary);
        updateRequirementSummary();

        node.querySelector('.remove-requirement').addEventListener('click', () => node.remove());

        return node;
    }

    document.getElementById('add-section').addEventListener('click', () => {
        sectionsContainer.appendChild(buildSection({}));
    });

    document.getElementById('add-risk-rule').addEventListener('click', () => {
        riskContainer.appendChild(buildRiskRule({}));
    });

    document.getElementById('add-requirement').addEventListener('click', () => {
        requirementsContainer.appendChild(buildRequirement({}));
    });

    (sections || []).forEach((section) => sectionsContainer.appendChild(buildSection(section)));
    (riskRules || []).forEach((rule) => riskContainer.appendChild(buildRiskRule(rule)));
    (requirements || []).forEach((item) => requirementsContainer.appendChild(buildRequirement(item)));
})();
</script>
@endpush
@endsection
