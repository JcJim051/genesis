@if ($entry->acta_pdf_path)
    <a class="btn btn-sm btn-link" href="{{ backpack_url('reincorporacion/' . $entry->id . '/acta-pdf') }}" target="_blank">
        <i class="la la-file-pdf"></i> Acta
    </a>
@else
    <a class="btn btn-sm btn-link" href="{{ backpack_url('reincorporacion/' . $entry->id . '/acta-pdf') }}" target="_blank">
        <i class="la la-file-pdf"></i> Generar Acta
    </a>
@endif
