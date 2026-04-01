<a href="#" class="btn btn-sm btn-primary encuesta-envio-send"
   data-entry-id="{{ $entry->getKey() }}"
   data-action="{{ backpack_url('encuesta-envio/' . $entry->getKey() . '/procesar') }}">
    <i class="la la-paper-plane"></i> Enviar
</a>
