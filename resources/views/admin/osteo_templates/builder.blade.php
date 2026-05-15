@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card p-4">
            <h4 class="mb-3">{{ $template->exists ? 'Editar' : 'Nueva' }} Plantilla Osteomuscular</h4>

            <form method="POST" action="{{ $template->exists ? backpack_url('osteo-template/' . $template->id . '/builder') : backpack_url('osteo-template/builder') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Empresa(s)</label>
                        <select name="cliente_ids[]" class="form-control" multiple required size="6">
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected(in_array((int) $cliente->id, array_map('intval', old('cliente_ids', $selectedClienteIds ?? [])), true))>
                                    {{ $cliente->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre público</label>
                                <input class="form-control" name="nombre_publico" value="{{ old('nombre_publico', $template->nombre_publico) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Código</label>
                                <input class="form-control" name="codigo" value="{{ old('codigo', $template->codigo) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Segmento</label>
                                <input class="form-control" name="segmento" value="{{ old('segmento', $template->segmento) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="activo" value="1" @checked(old('activo', $template->activo ?? true))>
                                    <span class="form-check-label">Plantilla activa</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="sections-root">
                    @php $oldSections = old('sections', $sections ?? []); @endphp
                    @foreach($oldSections as $si => $section)
                        <div class="card border p-3 mb-3 section-item">
                            <input type="hidden" name="sections[{{ $si }}][id]" value="{{ $section['id'] ?? '' }}">
                            <div class="row g-2 mb-2">
                                <div class="col-md-7"><input class="form-control" name="sections[{{ $si }}][titulo]" value="{{ $section['titulo'] ?? '' }}" placeholder="Título sección"></div>
                                <div class="col-md-2"><input class="form-control" type="number" name="sections[{{ $si }}][orden]" value="{{ $section['orden'] ?? 0 }}" placeholder="Orden"></div>
                                <div class="col-md-3 text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-section">Quitar sección</button></div>
                            </div>
                            <div class="fields-root">
                                @foreach(($section['fields'] ?? []) as $fi => $field)
                                    <div class="row g-2 mb-2 field-item">
                                        <input type="hidden" name="sections[{{ $si }}][fields][{{ $fi }}][id]" value="{{ $field['id'] ?? '' }}">
                                        <div class="col-md-3"><input class="form-control" name="sections[{{ $si }}][fields][{{ $fi }}][label]" value="{{ $field['label'] ?? '' }}" placeholder="Etiqueta"></div>
                                        <div class="col-md-2"><input class="form-control" name="sections[{{ $si }}][fields][{{ $fi }}][key_name]" value="{{ $field['key_name'] ?? '' }}" placeholder="key_name"></div>
                                        <div class="col-md-2">
                                            <select class="form-control" name="sections[{{ $si }}][fields][{{ $fi }}][tipo]">
                                                @foreach(['text','textarea','select','laterality_pair','plus_minus_pair','pain_scale_1_10'] as $tipo)
                                                    <option value="{{ $tipo }}" @selected(($field['tipo'] ?? 'text') === $tipo)>{{ $tipo }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2"><input class="form-control" type="number" name="sections[{{ $si }}][fields][{{ $fi }}][orden]" value="{{ $field['orden'] ?? 0 }}" placeholder="Orden"></div>
                                        <div class="col-md-2">
                                            <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="sections[{{ $si }}][fields][{{ $fi }}][required]" value="1" @checked((bool)($field['required'] ?? false))> Requerido</label>
                                        </div>
                                        <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-field">X</button></div>
                                        <div class="col-md-6"><textarea class="form-control" rows="2" name="sections[{{ $si }}][fields][{{ $fi }}][options_json]" placeholder="Opciones (una por línea)">{{ $field['options_json'] ?? '' }}</textarea></div>
                                        <div class="col-md-6"><textarea class="form-control" rows="2" name="sections[{{ $si }}][fields][{{ $fi }}][meta_json]" placeholder='Meta JSON: {"choices":["Normal","Disminuida","Ausente"]}'>{{ $field['meta_json'] ?? '' }}</textarea></div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary add-field">+ Campo</button>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex gap-2">
                    <button type="button" id="add-section" class="btn btn-outline-primary">+ Sección</button>
                    <button type="submit" class="btn btn-primary">Guardar plantilla</button>
                    <a href="{{ backpack_url('osteo-template') }}" class="btn btn-link">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('after_scripts')
<script>
(() => {
    const root = document.getElementById('sections-root');
    const addSectionBtn = document.getElementById('add-section');

    const fieldRow = (si, fi) => `
        <div class="row g-2 mb-2 field-item">
            <input type="hidden" name="sections[${si}][fields][${fi}][id]" value="">
            <div class="col-md-3"><input class="form-control" name="sections[${si}][fields][${fi}][label]" placeholder="Etiqueta"></div>
            <div class="col-md-2"><input class="form-control" name="sections[${si}][fields][${fi}][key_name]" placeholder="key_name"></div>
            <div class="col-md-2">
                <select class="form-control" name="sections[${si}][fields][${fi}][tipo]">
                    <option value="text">text</option><option value="textarea">textarea</option><option value="select">select</option>
                    <option value="laterality_pair">laterality_pair</option><option value="plus_minus_pair">plus_minus_pair</option><option value="pain_scale_1_10">pain_scale_1_10</option>
                </select>
            </div>
            <div class="col-md-2"><input class="form-control" type="number" name="sections[${si}][fields][${fi}][orden]" value="0" placeholder="Orden"></div>
            <div class="col-md-2"><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="sections[${si}][fields][${fi}][required]" value="1"> Requerido</label></div>
            <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-field">X</button></div>
            <div class="col-md-6"><textarea class="form-control" rows="2" name="sections[${si}][fields][${fi}][options_json]" placeholder="Opciones (una por línea)"></textarea></div>
            <div class="col-md-6"><textarea class="form-control" rows="2" name="sections[${si}][fields][${fi}][meta_json]" placeholder='Meta JSON: {"choices":["Normal","Disminuida","Ausente"]}'></textarea></div>
        </div>`;

    const sectionCard = (si) => `
        <div class="card border p-3 mb-3 section-item">
            <input type="hidden" name="sections[${si}][id]" value="">
            <div class="row g-2 mb-2">
                <div class="col-md-7"><input class="form-control" name="sections[${si}][titulo]" placeholder="Título sección"></div>
                <div class="col-md-2"><input class="form-control" type="number" name="sections[${si}][orden]" value="0" placeholder="Orden"></div>
                <div class="col-md-3 text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-section">Quitar sección</button></div>
            </div>
            <div class="fields-root"></div>
            <button type="button" class="btn btn-sm btn-outline-primary add-field">+ Campo</button>
        </div>`;

    const refreshActions = () => {
        root.querySelectorAll('.remove-section').forEach(btn => btn.onclick = () => btn.closest('.section-item').remove());
        root.querySelectorAll('.remove-field').forEach(btn => btn.onclick = () => btn.closest('.field-item').remove());
        root.querySelectorAll('.add-field').forEach(btn => {
            btn.onclick = () => {
                const section = btn.closest('.section-item');
                const si = Array.from(root.querySelectorAll('.section-item')).indexOf(section);
                const fieldsRoot = section.querySelector('.fields-root');
                const fi = fieldsRoot.querySelectorAll('.field-item').length;
                fieldsRoot.insertAdjacentHTML('beforeend', fieldRow(si, fi));
                refreshActions();
            };
        });
    };

    addSectionBtn?.addEventListener('click', () => {
        const si = root.querySelectorAll('.section-item').length;
        root.insertAdjacentHTML('beforeend', sectionCard(si));
        refreshActions();
    });

    refreshActions();
})();
</script>
@endpush

