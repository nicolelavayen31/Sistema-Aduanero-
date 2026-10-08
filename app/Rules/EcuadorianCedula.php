<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EcuadorianCedula implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/\A[0-9]{10}\z/', $value)) {
            $fail('La cédula debe contener exactamente 10 números.');

            return;
        }

        $province = (int) substr($value, 0, 2);
        $thirdDigit = (int) $value[2];

        if (!(($province >= 1 && $province <= 24) || $province === 30) || $thirdDigit > 5) {
            $fail('La cédula ecuatoriana tiene un código de provincia o tipo de identificación inválido.');

            return;
        }

        $sum = 0;

        for ($index = 0; $index < 9; $index++) {
            $digit = (int) $value[$index];
            $product = $digit * ($index % 2 === 0 ? 2 : 1);
            $sum += $product > 9 ? $product - 9 : $product;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        if ($checkDigit !== (int) $value[9]) {
            $fail('La cédula ecuatoriana no supera la validación del dígito verificador.');
        }
    }
}
