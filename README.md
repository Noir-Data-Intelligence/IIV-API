# SIG-IIV — Backend

API REST do Sistema Integrado de Gestão do Instituto de Investigação Veterinária (IIV/MINAGRIF, Angola). Laravel 12 (PHP 8.2+), API-only, consumido pelo frontend em `../Front-end`.

Contexto completo do projecto (requisitos, regras de negócio, arquitectura, decisões em aberto): `../SIG-IIV-MEMORIA-PROJETO.md`.

## Stack

- Laravel 12, PHP 8.2+
- MySQL/MariaDB (servidor externo já provisionado — SQLite em uso local até o driver `pdo_mysql`/ligação a esse servidor existirem nesta máquina, ver `.env.example`)
- Laravel Sanctum (SPA cookie-based)
- Locale `pt` (mensagens de validação em português, `laravel-lang/lang`)

## Correr localmente

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

O `--seed` popula: matriz RBAC dos 12 papéis (`RolePermissionSeeder`), 10 departamentos (`DepartmentSeeder`), 12 contas de demonstração — uma por papel, email `<papel>@iiv.demo`, password `<papel>123` (`UserSeeder`).

Para o frontend (`../Front-end`) consumir esta API em vez do mock MSW: `VITE_API_MOCK=false` e `VITE_API_URL=http://localhost:8000/api` no `.env` do frontend, mais `FRONTEND_URL`/`SANCTUM_STATEFUL_DOMAINS` aqui a apontar para a origem real do Vite dev server (por omissão `http://localhost:5173`).

## Testes

```bash
php artisan test
```

## Deploy com Docker

`docker-compose.yml` sobe `nginx` (porta 8000) + `app`/`queue` (PHP-FPM 8.2 Alpine) + um serviço `migrate` one-shot que corre as migrations antes de `app`/`queue` arrancarem — **sem container/volume de BD**: liga-se directamente a um servidor MySQL/MariaDB externo já provisionado, via `DB_HOST`/`DB_USERNAME`/`DB_PASSWORD` em `.env.docker`.

```bash
touch .env.docker   # editar: APP_KEY, DB_HOST, DB_USERNAME, DB_PASSWORD, FRONTEND_URL, SANCTUM_STATEFUL_DOMAINS (ver variáveis em docker-compose.yml)
php artisan key:generate --show --no-ansi   # copiar o valor para APP_KEY em .env.docker
docker compose --env-file .env.docker up --build
```

**Nunca copiar/renomear isto para `.env`** — esse nome colide com o `.env` de desenvolvimento local do Laravel (`APP_DEBUG=true`, `APP_ENV=local`); usar sempre a flag `--env-file .env.docker` explícita em todos os comandos `docker compose` (`up`, `down`, `ps`, `exec`, ...). `APP_ENV`/`APP_DEBUG`/`LOG_LEVEL` estão também fixos em produção directamente no `docker-compose.yml`, como segunda linha de defesa.

`APP_KEY`, `DB_HOST`, `DB_USERNAME` e `DB_PASSWORD` são obrigatórios (o compose recusa arrancar sem eles — ver `:?` em `docker-compose.yml`). Sem seed automático: correr `docker compose --env-file .env.docker exec app php artisan db:seed --force` manualmente (necessário para a matriz RBAC funcionar — sem seed, todos os papéis não-admin ficam sem nenhuma permissão) se forem necessárias também as 12 contas de demonstração.

`.env.docker` alimenta apenas a interpolação `${VAR}` do `docker-compose.yml` — é distinto do `.env` que o Laravel lê dentro do container, que vem inteiramente das variáveis `environment:` do compose, não de um ficheiro copiado para a imagem.

## Contrato da API

Fonte de verdade: `../Front-end/src/services/api/endpoints.ts`, `../Front-end/src/types/dto/*.ts`, `../Front-end/src/mocks/handlers/*.ts`. Este backend implementa esse contrato — em caso de dúvida sobre um campo exacto, o DTO correspondente é a referência definitiva.

## Documentação Swagger/OpenAPI

Gerada automaticamente pelo [Scramble](https://scramble.dedoc.co) a partir do código real (rotas, FormRequests, API Resources) — sem anotações manuais, fica sempre sincronizada com o código.

- UI interactiva: `/docs/api`
- Schema OpenAPI cru: `/docs/api.json`

**Temporariamente aberto sem sessão em qualquer ambiente** (pedido explícito do cliente nesta fase) — o schema é só leitura; as acções reais ("Try it out") continuam protegidas pelo Sanctum/RBAC nas próprias rotas, a documentação não contorna isso. `Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess` — ver `config('scramble.middleware')` — volta a restringir `/docs/api` fora de `local` a sessões com papel `admin` (`Gate::viewApiDocs`, já definido em `AppServiceProvider::boot()`, apenas sem uso por agora) quando o cliente pedir para reactivar.

`config/scramble.php` define título/descrição/versão, o esquema de segurança (Sanctum SPA cookie-based, não bearer — ver descrição no `/docs/api`) e `App\Support\Scramble\PaginatedResponseTypeExtension`, que ensina o Scramble a tipar correctamente o envelope `{data, meta}` de `PaginatedResponse::make()` (usado por ~50 endpoints `index()`) — sem esta extensão o `data` ficava tipado como `string` em vez do array de resources.

Interface: Swagger UI clássico (`resources/views/docs/swagger-ui.blade.php`, via CDN `swagger-ui-dist`), não o `elements`/Stoplight que vem por omissão no pacote — troca pedida pelo cliente, já familiarizado com o Swagger UI de outros projectos.

**Atenção com `config/scramble.php`**: nunca instanciar um objecto directamente no array devolvido (ex.: `SecurityScheme::apiKey(...)`) — `php artisan config:cache` (corrido em todos os deploys, ver `.github/workflows/main.yml`) faz `var_export()` da config inteira, e um objecto sem `__set_state()` rebenta esse comando a meio do deploy (já aconteceu — commit `fad42ad`). Guardar sempre só class-strings na config e construir o objecto real dentro da própria classe (ver `App\Support\Scramble\SanctumCookieSecurityStrategy`).

Recalcular depois de mudanças de rotas/validação: `php artisan scramble:clear` (o schema fica em cache).

## Fases de construção

Ver `../SIG-IIV-MEMORIA-PROJETO.md` secção 15 para a lista completa das Ondas (Fundação → Laboratório → Produção → Qualidade → Gestão → Financeiro/Património → Pessoas/Missões → Recursos/Produção → Investigação/BI → Observatório Veterinário Nacional → Cutover). Estado actual: Ondas 1-11 concluídas, incluindo o frontend do Observatório e a ligação a este backend real; dockerização de produção completa. Gaps conhecidos: módulo Insumos ainda não implementado no backend (rota `/insumos` inexistente).
