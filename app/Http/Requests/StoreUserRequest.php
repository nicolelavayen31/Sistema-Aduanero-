<?php

namespace App\Http\Requests;

use App\Rules\NoEmoji;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', new NoEmoji],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email', new NoEmoji],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'string', Rule::in(['admin', 'operador', 'auditor'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'El correo electrónico ya está registrado en el sistema.',
            'password.required' => 'La contraseña es obligatoria.',
            'role.in' => 'El rol seleccionado no es válido (debe ser admin, operador o auditor).',
        ];
    }
}
