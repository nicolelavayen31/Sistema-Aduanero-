<?php

namespace App\Http\Requests;

use App\Rules\NoEmoji;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'operador') ?? false;
    }

    public function rules(): array
    {
        $catalogos = config('aduanas');
        $metodosPermitidos = array_keys($catalogos['metodos_pago'] ?? []);
        $estadosPermitidos = array_keys($catalogos['estados_pago'] ?? []);

        return [
            'monto' => ['required', 'numeric', 'gt:0', 'regex:/^[0-9]+(?:\.[0-9]{1,2})?$/', new NoEmoji],
            'metodo_pago' => ['required', 'string', Rule::in($metodosPermitidos)],
            'estado' => ['sometimes', 'required', 'string', Rule::in($estadosPermitidos)],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'El monto del pago es obligatorio.',
            'monto.gt' => 'El monto del pago debe ser mayor que 0.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',
            'estado.in' => 'El estado de pago seleccionado no es válido.',
        ];
    }
}
