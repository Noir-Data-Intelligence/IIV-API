<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Fornecedor de LLM do Assistente (chat-assistant) — decisao D11 ainda
    // pendente de confirmacao do cliente (SIG-IIV-MEMORIA-PROJETO.md seccao
    // 16). null = endpoint funcional mas devolve aviso "nao configurado" em
    // vez de falhar. Valores previstos quando decidido: 'openai'|'anthropic'.
    'chat' => [
        'provider' => env('CHAT_LLM_PROVIDER'),
        'api_key' => env('CHAT_LLM_API_KEY'),
    ],

];
