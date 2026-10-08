<?php

namespace App\Models;

use App\Traits\HasAuditAndSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasAuditAndSoftDeletes;

    protected $fillable = [
        'declaracion_id',
        'monto',
        'metodo_pago',
        'estado',
        'fecha_pago',
        'created_by',
        'updated_by',
    ];

    public function declaracion(): BelongsTo
    {
        return $this->belongsTo(Declaracion::class);
    }
}