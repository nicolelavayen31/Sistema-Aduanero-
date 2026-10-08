<?php

namespace App\Services;

use App\Models\Declaracion;
use Illuminate\Support\Facades\DB;

class NumeroDeclaracionGenerator
{
    public function generateSiguiente(): string
    {
        return DB::transaction(function () {
            $maxId = Declaracion::withTrashed()->max('id') ?? 0;
            $siguienteId = $maxId + 1;

            $candidato = sprintf('DA-%03d', $siguienteId);

            while (Declaracion::withTrashed()->where('numero_declaracion', $candidato)->exists()) {
                $siguienteId++;
                $candidato = sprintf('DA-%03d', $siguienteId);
            }

            return $candidato;
        });
    }
}
