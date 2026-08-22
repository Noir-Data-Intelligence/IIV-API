<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $config->get('ui.title') ?? config('app.name') . ' - API Docs' }}</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>body { margin: 0; }</style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
    {{-- Sanctum SPA é cookie-based, não bearer (ver descrição da API) — o
         "Try it out" do Swagger UI precisa do XSRF-TOKEN da cookie reenviado
         como cabeçalho em cada pedido, tal como o cliente React real faz
         (Front-end/src/lib/http.ts), e das cookies incluídas no fetch. --}}
    const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
    const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
    const getCookieValue = (key) => {
        const cookie = document.cookie.split(';').find((c) => c.trim().startsWith(key + '='));
        return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : undefined;
    };

    window.ui = SwaggerUIBundle({
        spec: @json($spec),
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [SwaggerUIBundle.presets.apis],
        layout: 'BaseLayout',
        withCredentials: true,
        requestInterceptor: (req) => {
            const token = getCookieValue(CSRF_TOKEN_COOKIE_KEY);
            if (token) req.headers[CSRF_TOKEN_HEADER_KEY] = token;
            return req;
        },
    });
</script>
</body>
</html>
