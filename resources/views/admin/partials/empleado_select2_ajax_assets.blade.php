<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
        min-height: 38px;
        border: 1px solid #d1d5db;
        border-radius: .375rem;
        padding-top: 4px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 6px;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function initEmpleadoSelect2Ajax(select) {
        var $select = window.jQuery ? window.jQuery(select) : null;
        if (!$select || !$select.length || !window.jQuery.fn.select2 || $select.data('select2')) {
            return $select;
        }

        $select.select2({
            width: '100%',
            allowClear: true,
            placeholder: $select.data('placeholder') || $select.attr('data-placeholder') || '',
            minimumInputLength: parseInt($select.attr('data-minimum-input-length') || '1', 10),
            language: {
                inputTooShort: function (args) {
                    var remaining = args.minimum - args.input.length;
                    return remaining === 1
                        ? 'Escribe 1 carácter más para buscar'
                        : 'Escribe ' + remaining + ' caracteres más para buscar';
                },
                searching: function () { return 'Buscando...'; },
                noResults: function () {
                    return $select.attr('data-no-results') || 'No se encontraron personas';
                },
                errorLoading: function () { return 'No se pudieron cargar los resultados'; },
                loadingMore: function () { return 'Cargando más resultados...'; }
            },
            ajax: {
                url: $select.attr('data-ajax-url'),
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term, page: params.page || 1 };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results || [],
                        pagination: { more: !!(data.pagination && data.pagination.more) }
                    };
                }
            }
        });

        $select.on('select2:select', function (e) {
            var data = e.params.data || {};
            var option = $select.find('option[value="' + data.id + '"]');
            if (option.length && data.cliente_id) {
                option.attr('data-cliente-id', data.cliente_id);
            }
        });

        return $select;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery || !jQuery.fn.select2) return;
        document.querySelectorAll('select.js-empleado-select2-ajax').forEach(function (el) {
            initEmpleadoSelect2Ajax(el);
        });
    });
</script>
