<?php

namespace App\Models;

use App\Traits\HasAuditAndSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Declaracion extends Model
{
    use HasAuditAndSoftDeletes;

    protected $table = 'declaraciones';

    protected $fillable = [
        'cliente_id',
        'numero_declaracion',
        'tipo',
        'valor_mercancia',
        'estado',
        'fecha_declaracion',
        'created_by',
        'updated_by',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function estadoVisible(): string
    {
        if ($this->estado === 'pagado') {
            return 'pagado';
        }

        if ($this->relationLoaded('pagos')) {
            $totalPagado = $this->pagos
                ->where('estado', 'pagado')
                ->sum(fn (Pago $pago): float => (float) $pago->monto);

            if ($this->enCentavos($totalPagado) >= $this->enCentavos((float) $this->valor_mercancia)) {
                return 'pagado';
            }
        }

        return $this->estado;
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where(function (Builder $declaraciones) {
            $declaraciones->where('declaraciones.estado', 'pagado')
                ->orWhereRaw(
                    '(SELECT COALESCE(SUM(pagos.monto), 0) FROM pagos WHERE pagos.declaracion_id = declaraciones.id AND pagos.estado = ?) >= declaraciones.valor_mercancia',
                    ['pagado']
                );
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('declaraciones.estado', 'pendiente')
            ->whereRaw(
                '(SELECT COALESCE(SUM(pagos.monto), 0) FROM pagos WHERE pagos.declaracion_id = declaraciones.id AND pagos.estado = ?) < declaraciones.valor_mercancia',
                ['pagado']
            );
    }

    private function enCentavos(float $monto): int
    {
        return (int) round($monto * 100);
    }
}