{{-- Campo auxiliar para buscar persona por cédula y autollenar datos --}}
@include('crud::fields.inc.wrapper_start')
    <label>Buscar persona por cédula</label>
    <div class="input-group mb-2">
        <input type="text" class="form-control" id="buscar-cedula-input" placeholder="Ingrese cédula" />
        <button class="btn btn-primary" type="button" id="buscar-cedula-btn">Buscar</button>
    </div>
    <small class="text-muted">Al buscar, se diligencian automáticamente los datos personales del acta.</small>
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
(function() {
    function fillFields(data) {
        var map = {
            'nombre_completo': data.nombre || '',
            'identificacion': data.cedula || '',
            'fecha_nacimiento': data.fecha_nacimiento || '',
            'edad': data.edad || '',
            'genero': data.genero || '',
            'lateralidad': data.lateralidad || '',
            'eps': data.eps || '',
            'arl': data.arl || '',
            'fondo_pensiones': data.fondo_pensiones || '',
            'telefono_contacto': data.telefono || '',
            'correo_electronico': data.correo_electronico || '',
            'direccion_residencia': data.direccion || '',
            'fecha_ingreso_empresa': data.fecha_ingreso || '',
            'cargo_actual': data.cargo_actual || '',
            'antiguedad_cargo': data.antiguedad_cargo || '',
            'area_reintegra': data.area_actual || '',
            'asistente_trabajador_nombre': data.nombre || '',
            'asistente_trabajador_cedula': data.cedula || '',
            'asistente_trabajador_cargo': data.cargo_actual || ''
        };

        Object.keys(map).forEach(function(name) {
            var el = document.querySelector('[name="' + name + '"]');
            if (!el) return;
            el.value = map[name];
            el.dispatchEvent(new Event('change'));
        });
    }

    function lookup(params) {
        var url = '{{ backpack_url('empleado/lookup') }}' + '?' + new URLSearchParams(params).toString();
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) {
                if (!r.ok) throw r;
                return r.json();
            });
    }

    var btn = document.getElementById('buscar-cedula-btn');
    if (btn) {
        btn.addEventListener('click', function() {
            var cedula = document.getElementById('buscar-cedula-input').value || '';
            if (!cedula) return;
            lookup({ cedula: cedula }).then(function(data) {
                fillFields(data);
                var hidden = document.querySelector('[name="empleado_id"]');
                if (hidden) {
                    hidden.value = data.id;
                    hidden.dispatchEvent(new Event('change'));
                }
            }).catch(function(){});
        });
    }

    var empleadoHidden = document.querySelector('[name="empleado_id"]');
    if (empleadoHidden && empleadoHidden.value) {
        lookup({ id: empleadoHidden.value }).then(fillFields).catch(function(){});
    }
})();
</script>
@endpush
