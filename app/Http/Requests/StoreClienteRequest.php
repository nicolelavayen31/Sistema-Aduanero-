<?php

namespace App\Http\Requests;

use App\Rules\EcuadorianCedula;
use App\Rules\NoEmoji;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['nombres', 'apellidos'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized[$field] = preg_replace('/ +/u', ' ', trim($value));
            }
        }

        $this->merge($normalized);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres' => [
                'bail', 'required', 'string', 'max:255', new NoEmoji(),
                'regex:/\A(?:\p{Latin}\p{M}*)+(?: +(?:\p{Latin}\p{M}*)+)*\z/u',
            ],
            'apellidos' => [
                'bail', 'required', 'string', 'max:255', new NoEmoji(),
                'regex:/\A(?:\p{Latin}\p{M}*)+(?: +(?:\p{Latin}\p{M}*)+)*\z/u',
            ],
            'cedula' => [
                'bail', 'required', 'string', new NoEmoji(), 'regex:/\A[0-9]{10}\z/',
                Rule::unique('clientes', 'cedula'), new EcuadorianCedula(),
            ],
            'email' => [
                'bail', 'required', 'string', 'max:255', new NoEmoji(),
                'regex:/\A[^\s@]+@[^\s@]+\.[^\s@]+\z/u', 'email:rfc', Rule::unique('clientes', 'email'),
            ],
            'telefono' => [
                'nullable', 'bail', 'string', new NoEmoji(), 'regex:/\A[0-9]{10}\z/',
            ],
            'direccion' => [
                'nullable', 'bail', 'string', 'max:255', new NoEmoji(),
                'regex:/\A[\p{Latin}\p{M}0-9 ,.-]*\z/u',
            ],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.regex' => 'El nombre solo puede contener letras y espacios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.regex' => 'El apellido solo puede contener letras y espacios.',
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.regex' => 'La cédula debe contener exactamente 10 números, sin espacios ni símbolos.',
            'cedula.unique' => 'Esta cédula ya está registrada.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.regex' => 'El correo electrónico no tiene un formato válido o contiene espacios.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'telefono.regex' => 'El teléfono solo puede contener exactamente 10 números.',
            'direccion.regex' => 'La dirección contiene caracteres no permitidos.',
            'activo.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }
}
