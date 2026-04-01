@php
    $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
    $field['wrapper']['data-init-function'] = $field['wrapper']['data-init-function'] ?? 'bpFieldInitUploadElement';
    $field['wrapper']['data-field-name'] = $field['wrapper']['data-field-name'] ?? $field['name'];

    if(isset($field['parentFieldName'])) {
      if(!empty(old())) {
        $field['value'] = Arr::get(old(), square_brackets_to_dots($field['name'])) ??
                          Arr::get(old(), '_order_'.square_brackets_to_dots($field['name'])) ??
                          Arr::get(old(), '_clear_'.square_brackets_to_dots($field['name']));
      }
    }
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    @if (!empty($field['value']))
    <div class="existing-file">
        @if (isset($field['disk']))
            @php
                $path = Arr::get($field, 'prefix', '') . $field['value'];
                $url = request()->getSchemeAndHttpHost().'/storage/'.ltrim($path, '/');
            @endphp
            <a target="_blank" href="{{ $url }}">
        @else
            <a target="_blank" href="{{ (asset(Arr::get($field, 'prefix', '').$field['value'])) }}">
        @endif
            {{ $field['value'] }}
        </a>
        <a href="#" class="file_clear_button btn btn-light btn-sm float-right" title="Clear file" data-filename="{{ $field['value'] }}"><i class="la la-remove"></i></a>
        <div class="clearfix"></div>
    </div>
    @endif

    <div class="backstrap-file {{ isset($field['value']) && $field['value']!=null?'d-none':'' }}">
        <input
            type="file"
            name="{{ $field['name'] }}"
            data-filename="{{ $field['value'] ?? '' }}"
            @include('crud::fields.inc.attributes', ['default_class' => 'file_input backstrap-file-input'])
        >
        <label class="backstrap-file-label" for="customFile"></label>
    </div>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
    <style type="text/css">
        .existing-file {
            border: 1px solid rgba(0,40,100,.12);
            border-radius: 5px;
            padding-left: 10px;
            vertical-align: middle;
        }
        .existing-file a {
            padding-top: 5px;
            display: inline-block;
            font-size: 0.9em;
        }
        .backstrap-file {
          position: relative;
          display: inline-block;
          width: 100%;
          height: calc(1.5em + 0.75rem + 2px);
          margin-bottom: 0;
        }

        .backstrap-file-input {
          position: relative;
          z-index: 2;
          width: 100%;
          height: calc(1.5em + 0.75rem + 2px);
          margin: 0;
          opacity: 0;
        }

        .backstrap-file-input:focus ~ .backstrap-file-label {
          border-color: #acc5ea;
          box-shadow: 0 0 0 0rem rgba(70, 127, 208, 0.25);
        }

        .backstrap-file-input:disabled ~ .backstrap-file-label {
          background-color: #e4e7ea;
        }

        .backstrap-file-input:lang(en) ~ .backstrap-file-label::after {
          content: "Browse";
        }

        .backstrap-file-input ~ .backstrap-file-label[data-browse]::after {
          content: attr(data-browse);
        }

        .backstrap-file-label {
          position: absolute;
          top: 0;
          right: 0;
          left: 0;
          z-index: 1;
          height: calc(1.5em + 0.75rem + 2px);
          padding: 0.375rem 0.75rem;
          font-weight: 400;
          line-height: 1.5;
          border: 1px solid rgba(0,40,100,.12);
          border-radius: 0.375rem;
          background: #fff;
        }

        .backstrap-file-label::after {
          background: #f8f9fa;
          border-left: 1px solid rgba(0,40,100,.12);
        }
    </style>
@endpush

@push('crud_fields_scripts')
    <script>
      function bpFieldInitUploadElement(element) {
        var fieldName = element.attr('data-field-name');
        var fileInput = element.find('input[type=file]');
        var inputName = fileInput.attr('name');
        var originalFileInput = element.find('input[type=file]');
        var originalData = originalFileInput.data();

        element.on('click', '.file_clear_button', function(e) {
          e.preventDefault();
          fileInput.data('filename', '');
          fileInput.val('');
          fileInput.siblings('.order_uploads').remove();
          fileInput.siblings('.clear_file').remove();
          fileInput.after('<input type=\"hidden\" class=\"clear_file\" name=\"_clear_'+inputName+'\" value=\"'+$(this).data('filename')+'\">');
          element.find('.existing-file').addClass('d-none');
          element.find('.backstrap-file').removeClass('d-none');
        });
      }
    </script>
@endpush
