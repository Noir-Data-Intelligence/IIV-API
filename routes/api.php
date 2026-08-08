<?php

use App\Http\Controllers\Api\AmostraController;
use App\Http\Controllers\Api\AuditoriaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BoletimController;
use App\Http\Controllers\Api\ChatAssistantController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\CriterioRejeicaoController;
use App\Http\Controllers\Api\DashboardAlertAckController;
use App\Http\Controllers\Api\DashboardAlertHistoryController;
use App\Http\Controllers\Api\DashboardPrefController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DistribuicaoController;
use App\Http\Controllers\Api\DocumentLinkController;
use App\Http\Controllers\Api\DocumentoController;
use App\Http\Controllers\Api\DocumentPermissionController;
use App\Http\Controllers\Api\HeroSlideController;
use App\Http\Controllers\Api\InqueritoSatisfacaoController;
use App\Http\Controllers\Api\LaboratorioController;
use App\Http\Controllers\Api\LegislacaoController;
use App\Http\Controllers\Api\LoteController;
use App\Http\Controllers\Api\NaoConformidadeController;
use App\Http\Controllers\Api\NoticiaController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PlanoController;
use App\Http\Controllers\Api\ProcessoController;
use App\Http\Controllers\Api\ProcessTypeController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\PublicStatsController;
use App\Http\Controllers\Api\RbacController;
use App\Http\Controllers\Api\RequisicaoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Onda 1 — Fundação + Onda 2 — Geral (ver SIG-IIV-MEMORIA-PROJETO.md secção 15)
|--------------------------------------------------------------------------
| Fonte de verdade do contrato: Front-end/src/services/api/endpoints.ts.
| Caminhos e nomes ficam EXACTAMENTE como no frontend — nunca traduzir.
*/

// --- Auth (sem auth:sanctum, ver secção 2.2 da memória) ---------------------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// --- Rotas públicas de conteúdo (Onda 2) — sem auth:sanctum ------------------
// GET honra o filtro `published` só se o cliente o enviar, nunca o impõe
// (fiel ao handler mock — ver NoticiaController/LegislacaoController).
Route::get('/noticias', [NoticiaController::class, 'index']);
Route::get('/noticias/{id}', [NoticiaController::class, 'show']);
Route::get('/legislacao', [LegislacaoController::class, 'index']);
Route::get('/legislacao/{id}', [LegislacaoController::class, 'show']);
Route::get('/hero-slides', [HeroSlideController::class, 'index']);
Route::get('/public/stats', [PublicStatsController::class, 'show']);
Route::post('/contact-messages', [ContactMessageController::class, 'store'])
    ->middleware('throttle:10,1');
Route::post('/inqueritos-satisfacao', [InqueritoSatisfacaoController::class, 'store'])
    ->middleware('throttle:10,1');

