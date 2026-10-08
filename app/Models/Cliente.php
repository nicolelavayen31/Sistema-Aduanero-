<?php

namespace App\Models;

use App\Traits\HasAuditAndSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasAuditAndSoftDeletes;

    protected $fillable = [
        'nombres',
        'apellidos',
        'cedula',
        'email',
        'telefono',
        'direccion',
        'activo',
        'created_by',
        'updated_by',
    ];

    public function declaraciones(): HasMany
    {
        return $this->hasMany(Declaracion::class);
    }
}