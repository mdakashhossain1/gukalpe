<?php

namespace App\Models;

use App\Support\AdminRoles;
use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    protected $fillable = ['name', 'username', 'password', 'role', 'is_active'];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function roleLabel(): string
    {
        return AdminRoles::label($this->role);
    }
}
