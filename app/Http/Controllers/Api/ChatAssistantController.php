<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Substitui a Edge Function Supabase `chat-assistant` (que dependia do
 * Lovable AI Gateway — ver supabase/functions/chat-assistant/index.ts no
 * Front-end, mantida so como referencia). Fornecedor de LLM configuravel
 * via env (`CHAT_LLM_PROVIDER`), NUNCA hardcoded — decisao D11 ainda
 * pendente de confirmacao do cliente (ver SIG-IIV-MEMORIA-PROJETO.md
 * seccao 16). Sem chave configurada, devolve um aviso informativo no MESMO
 * formato SSE, para o `ChatWidget.tsx` do frontend nunca quebrar.
 *
 * Formato SSE (OpenAI-like), identico ao que o mock
 * (src/mocks/handlers/chatAssistant.ts) ja implementa e o ChatWidget.tsx
 * ja sabe interpretar:
 *   data: {"choices":[{"delta":{"content":"..."}}]}\n\n
 *   ...
 *   data: [DONE]\n\n
 */
class ChatAssistantController extends Controller
{
    public function stream(Request $request): StreamedResponse
    {
        $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $provider = config('services.chat.provider');

        return response()->stream(function () use ($provider) {
            $chunks = $provider
                ? $this->driverFor($provider)->reply()
                : [__('chat.provider_not_configured')];

            foreach ($chunks as $chunk) {
                echo 'data: '.json_encode(['choices' => [['delta' => ['content' => $chunk]]]])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            echo "data: [DONE]\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Ponto de extensao para quando o provedor (D11) for confirmado:
     * implementar um driver por provedor (ex: OpenAiChatDriver,
     * AnthropicChatDriver) atras desta mesma interface — nenhum outro
     * ficheiro muda.
     */
    private function driverFor(string $provider): object
    {
        return new class
        {
            /** @return iterable<string> */
            public function reply(): iterable
            {
                yield __('chat.provider_not_configured');
            }
        };
    }
}
