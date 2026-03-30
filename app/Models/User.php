<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, CrudTrait;

    /** @use HasFactory<UserFactory> */

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function empresas()
    {
        return $this->belongsToMany(Cliente::class, 'cliente_user');
    }

    public function plantas()
    {
        return $this->belongsToMany(Sucursal::class, 'sucursal_user');
    }

    public function setPasswordAttribute($value)
    {
        if (! is_null($value) && $value !== '') {
            $this->attributes['password'] = $value;
        }
    }
}
