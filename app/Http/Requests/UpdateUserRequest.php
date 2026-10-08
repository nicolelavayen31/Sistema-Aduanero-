<?php

namespace App\Http\Requests;

use App\Rules\NoEmoji;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100', new NoEmoji],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId), new NoEmoji],
            'password' => ['nullable', 'string', Password::min(8)->letters()->numbers()],
            'role' => ['sometimes', 'required', 'string', Rule::in(['admin', 'operador', 'auditor'])],
        ];
    }
}
