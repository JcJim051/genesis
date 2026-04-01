{{-- This file is used for menu items by any Backpack v7 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

@if(backpack_user() && backpack_user()->hasAnyRole(['Administrador','Coordinador general','Coordinador de planta','Asesor externo general','Asesor externo planta']))
    <x-backpack::menu-item title="Personas" icon="la la-user-check" :link="backpack_url('empleado')" />
@endif

@php
    // Autenticación y Administración se muestran en el menú superior (izquierda).
@endphp

@if(backpack_user() && backpack_user()->hasAnyRole(['Administrador','Coordinador general','Coordinador de planta','Asesor externo general','Asesor externo planta']))
    @php
        $programas = \App\Models\Programa::whereIn('slug', [
            'osteomuscular', 'visual', 'psicosocial', 'auditivo', 'cardiovascular', 'reincorporacion',
        ])->get()->keyBy('slug');
    @endphp
    <x-backpack::menu-dropdown title="Programas" icon="la la-list-alt">
        <x-backpack::menu-dropdown-item
            title="Casos por Evaluar"
            icon="la la-exclamation-circle"
            :link="backpack_url('programa-caso') . '?estado=No%20evaluado'"
        />
        <x-backpack::menu-dropdown-item
            title="Osteomuscular"
            icon="la la-dumbbell"
            :link="backpack_url('programa-caso') . '?programa_id=' . optional($programas->get('osteomuscular'))->id"
        />
        <x-backpack::menu-dropdown-item
            title="Visual"
            icon="la la-eye"
            :link="backpack_url('programa-caso') . '?programa_id=' . optional($programas->get('visual'))->id"
        />
        <x-backpack::menu-dropdown-item
            title="Psicosocial"
            icon="la la-brain"
            :link="backpack_url('programa-caso') . '?programa_id=' . optional($programas->get('psicosocial'))->id"
        />
        <x-backpack::menu-dropdown-item
            title="Auditivo"
            icon="la la-deaf"
            :link="backpack_url('programa-caso') . '?programa_id=' . optional($programas->get('auditivo'))->id"
        />
        <x-backpack::menu-dropdown-item
            title="Cardiovascular"
            icon="la la-heartbeat"
            :link="backpack_url('programa-caso') . '?programa_id=' . optional($programas->get('cardiovascular'))->id"
        />
        <x-backpack::menu-dropdown-item
            title="Reincorporación"
            icon="la la-clipboard-check"
            :link="backpack_url('reincorporacion')"
        />
    </x-backpack::menu-dropdown>
@endif

@if(backpack_user() && backpack_user()->hasAnyRole(['Administrador','Coordinador general']))
    <x-backpack::menu-dropdown title="Historiales" icon="la la-history">
        <x-backpack::menu-dropdown-item title="Cargos" icon="la la-id-badge" :link="backpack_url('empleado-cargo')" />
        <x-backpack::menu-dropdown-item title="Áreas" icon="la la-sitemap" :link="backpack_url('empleado-area')" />
    </x-backpack::menu-dropdown>
@endif

@if(backpack_user() && backpack_user()->hasAnyRole(['Administrador','Coordinador general','Coordinador de planta']))
    <x-backpack::menu-item title="Incapacidades" icon="la la-notes-medical" :link="backpack_url('incapacidad')" />
    <x-backpack::menu-item title="Exámenes periódicos" icon="la la-stethoscope" :link="backpack_url('examen')" />

    <x-backpack::menu-dropdown title="Pausas Activas" icon="la la-play-circle">
        @if(backpack_user()->hasRole('Administrador'))
            <x-backpack::menu-dropdown-item title="Pausas" icon="la la-list" :link="backpack_url('pausa')" />
        @endif
        @if(backpack_user()->hasAnyRole(['Administrador','Coordinador general']))
            <x-backpack::menu-dropdown-item title="Envíos" icon="la la-paper-plane" :link="backpack_url('pausa-envio')" />
        @endif
        @if(backpack_user()->hasAnyRole(['Administrador','Coordinador general','Coordinador de planta']))
            <x-backpack::menu-dropdown-item title="Participaciones" icon="la la-user-check" :link="backpack_url('pausa-participacion')" />
        @endif
    </x-backpack::menu-dropdown>

    <x-backpack::menu-dropdown title="Encuestas" icon="la la-edit">
        @if(backpack_user()->hasRole('Administrador'))
            <x-backpack::menu-dropdown-item title="Encuestas" icon="la la-edit" :link="backpack_url('encuesta')" />
        @endif
        @if(backpack_user()->hasAnyRole(['Administrador','Coordinador general']))
            <x-backpack::menu-dropdown-item title="Envíos Encuestas" icon="la la-paper-plane" :link="backpack_url('encuesta-envio')" />
        @endif
        @if(backpack_user()->hasAnyRole(['Administrador','Coordinador general','Coordinador de planta']))
            <x-backpack::menu-dropdown-item title="Participaciones" icon="la la-user-check" :link="backpack_url('encuesta-participacion')" />
        @endif
        @if(backpack_user()->hasAnyRole(['Administrador','Coordinador de planta']))
            <x-backpack::menu-dropdown-item title="Alertas Encuestas" icon="la la-bell" :link="backpack_url('encuesta-alerta')" />
        @endif
    </x-backpack::menu-dropdown>

@endif
