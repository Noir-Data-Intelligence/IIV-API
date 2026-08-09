# SIG-IIV — Backend

API REST do Sistema Integrado de Gestão do Instituto de Investigação Veterinária (IIV/MINAGRIF, Angola). Laravel 12 (PHP 8.2+), API-only, consumido pelo frontend em `../Front-end`.

Contexto completo do projecto (requisitos, regras de negócio, arquitectura, decisões em aberto): `../SIG-IIV-MEMORIA-PROJETO.md`.

## Stack

- Laravel 12, PHP 8.2+
- PostgreSQL 15+ (alvo de arquitectura — SQLite em uso local até o driver `pdo_pgsql`/servidor Postgres existirem nesta máquina, ver `.env.example`)
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

`docker-compose.yml` sobe a stack completa: `nginx` (porta 8000) + `app`/`queue` (PHP-FPM 8.2 Alpine) + `postgres` 15 + um serviço `migrate` one-shot que corre as migrations antes de `app`/`queue` arrancarem.

```bash
cp .env.example .env.docker   # editar: APP_KEY, DB_PASSWORD, FRONTEND_URL, SANCTUM_STATEFUL_DOMAINS
php artisan key:generate --show   # copiar o valor para APP_KEY em .env.docker
mv .env.docker .env               # docker compose lê .env na raiz do projecto para as variáveis ${...}
docker compose up --build
```

`APP_KEY` e `DB_PASSWORD` são obrigatórios (o compose recusa arrancar sem eles — ver `:?` em `docker-compose.yml`). Sem seed automático: correr `docker compose exec app php artisan db:seed` manualmente se forem necessárias as 12 contas de demonstração.

Este `.env` da raiz alimenta apenas o `docker-compose.yml` (interpolação `${VAR}`) — é distinto do `.env` que o Laravel lê dentro do container, que vem inteiramente das variáveis `environment:` do compose, não de um ficheiro copiado para a imagem.

## Contrato da API

Fonte de verdade: `../Front-end/src/services/api/endpoints.ts`, `../Front-end/src/types/dto/*.ts`, `../Front-end/src/mocks/handlers/*.ts`. Este backend implementa esse contrato — em caso de dúvida sobre um campo exacto, o DTO correspondente é a referência definitiva.

## Fases de construção

Ver `../SIG-IIV-MEMORIA-PROJETO.md` secção 15 para a lista completa das Ondas (Fundação → Laboratório → Produção → Qualidade → Gestão → Financeiro/Património → Pessoas/Missões → Recursos/Produção → Investigação/BI → Observatório Veterinário Nacional → Cutover). Estado actual: Onda 1 (Fundação) concluída.