// --- Rotas autenticadas -------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::patch('/user/password', [AuthController::class, 'changePassword']);

    Route::post('/chat-assistant', [ChatAssistantController::class, 'stream']);

    Route::apiResource('departamentos', DepartmentController::class)
        ->middleware(['module:departamentos'])
        ->except(['store', 'update', 'destroy']);
    Route::post('/departamentos', [DepartmentController::class, 'store'])
        ->middleware('module:departamentos,write');
    Route::put('/departamentos/{department}', [DepartmentController::class, 'update'])
        ->middleware('module:departamentos,write');
    Route::delete('/departamentos/{department}', [DepartmentController::class, 'destroy'])
        ->middleware('module:departamentos,write');

    Route::prefix('rbac')->group(function () {
        Route::get('/permissions', [RbacController::class, 'permissions'])->middleware('module:rbac');
        Route::put('/permissions', [RbacController::class, 'updatePermissions'])->middleware('module:rbac,write');
        Route::get('/users', [RbacController::class, 'users'])->middleware('module:rbac');
        Route::post('/users/{user}/roles', [RbacController::class, 'addRole'])->middleware('module:rbac,write');
        Route::delete('/users/{user}/roles/{role}', [RbacController::class, 'removeRole'])->middleware('module:rbac,write');
    });

    // --- Noticias / Legislacao (escrita) ---------------------------------
    Route::post('/noticias', [NoticiaController::class, 'store'])->middleware('module:noticias,write');
    Route::put('/noticias/{noticia}', [NoticiaController::class, 'update'])->middleware('module:noticias,write');
    Route::delete('/noticias/{noticia}', [NoticiaController::class, 'destroy'])->middleware('module:noticias,write');

    Route::post('/legislacao', [LegislacaoController::class, 'store'])->middleware('module:legislacao,write');
    Route::put('/legislacao/{id}', [LegislacaoController::class, 'update'])->middleware('module:legislacao,write');
    Route::delete('/legislacao/{id}', [LegislacaoController::class, 'destroy'])->middleware('module:legislacao,write');

    // --- Hero Slides (admin) ----------------------------------------------
    // /hero-slides/admin tem de vir ANTES de /hero-slides/{heroSlide} (mais
    // especifico primeiro), senão "admin" seria interpretado como um id.
    Route::get('/hero-slides/admin', [HeroSlideController::class, 'adminIndex'])->middleware('module:slideshow');
    Route::post('/hero-slides', [HeroSlideController::class, 'store'])->middleware('module:slideshow,write');
    Route::put('/hero-slides/{heroSlide}', [HeroSlideController::class, 'update'])->middleware('module:slideshow,write');
    Route::delete('/hero-slides/{heroSlide}', [HeroSlideController::class, 'destroy'])->middleware('module:slideshow,write');

    // --- Mensagens de contacto (caixa de entrada admin) --------------------
    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->middleware('module:mensagens');
    Route::patch('/contact-messages/{contactMessage}', [ContactMessageController::class, 'update'])->middleware('module:mensagens,write');
    Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->middleware('module:mensagens,write');

    // --- Notificacoes (por utilizador, sem module gate — ver ModuleKey) ---
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}', [NotificationController::class, 'update']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'clear']);

    // --- Dashboard prefs / alert acks (por utilizador) ---------------------
    Route::get('/dashboard-prefs/me', [DashboardPrefController::class, 'show']);
    Route::put('/dashboard-prefs/me', [DashboardPrefController::class, 'update']);

    Route::get('/dashboard-alert-acks', [DashboardAlertAckController::class, 'index']);
    Route::post('/dashboard-alert-acks', [DashboardAlertAckController::class, 'store']);
    Route::delete('/dashboard-alert-acks/{metricKey}', [DashboardAlertAckController::class, 'destroy']);

    // --- Historico de alertas (modulo "historico-alertas") -----------------
    Route::get('/dashboard-alert-history', [DashboardAlertHistoryController::class, 'index'])->middleware('module:historico-alertas');
    Route::post('/dashboard-alert-history', [DashboardAlertHistoryController::class, 'store']);
    Route::put('/dashboard-alert-history/{dashboardAlertHistory}', [DashboardAlertHistoryController::class, 'update'])->middleware('module:historico-alertas,write');
    Route::delete('/dashboard-alert-history', [DashboardAlertHistoryController::class, 'clear'])->middleware('module:historico-alertas,write');

    // --- Onda 3 — Laboratório (ver deep-dive na SIG-IIV-MEMORIA-PROJETO.md secção 15) ---
    Route::apiResource('laboratorios', LaboratorioController::class)
        ->middleware(['module:laboratorios'])
        ->except(['store', 'update', 'destroy']);
    Route::post('/laboratorios', [LaboratorioController::class, 'store'])->middleware('module:laboratorios,write');
    Route::put('/laboratorios/{laboratorio}', [LaboratorioController::class, 'update'])->middleware('module:laboratorios,write');
    Route::delete('/laboratorios/{laboratorio}', [LaboratorioController::class, 'destroy'])->middleware('module:laboratorios,write');

    Route::get('/criterios-rejeicao', [CriterioRejeicaoController::class, 'index'])->middleware('module:laboratorios');
    Route::post('/criterios-rejeicao', [CriterioRejeicaoController::class, 'store'])->middleware('module:laboratorios,write');
    Route::delete('/criterios-rejeicao/{criterioRejeicao}', [CriterioRejeicaoController::class, 'destroy'])->middleware('module:laboratorios,write');

    Route::get('/requisicoes', [RequisicaoController::class, 'index'])->middleware('module:analises');
    Route::post('/requisicoes', [RequisicaoController::class, 'store'])->middleware('module:analises,write');
    Route::get('/requisicoes/{requisicao}', [RequisicaoController::class, 'show'])->middleware('module:analises');

    Route::get('/amostras', [AmostraController::class, 'index'])->middleware('module:analises');
    Route::get('/amostras/{amostra}', [AmostraController::class, 'show'])->middleware('module:analises');
    Route::post('/amostras/{amostra}/aceitar', [AmostraController::class, 'aceitar'])->middleware('module:analises,write');
    Route::post('/amostras/{amostra}/rejeitar', [AmostraController::class, 'rejeitar'])->middleware('module:analises,write');

    Route::get('/boletins', [BoletimController::class, 'index'])->middleware('module:analises');
    Route::get('/boletins/{boletim}', [BoletimController::class, 'show'])->middleware('module:analises');
    Route::put('/boletins/{boletim}/resultado', [BoletimController::class, 'registarResultado'])->middleware('module:analises,write');
    Route::post('/boletins/{boletim}/validar', [BoletimController::class, 'validar'])->middleware('module:resultados,write');
    Route::post('/boletins/{boletim}/comunicar', [BoletimController::class, 'comunicar'])->middleware('module:resultados,write');

    Route::get('/inqueritos-satisfacao', [InqueritoSatisfacaoController::class, 'index'])->middleware('module:analises');

    // --- Onda 4 — Produção ---------------------------------------------------
    Route::get('/produtos', [ProdutoController::class, 'index'])->middleware('module:produtos');
    Route::post('/produtos', [ProdutoController::class, 'store'])->middleware('module:produtos,write');
    Route::put('/produtos/{produto}', [ProdutoController::class, 'update'])->middleware('module:produtos,write');
    Route::delete('/produtos/{produto}', [ProdutoController::class, 'destroy'])->middleware('module:produtos,write');
    Route::post('/produtos/{produto}/archive', [ProdutoController::class, 'archive'])->middleware('module:produtos,write');
    Route::post('/produtos/{produto}/restore', [ProdutoController::class, 'restore'])->middleware('module:produtos,write');

    Route::get('/lotes', [LoteController::class, 'index'])->middleware('module:lotes');
    Route::post('/lotes', [LoteController::class, 'store'])->middleware('module:lotes,write');
    Route::put('/lotes/{lote}', [LoteController::class, 'update'])->middleware('module:lotes,write');
    Route::delete('/lotes/{lote}', [LoteController::class, 'destroy'])->middleware('module:lotes,write');

    Route::get('/planeamento', [PlanoController::class, 'index'])->middleware('module:planeamento');
    Route::post('/planeamento', [PlanoController::class, 'store'])->middleware('module:planeamento,write');
    Route::put('/planeamento/{plano}', [PlanoController::class, 'update'])->middleware('module:planeamento,write');
    Route::delete('/planeamento/{plano}', [PlanoController::class, 'destroy'])->middleware('module:planeamento,write');

    Route::get('/distribuicao', [DistribuicaoController::class, 'index'])->middleware('module:distribuicao');
    Route::post('/distribuicao', [DistribuicaoController::class, 'store'])->middleware('module:distribuicao,write');
    Route::put('/distribuicao/{distribuicao}', [DistribuicaoController::class, 'update'])->middleware('module:distribuicao,write');
    Route::delete('/distribuicao/{distribuicao}', [DistribuicaoController::class, 'destroy'])->middleware('module:distribuicao,write');

    // --- Onda 5 — Qualidade ---------------------------------------------------
    Route::get('/auditorias', [AuditoriaController::class, 'index'])->middleware('module:auditorias');
    Route::get('/auditorias/{auditoria}', [AuditoriaController::class, 'show'])->middleware('module:auditorias');
    Route::post('/auditorias', [AuditoriaController::class, 'store'])->middleware('module:auditorias,write');
    Route::put('/auditorias/{auditoria}', [AuditoriaController::class, 'update'])->middleware('module:auditorias,write');
    Route::delete('/auditorias/{auditoria}', [AuditoriaController::class, 'destroy'])->middleware('module:auditorias,write');

    Route::get('/nao-conformidades', [NaoConformidadeController::class, 'index'])->middleware('module:nao-conformidades');
    Route::get('/nao-conformidades/{naoConformidade}', [NaoConformidadeController::class, 'show'])->middleware('module:nao-conformidades');
    Route::post('/nao-conformidades', [NaoConformidadeController::class, 'store'])->middleware('module:nao-conformidades,write');
    Route::put('/nao-conformidades/{naoConformidade}', [NaoConformidadeController::class, 'update'])->middleware('module:nao-conformidades,write');
    Route::delete('/nao-conformidades/{naoConformidade}', [NaoConformidadeController::class, 'destroy'])->middleware('module:nao-conformidades,write');

    // --- Onda 6 — Gestão: Documentos ------------------------------------------
    Route::get('/document-categories', [DocumentoController::class, 'categories'])->middleware('module:documentos');

    Route::get('/documentos', [DocumentoController::class, 'index'])->middleware('module:documentos');
    Route::post('/documentos', [DocumentoController::class, 'store'])->middleware('module:documentos,write');
    Route::get('/documentos/{documento}', [DocumentoController::class, 'show'])->middleware('module:documentos');
    Route::put('/documentos/{documento}', [DocumentoController::class, 'update'])->middleware('module:documentos,write');
    Route::delete('/documentos/{documento}', [DocumentoController::class, 'destroy'])->middleware('module:documentos,write');
    Route::get('/documentos/{documento}/versions', [DocumentoController::class, 'versions'])->middleware('module:documentos');
    Route::post('/documentos/{documento}/versions', [DocumentoController::class, 'storeVersion'])->middleware('module:documentos,write');

    Route::get('/document-links', [DocumentLinkController::class, 'index'])->middleware('module:documentos');
    Route::post('/document-links', [DocumentLinkController::class, 'store'])->middleware('module:documentos,write');
    Route::delete('/document-links/{documentLink}', [DocumentLinkController::class, 'destroy'])->middleware('module:documentos,write');

    Route::get('/document-permissions', [DocumentPermissionController::class, 'index'])->middleware('module:documentos');
    Route::post('/document-permissions', [DocumentPermissionController::class, 'store'])->middleware('module:documentos,write');
    Route::delete('/document-permissions/{documentPermission}', [DocumentPermissionController::class, 'destroy'])->middleware('module:documentos,write');

    // --- Onda 6 — Gestão: Tipos de Processo -----------------------------------
    Route::get('/processos-tipos', [ProcessTypeController::class, 'index'])->middleware('module:processos');
    Route::post('/processos-tipos', [ProcessTypeController::class, 'store'])->middleware('module:processos,write');
    Route::put('/processos-tipos/{processoTipo}', [ProcessTypeController::class, 'update'])->middleware('module:processos,write');
    Route::delete('/processos-tipos/{processoTipo}', [ProcessTypeController::class, 'destroy'])->middleware('module:processos,write');
    Route::get('/processos-tipos/{processoTipo}/etapas', [ProcessTypeController::class, 'steps'])->middleware('module:processos');
    Route::post('/processos-tipos/{processoTipo}/etapas', [ProcessTypeController::class, 'storeStep'])->middleware('module:processos,write');
    Route::post('/processos-tipos/{processoTipo}/etapas/reordenar', [ProcessTypeController::class, 'reorderSteps'])->middleware('module:processos,write');
    Route::put('/processos-tipos/{processoTipo}/etapas/{etapa}', [ProcessTypeController::class, 'updateStep'])->middleware('module:processos,write');
    Route::delete('/processos-tipos/{processoTipo}/etapas/{etapa}', [ProcessTypeController::class, 'destroyStep'])->middleware('module:processos,write');

    // --- Onda 6 — Gestão: Processos (BPM) -------------------------------------
    Route::get('/processos/stats', [ProcessoController::class, 'stats'])->middleware('module:processos');
    Route::get('/processos', [ProcessoController::class, 'index'])->middleware('module:processos');
    Route::post('/processos', [ProcessoController::class, 'store'])->middleware('module:processos,write');
    Route::get('/processos/{processo}', [ProcessoController::class, 'show'])->middleware('module:processos');
    Route::put('/processos/{processo}', [ProcessoController::class, 'update'])->middleware('module:processos,write');
    Route::delete('/processos/{processo}', [ProcessoController::class, 'destroy'])->middleware('module:processos,write');

    Route::get('/processos/{processo}/etapas', [ProcessoController::class, 'steps'])->middleware('module:processos');
    Route::put('/processos/{processo}/etapas/{etapa}', [ProcessoController::class, 'updateStep'])->middleware('module:processos,write');

    Route::get('/processos/{processo}/eventos', [ProcessoController::class, 'events'])->middleware('module:processos');
    Route::post('/processos/{processo}/eventos', [ProcessoController::class, 'storeEvent'])->middleware('module:processos,write');

    Route::post('/processos/{processo}/avancar', [ProcessoController::class, 'advance'])->middleware('module:processos,write');
    Route::post('/processos/{processo}/devolver', [ProcessoController::class, 'returnStep'])->middleware('module:processos,write');
    Route::post('/processos/{processo}/cancelar', [ProcessoController::class, 'cancel'])->middleware('module:processos,write');

    Route::get('/processos/{processo}/anexos', [ProcessoController::class, 'attachments'])->middleware('module:processos');
    Route::post('/processos/{processo}/anexos', [ProcessoController::class, 'storeAttachment'])->middleware('module:processos,write');
    Route::delete('/processos/{processo}/anexos/{anexo}', [ProcessoController::class, 'destroyAttachment'])->middleware('module:processos,write');
});
