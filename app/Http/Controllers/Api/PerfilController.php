<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePerfilRequest;
use App\Http\Resources\PerfilResource;
use Illuminate\Http\Request;

/**
 * "O meu perfil" — dados de negócio (nome/telefone/avatar) do utilizador
 * autenticado, distinto do módulo `users` genérico (gestão de outras
 * contas). Sem gate de módulo: qualquer utilizador autenticado edita o
 * próprio perfil, mesmo padrão de `PATCH /user/password`.
 */
class PerfilController extends Controller
{
    public function show(Request $request): PerfilResource
    {
        return new PerfilResource($request->user());
    }

    public function update(UpdatePerfilRequest $request): PerfilResource
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'full_name' => $data['fullName'] ?? $user->full_name,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $user->phone,
            'avatar_url' => array_key_exists('avatarUrl', $data) ? $data['avatarUrl'] : $user->avatar_url,
        ])->save();

        return new PerfilResource($user);
    }
}
