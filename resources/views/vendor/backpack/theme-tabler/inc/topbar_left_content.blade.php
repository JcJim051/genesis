@if(backpack_user() && backpack_user()->hasRole('Administrador'))
    <x-backpack::menu-dropdown title="Autenticación" icon="la la-group">
        <x-backpack::menu-dropdown-item title="Usuarios" icon="la la-user" :link="backpack_url('user')" />
        <x-backpack::menu-dropdown-item title="Roles" icon="la la-group" :link="backpack_url('role')" />
        <x-backpack::menu-dropdown-item title="Permisos" icon="la la-key" :link="backpack_url('permission')" />
    </x-backpack::menu-dropdown>
@endif

@if(backpack_user() && backpack_user()->hasAnyRole(['Administrador','Coordinador general','Coordinador de planta','Asesor externo general','Asesor externo planta']))
    <x-backpack::menu-dropdown title="Administración" icon="la la-tools">
        <x-backpack::menu-dropdown-item title="Empresas" icon="la la-building" :link="backpack_url('cliente')" />
        <x-backpack::menu-dropdown-item title="Plantas" icon="la la-industry" :link="backpack_url('sucursal')" />
        <x-backpack::menu-dropdown-item title="Personas" icon="la la-user-check" :link="backpack_url('empleado')" />
        @if(backpack_user()->hasRole('Administrador'))
            <x-backpack::menu-dropdown-item title="Programas" icon="la la-list" :link="backpack_url('programa')" />
            <x-backpack::menu-dropdown-item title="CIE10" icon="la la-book" :link="backpack_url('cie10')" />
            <x-backpack::menu-dropdown-item title="Mapeo Diagnóstico" icon="la la-link" :link="backpack_url('diagnostico-programa')" />
        @endif
    </x-backpack::menu-dropdown>
@endif
