<?php

namespace App\Http\Controllers;

class CatalogoController extends Controller
{
    public function index()
    {
        return response()->json([
            'tipos_declaracion' => $this->options(config('aduanas.tipos_declaracion')),
            'metodos_pago' => $this->options(config('aduanas.metodos_pago')),
            'estados_pago' => $this->options(config('aduanas.estados_pago')),
        ]);
    }

    private function options(array $catalog): array
    {
        $options = [];

        foreach ($catalog as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $options;
    }
}
