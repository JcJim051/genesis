<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Administrador',
            'Coordinador general',
            'Coordinador de planta',
            'Asesor externo general',
            'Asesor externo planta',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $admin = User::where('email', 'admin@local.test')->first();
        if ($admin) {
            $admin->assignRole('Administrador');
        }
    }
}
