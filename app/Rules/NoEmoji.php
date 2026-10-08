<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoEmoji implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match('/[\p{Extended_Pictographic}\p{Regional_Indicator}\x{FE0F}\x{200D}\x{20E3}]/u', $value)) {
            $label = [
                'nombres' => 'El nombre',
                'apellidos' => 'El apellido',
                'cedula' => 'La cédula',
                'telefono' => 'El teléfono',
                'email' => 'El correo electrónico',
                'direccion' => 'La dirección',
                'numero_declaracion' => 'El número de declaración',
                'valor_mercancia' => 'El valor de mercancía',
                'monto' => 'El monto',
            ][$attribute] ?? 'Este campo';

            $fail($label.' no permite emojis.');
        }
    }
}
