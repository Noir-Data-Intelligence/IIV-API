<?php

use App\Http\Controllers\Api\AgriculturaController;
use App\Http\Controllers\Api\AmostraController;
use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\AuditoriaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvaliacaoController;
use App\Http\Controllers\Api\BoletimController;
use App\Http\Controllers\Api\ChatAssistantController;
use App\Http\Controllers\Api\ContaController;
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
use App\Http\Controllers\Api\EstacaoController;
use App\Http\Controllers\Api\FormacaoController;
use App\Http\Controllers\Api\HeroSlideController;
use App\Http\Controllers\Api\InqueritoSatisfacaoController;
use App\Http\Controllers\Api\InseminacaoController;
use App\Http\Controllers\Api\InvestigacaoController;
use App\Http\Controllers\Api\LaboratorioController;
use App\Http\Controllers\Api\LancamentoController;
use App\Http\Controllers\Api\LegislacaoController;
use App\Http\Controllers\Api\LoteController;
use App\Http\Controllers\Api\MissaoController;
use App\Http\Controllers\Api\NaoConformidadeController;
use App\Http\Controllers\Api\NoticiaController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrcamentoController;
use App\Http\Controllers\Api\PatrimonioCentralController;
use App\Http\Controllers\Api\PatrimonioEstacaoController;
use App\Http\Controllers\Api\PlanoController;
use App\Http\Controllers\Api\ProcessoController;
use App\Http\Controllers\Api\ProcessTypeController;
use App\Http\Controllers\Api\ProducaoPecuariaController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\PublicStatsController;
use App\Http\Controllers\Api\RbacController;
use App\Http\Controllers\Api\RequisicaoController;
use App\Http\Controllers\Api\RhLaboratorioController;
use App\Http\Controllers\Api\RhTransversalController;
use App\Http\Controllers\Api\StockController;
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

    // --- Onda 7 — Financeiro ---------------------------------------------------
    Route::get('/contas', [ContaController::class, 'index'])->middleware('module:financeiro');
    Route::post('/contas', [ContaController::class, 'store'])->middleware('module:financeiro,write');
    Route::put('/contas/{conta}', [ContaController::class, 'update'])->middleware('module:financeiro,write');
    Route::delete('/contas/{conta}', [ContaController::class, 'destroy'])->middleware('module:financeiro,write');

    Route::get('/lancamentos', [LancamentoController::class, 'index'])->middleware('module:financeiro');
    Route::post('/lancamentos', [LancamentoController::class, 'store'])->middleware('module:financeiro,write');
    Route::put('/lancamentos/{lancamento}', [LancamentoController::class, 'update'])->middleware('module:financeiro,write');
    Route::delete('/lancamentos/{lancamento}', [LancamentoController::class, 'destroy'])->middleware('module:financeiro,write');

    Route::get('/orcamentos', [OrcamentoController::class, 'index'])->middleware('module:financeiro');
    Route::post('/orcamentos', [OrcamentoController::class, 'store'])->middleware('module:financeiro,write');
    Route::put('/orcamentos/{orcamento}', [OrcamentoController::class, 'update'])->middleware('module:financeiro,write');
    Route::delete('/orcamentos/{orcamento}', [OrcamentoController::class, 'destroy'])->middleware('module:financeiro,write');

    // --- Onda 7 — Património Central (DG/Lab) — esquema separado -------------
    // Dualidade obrigatória (ver SIG-IIV-MEMORIA-PROJETO.md secção 6): NUNCA
    // partilhar tabela/controller/rota com o Património de Estação abaixo.
    Route::get('/patrimonio-central', [PatrimonioCentralController::class, 'index'])->middleware('module:patrimonio');
    Route::post('/patrimonio-central', [PatrimonioCentralController::class, 'store'])->middleware('module:patrimonio,write');
    Route::put('/patrimonio-central/{patrimonioCentral}', [PatrimonioCentralController::class, 'update'])->middleware('module:patrimonio,write');
    Route::delete('/patrimonio-central/{patrimonioCentral}', [PatrimonioCentralController::class, 'destroy'])->middleware('module:patrimonio,write');

    Route::get('/patrimonio-central/manutencoes', [PatrimonioCentralController::class, 'manutencoesIndex'])->middleware('module:patrimonio');
    Route::post('/patrimonio-central/manutencoes', [PatrimonioCentralController::class, 'manutencoesStore'])->middleware('module:patrimonio,write');
    Route::put('/patrimonio-central/manutencoes/{manutencao}', [PatrimonioCentralController::class, 'manutencoesUpdate'])->middleware('module:patrimonio,write');
    Route::delete('/patrimonio-central/manutencoes/{manutencao}', [PatrimonioCentralController::class, 'manutencoesDestroy'])->middleware('module:patrimonio,write');

    // --- Onda 7 — Património de Estação — esquema separado -------------------
    Route::get('/patrimonio-estacao', [PatrimonioEstacaoController::class, 'index'])->middleware('module:patrimonio');
    Route::post('/patrimonio-estacao', [PatrimonioEstacaoController::class, 'store'])->middleware('module:patrimonio,write');
    Route::put('/patrimonio-estacao/{patrimonioEstacao}', [PatrimonioEstacaoController::class, 'update'])->middleware('module:patrimonio,write');
    Route::delete('/patrimonio-estacao/{patrimonioEstacao}', [PatrimonioEstacaoController::class, 'destroy'])->middleware('module:patrimonio,write');

    Route::get('/patrimonio-estacao/manutencoes', [PatrimonioEstacaoController::class, 'manutencoesIndex'])->middleware('module:patrimonio');
    Route::post('/patrimonio-estacao/manutencoes', [PatrimonioEstacaoController::class, 'manutencoesStore'])->middleware('module:patrimonio,write');
    Route::put('/patrimonio-estacao/manutencoes/{manutencao}', [PatrimonioEstacaoController::class, 'manutencoesUpdate'])->middleware('module:patrimonio,write');
    Route::delete('/patrimonio-estacao/manutencoes/{manutencao}', [PatrimonioEstacaoController::class, 'manutencoesDestroy'])->middleware('module:patrimonio,write');

    // --- Onda 8 — RH Transversal — esquema separado ---------------------------
    // Dualidade obrigatória (ver SIG-IIV-MEMORIA-PROJETO.md secção 6): NUNCA
    // partilhar tabela/controller/rota com o RH Laboratorial abaixo.
    Route::get('/rh-transversal/colaboradores', [RhTransversalController::class, 'index'])->middleware('module:rh');
    Route::post('/rh-transversal/colaboradores', [RhTransversalController::class, 'store'])->middleware('module:rh,write');
    Route::put('/rh-transversal/colaboradores/{colaborador}', [RhTransversalController::class, 'update'])->middleware('module:rh,write');
    Route::delete('/rh-transversal/colaboradores/{colaborador}', [RhTransversalController::class, 'destroy'])->middleware('module:rh,write');

    Route::get('/rh-transversal/contratos', [RhTransversalController::class, 'contratosIndex'])->middleware('module:rh');
    Route::post('/rh-transversal/contratos', [RhTransversalController::class, 'contratosStore'])->middleware('module:rh,write');
    Route::put('/rh-transversal/contratos/{contrato}', [RhTransversalController::class, 'contratosUpdate'])->middleware('module:rh,write');
    Route::delete('/rh-transversal/contratos/{contrato}', [RhTransversalController::class, 'contratosDestroy'])->middleware('module:rh,write');

    Route::get('/rh-transversal/ausencias', [RhTransversalController::class, 'ausenciasIndex'])->middleware('module:rh');
    Route::post('/rh-transversal/ausencias', [RhTransversalController::class, 'ausenciasStore'])->middleware('module:rh,write');
    Route::put('/rh-transversal/ausencias/{ausencia}', [RhTransversalController::class, 'ausenciasUpdate'])->middleware('module:rh,write');
    Route::delete('/rh-transversal/ausencias/{ausencia}', [RhTransversalController::class, 'ausenciasDestroy'])->middleware('module:rh,write');

    // --- Onda 8 — RH Laboratorial — esquema separado ---------------------------
    Route::get('/rh-laboratorio/colaboradores', [RhLaboratorioController::class, 'index'])->middleware('module:rh');
    Route::post('/rh-laboratorio/colaboradores', [RhLaboratorioController::class, 'store'])->middleware('module:rh,write');
    Route::put('/rh-laboratorio/colaboradores/{colaborador}', [RhLaboratorioController::class, 'update'])->middleware('module:rh,write');
    Route::delete('/rh-laboratorio/colaboradores/{colaborador}', [RhLaboratorioController::class, 'destroy'])->middleware('module:rh,write');

    Route::get('/rh-laboratorio/contratos', [RhLaboratorioController::class, 'contratosIndex'])->middleware('module:rh');
    Route::post('/rh-laboratorio/contratos', [RhLaboratorioController::class, 'contratosStore'])->middleware('module:rh,write');
    Route::put('/rh-laboratorio/contratos/{contrato}', [RhLaboratorioController::class, 'contratosUpdate'])->middleware('module:rh,write');
    Route::delete('/rh-laboratorio/contratos/{contrato}', [RhLaboratorioController::class, 'contratosDestroy'])->middleware('module:rh,write');

    Route::get('/rh-laboratorio/ausencias', [RhLaboratorioController::class, 'ausenciasIndex'])->middleware('module:rh');
    Route::post('/rh-laboratorio/ausencias', [RhLaboratorioController::class, 'ausenciasStore'])->middleware('module:rh,write');
    Route::put('/rh-laboratorio/ausencias/{ausencia}', [RhLaboratorioController::class, 'ausenciasUpdate'])->middleware('module:rh,write');
    Route::delete('/rh-laboratorio/ausencias/{ausencia}', [RhLaboratorioController::class, 'ausenciasDestroy'])->middleware('module:rh,write');

    // --- Onda 8 — Formações ----------------------------------------------------
    Route::get('/formacoes', [FormacaoController::class, 'index'])->middleware('module:rh');
    Route::post('/formacoes', [FormacaoController::class, 'store'])->middleware('module:rh,write');
    Route::put('/formacoes/{formacao}', [FormacaoController::class, 'update'])->middleware('module:rh,write');
    Route::delete('/formacoes/{formacao}', [FormacaoController::class, 'destroy'])->middleware('module:rh,write');

    // --- Onda 8 — Missões (3ª máquina de estados) -------------------------------
    // /missoes/stats tem de vir ANTES de /missoes/{missao}, mesma armadilha de
    // ordenação já resolvida na Onda 2 (/hero-slides/admin) e na Onda 6
    // (/processos/stats).
    Route::get('/missoes/stats', [MissaoController::class, 'stats'])->middleware('module:missoes');
    Route::get('/missoes', [MissaoController::class, 'index'])->middleware('module:missoes');
    Route::post('/missoes', [MissaoController::class, 'store'])->middleware('module:missoes,write');
    Route::get('/missoes/{missao}', [MissaoController::class, 'show'])->middleware('module:missoes');
    Route::put('/missoes/{missao}', [MissaoController::class, 'update'])->middleware('module:missoes,write');
    Route::delete('/missoes/{missao}', [MissaoController::class, 'destroy'])->middleware('module:missoes,write');

    Route::post('/missoes/{missao}/submeter', [MissaoController::class, 'submit'])->middleware('module:missoes,write');
    Route::post('/missoes/{missao}/aprovar', [MissaoController::class, 'approve'])->middleware('module:missoes,write');
    Route::post('/missoes/{missao}/rejeitar', [MissaoController::class, 'reject'])->middleware('module:missoes,write');

    Route::get('/missoes/{missao}/participantes', [MissaoController::class, 'participants'])->middleware('module:missoes');
    Route::post('/missoes/{missao}/participantes', [MissaoController::class, 'storeParticipant'])->middleware('module:missoes,write');
    Route::delete('/missoes/{missao}/participantes/{participante}', [MissaoController::class, 'destroyParticipant'])->middleware('module:missoes,write');

    Route::get('/missoes/{missao}/guia', [MissaoController::class, 'guide'])->middleware('module:missoes');
    Route::put('/missoes/{missao}/guia', [MissaoController::class, 'updateGuide'])->middleware('module:missoes,write');

    Route::get('/missoes/{missao}/despesas', [MissaoController::class, 'expenses'])->middleware('module:missoes');
    Route::post('/missoes/{missao}/despesas', [MissaoController::class, 'storeExpense'])->middleware('module:missoes,write');
    Route::delete('/missoes/{missao}/despesas/{despesa}', [MissaoController::class, 'destroyExpense'])->middleware('module:missoes,write');

    Route::get('/missoes/{missao}/relatorio', [MissaoController::class, 'report'])->middleware('module:missoes');
    Route::put('/missoes/{missao}/relatorio', [MissaoController::class, 'updateReport'])->middleware('module:missoes,write');
    Route::post('/missoes/{missao}/relatorio/aprovar', [MissaoController::class, 'approveReport'])->middleware('module:missoes,write');

    // --- Onda 8 — Avaliações de desempenho (4ª máquina de estados) -------------
    // /avaliacoes/ciclos, /avaliacoes/criterios e /avaliacoes/stats têm de vir
    // ANTES de /avaliacoes/{avaliacao} (mesma armadilha de ordenação).
    Route::get('/avaliacoes/ciclos', [AvaliacaoController::class, 'cyclesIndex'])->middleware('module:avaliacoes');
    Route::post('/avaliacoes/ciclos', [AvaliacaoController::class, 'cyclesStore'])->middleware('module:avaliacoes,write');
    Route::put('/avaliacoes/ciclos/{ciclo}', [AvaliacaoController::class, 'cyclesUpdate'])->middleware('module:avaliacoes,write');
    Route::delete('/avaliacoes/ciclos/{ciclo}', [AvaliacaoController::class, 'cyclesDestroy'])->middleware('module:avaliacoes,write');

    Route::get('/avaliacoes/criterios', [AvaliacaoController::class, 'criteriaIndex'])->middleware('module:avaliacoes');
    Route::post('/avaliacoes/criterios', [AvaliacaoController::class, 'criteriaStore'])->middleware('module:avaliacoes,write');
    Route::put('/avaliacoes/criterios/{criterio}', [AvaliacaoController::class, 'criteriaUpdate'])->middleware('module:avaliacoes,write');
    Route::delete('/avaliacoes/criterios/{criterio}', [AvaliacaoController::class, 'criteriaDestroy'])->middleware('module:avaliacoes,write');

    Route::get('/avaliacoes/stats', [AvaliacaoController::class, 'stats'])->middleware('module:avaliacoes');
    Route::get('/avaliacoes', [AvaliacaoController::class, 'index'])->middleware('module:avaliacoes');
    Route::post('/avaliacoes', [AvaliacaoController::class, 'store'])->middleware('module:avaliacoes,write');
    Route::get('/avaliacoes/{avaliacao}', [AvaliacaoController::class, 'show'])->middleware('module:avaliacoes');
    Route::put('/avaliacoes/{avaliacao}', [AvaliacaoController::class, 'update'])->middleware('module:avaliacoes,write');
    Route::delete('/avaliacoes/{avaliacao}', [AvaliacaoController::class, 'destroy'])->middleware('module:avaliacoes,write');

    Route::get('/avaliacoes/{avaliacao}/pontuacoes', [AvaliacaoController::class, 'scores'])->middleware('module:avaliacoes');
    Route::get('/avaliacoes/{avaliacao}/historico', [AvaliacaoController::class, 'history'])->middleware('module:avaliacoes');

    Route::post('/avaliacoes/{avaliacao}/submeter', [AvaliacaoController::class, 'submit'])->middleware('module:avaliacoes,write');
    Route::post('/avaliacoes/{avaliacao}/aprovar', [AvaliacaoController::class, 'approve'])->middleware('module:avaliacoes,write');
    Route::post('/avaliacoes/{avaliacao}/rejeitar', [AvaliacaoController::class, 'reject'])->middleware('module:avaliacoes,write');
    Route::post('/avaliacoes/{avaliacao}/validar', [AvaliacaoController::class, 'validate'])->middleware('module:avaliacoes,write');
    Route::post('/avaliacoes/{avaliacao}/reabrir', [AvaliacaoController::class, 'reopen'])->middleware('module:avaliacoes,write');

    // --- Onda 9 — Estações Zootécnicas -----------------------------------------
    Route::get('/estacoes', [EstacaoController::class, 'index'])->middleware('module:estacoes');
    Route::post('/estacoes', [EstacaoController::class, 'store'])->middleware('module:estacoes,write');
    Route::put('/estacoes/{estacao}', [EstacaoController::class, 'update'])->middleware('module:estacoes,write');
    Route::delete('/estacoes/{estacao}', [EstacaoController::class, 'destroy'])->middleware('module:estacoes,write');

    // --- Onda 9 — Stock ---------------------------------------------------------
    Route::get('/stock/itens', [StockController::class, 'itensIndex'])->middleware('module:stock');
    Route::post('/stock/itens', [StockController::class, 'itensStore'])->middleware('module:stock,write');
    Route::put('/stock/itens/{item}', [StockController::class, 'itensUpdate'])->middleware('module:stock,write');
    Route::delete('/stock/itens/{item}', [StockController::class, 'itensDestroy'])->middleware('module:stock,write');

    Route::get('/stock/localizacoes', [StockController::class, 'localizacoesIndex'])->middleware('module:stock');
    Route::post('/stock/localizacoes', [StockController::class, 'localizacoesStore'])->middleware('module:stock,write');
    Route::put('/stock/localizacoes/{localizacao}', [StockController::class, 'localizacoesUpdate'])->middleware('module:stock,write');
    Route::delete('/stock/localizacoes/{localizacao}', [StockController::class, 'localizacoesDestroy'])->middleware('module:stock,write');

    Route::get('/stock/movimentos', [StockController::class, 'movimentosIndex'])->middleware('module:stock');
    Route::post('/stock/movimentos', [StockController::class, 'movimentosStore'])->middleware('module:stock,write');
    Route::delete('/stock/movimentos/{movimento}', [StockController::class, 'movimentosDestroy'])->middleware('module:stock,write');

    // --- Onda 9 — Agricultura ----------------------------------------------------
    Route::get('/culturas', [AgriculturaController::class, 'culturasIndex'])->middleware('module:agricultura');
    Route::post('/culturas', [AgriculturaController::class, 'culturasStore'])->middleware('module:agricultura,write');
    Route::put('/culturas/{cultura}', [AgriculturaController::class, 'culturasUpdate'])->middleware('module:agricultura,write');
    Route::delete('/culturas/{cultura}', [AgriculturaController::class, 'culturasDestroy'])->middleware('module:agricultura,write');

    Route::get('/campos', [AgriculturaController::class, 'camposIndex'])->middleware('module:agricultura');
    Route::post('/campos', [AgriculturaController::class, 'camposStore'])->middleware('module:agricultura,write');
    Route::put('/campos/{campo}', [AgriculturaController::class, 'camposUpdate'])->middleware('module:agricultura,write');
    Route::delete('/campos/{campo}', [AgriculturaController::class, 'camposDestroy'])->middleware('module:agricultura,write');

    Route::get('/colheitas', [AgriculturaController::class, 'colheitasIndex'])->middleware('module:agricultura');
    Route::post('/colheitas', [AgriculturaController::class, 'colheitasStore'])->middleware('module:agricultura,write');
    Route::put('/colheitas/{colheita}', [AgriculturaController::class, 'colheitasUpdate'])->middleware('module:agricultura,write');
    Route::delete('/colheitas/{colheita}', [AgriculturaController::class, 'colheitasDestroy'])->middleware('module:agricultura,write');

    // --- Onda 9 — Produção Pecuária ----------------------------------------------
    Route::get('/producao-pecuaria', [ProducaoPecuariaController::class, 'index'])->middleware('module:pecuaria');
    Route::post('/producao-pecuaria', [ProducaoPecuariaController::class, 'store'])->middleware('module:pecuaria,write');
    Route::put('/producao-pecuaria/{producao}', [ProducaoPecuariaController::class, 'update'])->middleware('module:pecuaria,write');
    Route::delete('/producao-pecuaria/{producao}', [ProducaoPecuariaController::class, 'destroy'])->middleware('module:pecuaria,write');

    // --- Onda 9 — Animais ---------------------------------------------------------
    Route::get('/animais', [AnimalController::class, 'index'])->middleware('module:animais');
    Route::post('/animais', [AnimalController::class, 'store'])->middleware('module:animais,write');
    Route::put('/animais/{animal}', [AnimalController::class, 'update'])->middleware('module:animais,write');
    Route::delete('/animais/{animal}', [AnimalController::class, 'destroy'])->middleware('module:animais,write');

    Route::get('/animais/{animal}/eventos', [AnimalController::class, 'events'])->middleware('module:animais');
    Route::post('/animais/{animal}/eventos', [AnimalController::class, 'storeEvent'])->middleware('module:animais,write');

    Route::get('/animais/{animal}/saude', [AnimalController::class, 'healthRecords'])->middleware('module:animais');
    Route::post('/animais/{animal}/saude', [AnimalController::class, 'storeHealthRecord'])->middleware('module:animais,write');

    // --- Onda 9 — Inseminação Artificial (sem paginação, fiel ao mock) -----------
    Route::get('/inseminacao/centros', [InseminacaoController::class, 'centros'])->middleware('module:inseminacao');
    Route::post('/inseminacao/centros', [InseminacaoController::class, 'storeCentro'])->middleware('module:inseminacao,write');
    Route::put('/inseminacao/centros/{centro}', [InseminacaoController::class, 'updateCentro'])->middleware('module:inseminacao,write');
    Route::delete('/inseminacao/centros/{centro}', [InseminacaoController::class, 'destroyCentro'])->middleware('module:inseminacao,write');

    Route::get('/inseminacao/reprodutores', [InseminacaoController::class, 'reprodutores'])->middleware('module:inseminacao');
    Route::post('/inseminacao/reprodutores', [InseminacaoController::class, 'storeReprodutor'])->middleware('module:inseminacao,write');
    Route::put('/inseminacao/reprodutores/{reprodutor}', [InseminacaoController::class, 'updateReprodutor'])->middleware('module:inseminacao,write');
    Route::delete('/inseminacao/reprodutores/{reprodutor}', [InseminacaoController::class, 'destroyReprodutor'])->middleware('module:inseminacao,write');

    Route::get('/inseminacao/tanques', [InseminacaoController::class, 'tanques'])->middleware('module:inseminacao');
    Route::post('/inseminacao/tanques', [InseminacaoController::class, 'storeTanque'])->middleware('module:inseminacao,write');
    Route::put('/inseminacao/tanques/{tanque}', [InseminacaoController::class, 'updateTanque'])->middleware('module:inseminacao,write');
    Route::delete('/inseminacao/tanques/{tanque}', [InseminacaoController::class, 'destroyTanque'])->middleware('module:inseminacao,write');

    Route::get('/inseminacao/doses', [InseminacaoController::class, 'doses'])->middleware('module:inseminacao');
    Route::post('/inseminacao/doses', [InseminacaoController::class, 'storeDose'])->middleware('module:inseminacao,write');
    Route::put('/inseminacao/doses/{dose}', [InseminacaoController::class, 'updateDose'])->middleware('module:inseminacao,write');
    Route::delete('/inseminacao/doses/{dose}', [InseminacaoController::class, 'destroyDose'])->middleware('module:inseminacao,write');

    Route::get('/inseminacao/registos', [InseminacaoController::class, 'registos'])->middleware('module:inseminacao');
    Route::post('/inseminacao/registos', [InseminacaoController::class, 'storeRegisto'])->middleware('module:inseminacao,write');
    Route::put('/inseminacao/registos/{registo}', [InseminacaoController::class, 'updateRegisto'])->middleware('module:inseminacao,write');
    Route::delete('/inseminacao/registos/{registo}', [InseminacaoController::class, 'destroyRegisto'])->middleware('module:inseminacao,write');

    // --- Onda 10 — Investigação --------------------------------------------------
    // "BI" não tem endpoints próprios (mocks/handlers: agregação de leitura
    // client-side sobre os módulos já migrados) — ver secção 15 da memória.
    Route::get('/investigacao/stats', [InvestigacaoController::class, 'stats'])->middleware('module:investigacao');

    Route::get('/investigacao/linhas', [InvestigacaoController::class, 'linesIndex'])->middleware('module:investigacao');
    Route::post('/investigacao/linhas', [InvestigacaoController::class, 'linesStore'])->middleware('module:investigacao,write');
    Route::put('/investigacao/linhas/{linha}', [InvestigacaoController::class, 'linesUpdate'])->middleware('module:investigacao,write');
    Route::delete('/investigacao/linhas/{linha}', [InvestigacaoController::class, 'linesDestroy'])->middleware('module:investigacao,write');

    Route::get('/investigacao/projectos', [InvestigacaoController::class, 'projectsIndex'])->middleware('module:investigacao');
    Route::post('/investigacao/projectos', [InvestigacaoController::class, 'projectsStore'])->middleware('module:investigacao,write');
    Route::put('/investigacao/projectos/{projecto}', [InvestigacaoController::class, 'projectsUpdate'])->middleware('module:investigacao,write');
    Route::delete('/investigacao/projectos/{projecto}', [InvestigacaoController::class, 'projectsDestroy'])->middleware('module:investigacao,write');

    Route::get('/investigacao/publicacoes', [InvestigacaoController::class, 'publicationsIndex'])->middleware('module:investigacao');
    Route::post('/investigacao/publicacoes', [InvestigacaoController::class, 'publicationsStore'])->middleware('module:investigacao,write');
    Route::put('/investigacao/publicacoes/{publicacao}', [InvestigacaoController::class, 'publicationsUpdate'])->middleware('module:investigacao,write');
    Route::delete('/investigacao/publicacoes/{publicacao}', [InvestigacaoController::class, 'publicationsDestroy'])->middleware('module:investigacao,write');
});
