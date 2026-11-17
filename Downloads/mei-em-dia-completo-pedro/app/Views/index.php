<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'MEI em Dia') ?> - Gestão Financeira para MEIs</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>

    <!-- Loading Spinner Overlay -->
    <div id="loadingOverlay" class="d-none">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
        <div id="appToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 1. TELA DE AUTENTICAÇÃO (LOGIN / CADASTRO) -->
    <!-- ========================================== -->
    <div id="authSection" class="auth-wrapper">
        <div class="card auth-card">
            <div class="auth-header">
                <h3 class="mb-1"><i class="bi bi-wallet2 me-2"></i>MEI em Dia</h3>
                <p class="mb-0 small text-white-50">Gestão financeira simplificada para microempreendedores</p>
            </div>
            <div class="card-body p-4">
                <!-- Nav Tabs para alternar Login / Cadastro -->
                <ul class="nav nav-pills nav-justified mb-4" id="authTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tabLoginBtn" data-bs-toggle="pill" data-bs-target="#tabLogin" type="button" role="tab">Entrar</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tabRegisterBtn" data-bs-toggle="pill" data-bs-target="#tabRegister" type="button" role="tab">Criar Conta</button>
                    </li>
                </ul>

                <div class="tab-content" id="authTabsContent">
                    <!-- Formulário de Login -->
                    <div class="tab-pane fade show active" id="tabLogin" role="tabpanel">
                        <form id="formLogin">
                            <div class="mb-3">
                                <label for="loginEmail" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="loginEmail" placeholder="seu@email.com" required>
                            </div>
                            <div class="mb-3">
                                <label for="loginPassword" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="loginPassword" placeholder="Sua senha" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Acessar Sistema
                            </button>
                        </form>

                        <div class="mt-4 p-3 bg-light rounded border text-muted small">
                            <strong class="text-dark"><i class="bi bi-info-circle me-1"></i>Acesso de demonstração:</strong><br>
                            E-mail: <code>pedro@email.com</code><br>
                            Senha: <code>SenhaSegura123</code>
                        </div>
                    </div>

                    <!-- Formulário de Cadastro -->
                    <div class="tab-pane fade" id="tabRegister" role="tabpanel">
                        <form id="formRegister">
                            <div class="mb-3">
                                <label for="regName" class="form-label">Seu Nome</label>
                                <input type="text" class="form-control" id="regName" placeholder="Ex: Maria Souza" required>
                            </div>
                            <div class="mb-3">
                                <label for="regEmail" class="form-label">Seu E-mail</label>
                                <input type="email" class="form-control" id="regEmail" placeholder="maria@email.com" required>
                            </div>
                            <div class="mb-3">
                                <label for="regBusinessName" class="form-label">Nome do seu Negócio / Empresa</label>
                                <input type="text" class="form-control" id="regBusinessName" placeholder="Ex: Maria Confecções MEI" required>
                            </div>
                            <div class="mb-3">
                                <label for="regPassword" class="form-label">Senha (mínimo 8 caracteres)</label>
                                <input type="password" class="form-control" id="regPassword" minlength="8" placeholder="Senha segura" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 py-2">
                                <i class="bi bi-person-check me-1"></i> Criar Conta e Começar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. APLICAÇÃO PRINCIPAL (QUANDO AUTENTICADO) -->
    <!-- ========================================== -->
    <div id="appSection" class="d-none">
        <!-- Barra de Navegação Superior -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
            <div class="container-fluid px-lg-4">
                <a class="navbar-brand d-flex align-items-center" href="#dashboard">
                    <i class="bi bi-wallet2 fs-4 me-2"></i> MEI em Dia
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link active" id="navLinkDashboard" href="#dashboard" onclick="app.showPage('dashboard')">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="navLinkTransactions" href="#transactions" onclick="app.showPage('transactions')">
                                <i class="bi bi-arrow-left-right me-1"></i> Movimentações
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="navLinkCategories" href="#categories" onclick="app.showPage('categories')">
                                <i class="bi bi-tags me-1"></i> Categorias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="navLinkBusiness" href="#business" onclick="app.showPage('business')">
                                <i class="bi bi-building me-1"></i> Minha Empresa
                            </a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-white small">
                            <i class="bi bi-person-circle me-1"></i>
                            <span id="navUserName">Usuário</span> 
                            <span class="badge bg-light text-primary ms-1" id="navBusinessName">Empresa</span>
                        </span>
                        <button class="btn btn-outline-light btn-sm" onclick="app.logout()">
                            <i class="bi bi-box-arrow-right me-1"></i> Sair
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-lg-4 py-4">

            <!-- --------------------------------------- -->
            <!-- PÁGINA 1: DASHBOARD FINANCEIRO          -->
            <!-- --------------------------------------- -->
            <div id="pageDashboard" class="page-content">
                <!-- Cabeçalho com Filtro de Mês/Ano -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <div>
                        <h4 class="mb-0 fw-bold">Painel Financeiro</h4>
                        <p class="text-muted small mb-0">Acompanhe suas receitas, despesas e saldo em tempo real</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" id="dashMonth" style="width: 140px;">
                            <option value="1">Janeiro</option>
                            <option value="2">Fevereiro</option>
                            <option value="3">Março</option>
                            <option value="4">Abril</option>
                            <option value="5">Maio</option>
                            <option value="6">Junho</option>
                            <option value="7">Julho</option>
                            <option value="8">Agosto</option>
                            <option value="9">Setembro</option>
                            <option value="10">Outubro</option>
                            <option value="11">Novembro</option>
                            <option value="12">Dezembro</option>
                        </select>
                        <select class="form-select form-select-sm" id="dashYear" style="width: 100px;">
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                        </select>
                        <button class="btn btn-primary btn-sm" onclick="app.loadDashboard()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Atualizar
                        </button>
                    </div>
                </div>

                <!-- Cards de Indicadores (KPIs) -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card kpi-card h-100 p-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small text-uppercase fw-semibold">Receitas Pagas</span>
                                    <h3 class="fw-bold text-success mt-1 mb-0" id="kpiIncome">R$ 0,00</h3>
                                    <span class="text-muted small">Recebimentos do mês</span>
                                </div>
                                <div class="kpi-icon bg-success-subtle text-success">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card kpi-card h-100 p-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small text-uppercase fw-semibold">Despesas Pagas</span>
                                    <h3 class="fw-bold text-danger mt-1 mb-0" id="kpiExpenses">R$ 0,00</h3>
                                    <span class="text-muted small">Saídas quitadas</span>
                                </div>
                                <div class="kpi-icon bg-danger-subtle text-danger">
                                    <i class="bi bi-graph-down-arrow"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card kpi-card h-100 p-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small text-uppercase fw-semibold">Saldo Atual</span>
                                    <h3 class="fw-bold mt-1 mb-0" id="kpiBalance">R$ 0,00</h3>
                                    <span class="text-muted small">Receitas - Despesas</span>
                                </div>
                                <div class="kpi-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-wallet"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card kpi-card h-100 p-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small text-uppercase fw-semibold">Pendências</span>
                                    <h5 class="fw-bold text-warning mb-1" id="kpiPendingText">R$ 0,00</h5>
                                    <span class="badge bg-danger" id="kpiOverdueBadge">0 atrasadas</span>
                                </div>
                                <div class="kpi-icon bg-warning-subtle text-warning">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Limite Anual MEI (Melhoria de Feedback) -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm p-3 bg-white">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="fw-bold"><i class="bi bi-shield-check text-success me-1"></i> Limite Anual MEI (DASN-SIMEI)</span>
                                    <span class="text-muted small ms-2" id="annualRevenueText">Faturamento acumulado: R$ 0,00 de R$ 81.000,00</span>
                                </div>
                                <span class="badge bg-success" id="annualStatusBadge">Dentro do Limite</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" id="annualProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos e Ações Rápidas -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-lg-8">
                        <div class="card border-0 shadow-sm p-3 bg-white h-100">
                            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Comparativo: Receitas vs Despesas</h6>
                            <div style="position: relative; height: 280px;">
                                <canvas id="chartIncomeExpense"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm p-3 bg-white h-100">
                            <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-success me-2"></i>Despesas por Categoria</h6>
                            <div style="position: relative; height: 280px;">
                                <canvas id="chartCategories"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Atalhos rápidos de ação -->
                <div class="card border-0 shadow-sm p-3 bg-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span class="fw-bold"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Ações Rápidas:</span>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm" onclick="app.openTransactionModal('income')">
                                <i class="bi bi-plus-circle me-1"></i> Nova Receita
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="app.openTransactionModal('expense')">
                                <i class="bi bi-dash-circle me-1"></i> Nova Despesa
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="app.showPage('transactions')">
                                <i class="bi bi-list-ul me-1"></i> Ver Todas Movimentações
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- --------------------------------------- -->
            <!-- PÁGINA 2: MOVIMENTAÇÕES FINANCEIRAS     -->
            <!-- --------------------------------------- -->
            <div id="pageTransactions" class="page-content d-none">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div>
                        <h4 class="mb-0 fw-bold">Movimentações Financeiras</h4>
                        <p class="text-muted small mb-0">Gerencie todas as entradas e saídas do seu negócio</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="/api/transactions/export" class="btn btn-outline-secondary" target="_blank" title="Exportar CSV">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV
                        </a>
                        <button class="btn btn-success" onclick="app.openTransactionModal('income')">
                            <i class="bi bi-plus-lg me-1"></i> Nova Receita
                        </button>
                        <button class="btn btn-danger" onclick="app.openTransactionModal('expense')">
                            <i class="bi bi-dash-lg me-1"></i> Nova Despesa
                        </button>
                    </div>
                </div>

                <!-- Card de Filtros -->
                <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
                    <div class="row g-2">
                        <div class="col-12 col-md-3">
                            <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Buscar descrição...">
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select form-select-sm" id="filterType">
                                <option value="">Todos os tipos</option>
                                <option value="income">Receitas</option>
                                <option value="expense">Despesas</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select form-select-sm" id="filterStatus">
                                <option value="">Todos os status</option>
                                <option value="pending">Pendente</option>
                                <option value="paid">Pago</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" class="form-control form-control-sm" id="filterStartDate" title="Data inicial">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" class="form-control form-control-sm" id="filterEndDate" title="Data final">
                        </div>
                        <div class="col-12 col-md-1 d-grid">
                            <button class="btn btn-primary btn-sm" onclick="app.loadTransactions(1)">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Movimentações -->
                <div class="card border-0 shadow-sm bg-white overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Vencimento</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsTableBody">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Carregando movimentações...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginação -->
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <span class="text-muted small" id="txPaginationInfo">Exibindo 0 registros</span>
                        <div class="btn-group btn-group-sm" id="txPaginationControls">
                            <!-- Injetado por JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- --------------------------------------- -->
            <!-- PÁGINA 3: CATEGORIAS                    -->
            <!-- --------------------------------------- -->
            <div id="pageCategories" class="page-content d-none">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div>
                        <h4 class="mb-0 fw-bold">Categorias</h4>
                        <p class="text-muted small mb-0">Organize suas despesas e receitas por áreas</p>
                    </div>
                    <button class="btn btn-primary" onclick="app.openCategoryModal()">
                        <i class="bi bi-plus-lg me-1"></i> Nova Categoria
                    </button>
                </div>

                <!-- Filtro de Categorias -->
                <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <span class="small fw-semibold text-muted">Filtrar por tipo:</span>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="catTypeFilter" id="catFilterAll" value="" checked onchange="app.loadCategories()">
                                <label class="btn btn-outline-secondary" for="catFilterAll">Todas</label>

                                <input type="radio" class="btn-check" name="catTypeFilter" id="catFilterIncome" value="income" onchange="app.loadCategories()">
                                <label class="btn btn-outline-success" for="catFilterIncome">Receitas</label>

                                <input type="radio" class="btn-check" name="catTypeFilter" id="catFilterExpense" value="expense" onchange="app.loadCategories()">
                                <label class="btn btn-outline-danger" for="catFilterExpense">Despesas</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Categorias -->
                <div class="card border-0 shadow-sm bg-white overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome da Categoria</th>
                                    <th>Tipo</th>
                                    <th>Data de Cadastro</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Carregando categorias...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- --------------------------------------- -->
            <!-- PÁGINA 4: MINHA EMPRESA (PERFIL)        -->
            <!-- --------------------------------------- -->
            <div id="pageBusiness" class="page-content d-none">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8 col-lg-6">
                        <div class="card border-0 shadow-sm p-4 bg-white">
                            <h4 class="fw-bold mb-3"><i class="bi bi-building text-primary me-2"></i>Perfil Empresarial</h4>
                            <p class="text-muted small mb-4">Mantenha os dados cadastrais do seu MEI atualizados</p>

                            <form id="formBusiness">
                                <div class="mb-3">
                                    <label for="bizName" class="form-label">Nome da Empresa / Razão Social</label>
                                    <input type="text" class="form-control" id="bizName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="bizCnpj" class="form-label">CNPJ (14 dígitos, opcional)</label>
                                    <input type="text" class="form-control" id="bizCnpj" placeholder="00.000.000/0000-00" maxlength="18">
                                    <div class="form-text">Informe os 14 dígitos numéricos do seu CNPJ MEI.</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAIS DE CADASTRO E EDIÇÃO                -->
    <!-- ========================================== -->

    <!-- Modal Movimentação (Receita / Despesa) -->
    <div class="modal fade" id="modalTransaction" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" id="modalTxHeader">
                    <h5 class="modal-title fw-bold" id="modalTxTitle">Nova Movimentação</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formTransaction">
                    <div class="modal-body p-4">
                        <input type="hidden" id="txId">
                        <input type="hidden" id="txVersion" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de Movimentação</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="txType" id="txTypeIncome" value="income" onchange="app.onTxTypeChanged()">
                                <label class="btn btn-outline-success" for="txTypeIncome"><i class="bi bi-arrow-up-circle me-1"></i> Receita</label>

                                <input type="radio" class="btn-check" name="txType" id="txTypeExpense" value="expense" onchange="app.onTxTypeChanged()">
                                <label class="btn btn-outline-danger" for="txTypeExpense"><i class="bi bi-arrow-down-circle me-1"></i> Despesa</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="txDescription" class="form-label">Descrição</label>
                            <input type="text" class="form-control" id="txDescription" placeholder="Ex: Venda de serviço ou Compra de insumos" required maxlength="180">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="txAmount" class="form-label">Valor (R$)</label>
                                <input type="number" class="form-control" id="txAmount" placeholder="0.00" step="0.01" min="0.01" required>
                            </div>
                            <div class="col-6">
                                <label for="txDueDate" class="form-label">Data de Vencimento</label>
                                <input type="date" class="form-control" id="txDueDate" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="txCategory" class="form-label">Categoria</label>
                            <select class="form-select" id="txCategory" required>
                                <option value="">Selecione uma categoria...</option>
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label for="txStatus" class="form-label">Status</label>
                                <select class="form-select" id="txStatus" onchange="app.onTxStatusChanged()">
                                    <option value="pending">Pendente</option>
                                    <option value="paid">Pago</option>
                                    <option value="cancelled">Cancelado</option>
                                </select>
                            </div>
                            <div class="col-6" id="groupPaidAt">
                                <label for="txPaidAt" class="form-label">Data Pagamento</label>
                                <input type="datetime-local" class="form-control" id="txPaidAt">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveTx">Salvar Movimentação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Categoria -->
    <div class="modal fade" id="modalCategory" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalCatTitle">Nova Categoria</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCategory">
                    <div class="modal-body p-4">
                        <input type="hidden" id="catId">
                        <div class="mb-3">
                            <label for="catName" class="form-label">Nome da Categoria</label>
                            <input type="text" class="form-control" id="catName" placeholder="Ex: Marketing e Anúncios" required maxlength="80">
                        </div>
                        <div class="mb-3">
                            <label for="catType" class="form-label">Tipo</label>
                            <select class="form-select" id="catType" required>
                                <option value="expense">Despesa</option>
                                <option value="income">Receita</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmação de Exclusão -->
    <div class="modal fade" id="modalDeleteConfirm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title fw-bold">Confirmar Exclusão</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-3">
                    <p class="mb-0" id="deleteConfirmMessage">Tem certeza de que deseja excluir este item?</p>
                </div>
                <div class="modal-footer justify-content-center bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Não, cancelar</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btnConfirmDelete">Sim, excluir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <!-- App Logic -->
    <script src="/js/app.js"></script>
</body>
</html>
