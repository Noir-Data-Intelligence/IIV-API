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

## Contrato da API

Fonte de verdade: `../Front-end/src/services/api/endpoints.ts`, `../Front-end/src/types/dto/*.ts`, `../Front-end/src/mocks/handlers/*.ts`. Este backend implementa esse contrato — em caso de dúvida sobre um campo exacto, o DTO correspondente é a referência definitiva.

## Fases de construção

Ver `../SIG-IIV-MEMORIA-PROJETO.md` secção 15 para a lista completa das Ondas (Fundação → Laboratório → Produção → Qualidade → Gestão → Financeiro/Património → Pessoas/Missões → Recursos/Produção → Investigação/BI → Observatório Veterinário Nacional → Cutover). Estado actual: Onda 1 (Fundação) concluída.
