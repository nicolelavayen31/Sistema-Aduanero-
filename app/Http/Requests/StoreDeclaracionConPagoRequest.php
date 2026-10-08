<?php

namespace App\Http\Requests;

use App\Rules\NoEmoji;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeclaracionConPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where('activo', true),
            ],
            'numero_declaracion' => [
                'bail',
                'required',
                'string',
                'max:20',
                new NoEmoji(),
                'regex:/\ADA-[0-9]{3}\z/',
                Rule::unique('declaraciones', 'numero_declaracion'),
            ],
            'tipo' => ['required', 'string', Rule::in(array_keys(config('aduanas.tipos_declaracion')))],
            'valor_mercancia' => [
                'bail', 'required', new NoEmoji(), 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99',
            ],
            'monto' => [
                'bail', 'required', new NoEmoji(), 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99',
            ],
            'metodo_pago' => ['required', 'string', Rule::in(array_keys(config('aduanas.metodos_pago')))],
            'estado' => ['sometimes', 'required', 'string', Rule::in(array_keys(config('aduanas.estados_pago')))],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Selecciona un cliente.',
            'cliente_id.integer' => 'El cliente seleccionado no es válido.',
            'cliente_id.exists' => 'El cliente no existe o está inactivo.',
            'numero_declaracion.required' => 'El número de declaración es obligatorio.',
            'numero_declaracion.regex' => 'Usa el formato DA-001: prefijo DA-, guion y 3 números, sin espacios.',
            'numero_declaracion.unique' => 'Este número de declaración ya está registrado.',
            'tipo.required' => 'Selecciona un tipo de declaración.',
            'tipo.in' => 'El tipo de declaración seleccionado no está permitido.',
            'valor_mercancia.required' => 'El valor de mercancía es obligatorio.',
            'valor_mercancia.numeric' => 'El valor de mercancía debe ser numérico.',
            'valor_mercancia.decimal' => 'El valor de mercancía permite máximo 2 decimales.',
            'valor_mercancia.gt' => 'El valor de mercancía debe ser mayor que 0.',
            'valor_mercancia.max' => 'El valor de mercancía supera el máximo permitido.',
            'monto.required' => 'El monto del pago es obligatorio.',
            'monto.numeric' => 'El monto del pago debe ser numérico.',
            'monto.decimal' => 'El monto del pago permite máximo 2 decimales.',
            'monto.gt' => 'El monto del pago debe ser mayor que 0.',
            'monto.max' => 'El monto del pago supera el máximo permitido.',
            'metodo_pago.required' => 'Selecciona un método de pago.',
            'metodo_pago.in' => 'El método de pago seleccionado no está permitido.',
            'estado.required' => 'Selecciona un estado de pago.',
            'estado.in' => 'El estado de pago seleccionado no está permitido.',
        ];
    }
}
