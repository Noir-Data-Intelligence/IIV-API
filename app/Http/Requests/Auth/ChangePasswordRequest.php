<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Sem isto, um pedido feito com uma sessão obtida por acesso
            // momentâneo (posto partilhado, cookie roubado) convertia-se
            // instantaneamente em posse permanente da conta — bastava um
            // único PATCH sem qualquer prova de que quem o fez é o titular
            // (auditoria de segurança, 2026-08-09).
            'currentPassword' => ['required', 'current_password'],
            'password' => ['required', Password::defaults()],
        ];
    }
}
