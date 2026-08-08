<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardPrefResource;
use App\Models\DashboardPref;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Recurso singular por utilizador (/dashboard-prefs/me). kpis/charts vazios
 * significam "usar as predefinicoes do papel" — a pagina aplica essa regra,
 * o backend so guarda o que o utilizador escolheu explicitamente.
 */
class DashboardPrefController extends Controller
{
    public function show(Request $request): DashboardPrefResource
    {
        $pref = $this->forUser($request);

        return new DashboardPrefResource($pref);
    }

    public function update(Request $request): DashboardPrefResource
    {
        $data = $request->validate([
            'kpis' => ['sometimes', 'array'],
            'charts' => ['sometimes', 'array'],
            'thresholds' => ['sometimes', 'array'],
        ]);

        $pref = $this->forUser($request);
        $pref->update([...$data, 'updated_at' => now()]);

        return new DashboardPrefResource($pref);
    }

    private function forUser(Request $request): DashboardPref
    {
        $pref = DashboardPref::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['id' => (string) Str::uuid(), 'kpis' => [], 'charts' => [], 'thresholds' => [], 'updated_at' => now()],
        );

        // Recurso singular (GET/PUT), nunca deve responder 201 mesmo quando
        // o firstOrCreate cria a linha agora — evita o 201 automatico que o
        // Laravel aplica a JsonResource sobre um model `wasRecentlyCreated`.
        $pref->wasRecentlyCreated = false;

        return $pref;
    }
}
