/**
 * MEI em Dia - Lógica Frontend
 */

const app = {
    user: null,
    business: null,
    csrfToken: '',
    categories: [],
    currentPage: 'dashboard',
    currentTxPage: 1,
    chartBar: null,
    chartPie: null,
    modalTx: null,
    modalCat: null,
    modalDel: null,
    pendingDeleteAction: null,

    init() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        // Modais Bootstrap
        this.modalTx = new bootstrap.Modal(document.getElementById('modalTransaction'));
        this.modalCat = new bootstrap.Modal(document.getElementById('modalCategory'));
        this.modalDel = new bootstrap.Modal(document.getElementById('modalDeleteConfirm'));

        // Configura seletor de mês/ano do Dashboard com mês/ano atual
        const now = new Date();
        document.getElementById('dashMonth').value = String(now.getMonth() + 1);
        document.getElementById('dashYear').value = String(now.getFullYear());

        this.bindEvents();
        this.checkAuth();
    },

    bindEvents() {
        // Formulário de Login
        document.getElementById('formLogin').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            await this.login(email, password);
        });

        // Formulário de Cadastro
        document.getElementById('formRegister').addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;
            const business_name = document.getElementById('regBusinessName').value;
            await this.register({ name, email, password, business_name });
        });

        // Formulário de Movimentação
        document.getElementById('formTransaction').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.saveTransaction();
        });

        // Formulário de Categoria
        document.getElementById('formCategory').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.saveCategory();
        });

        // Formulário de Perfil Empresarial
        document.getElementById('formBusiness').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.saveBusiness();
        });

        // Confirmação de Exclusão
        document.getElementById('btnConfirmDelete').addEventListener('click', async () => {
            if (typeof this.pendingDeleteAction === 'function') {
                await this.pendingDeleteAction();
                this.pendingDeleteAction = null;
                this.modalDel.hide();
            }
        });
    },

    // ----------------------------------------------------
    // API Client Fetch Wrapper
    // ----------------------------------------------------
    async api(url, options = {}) {
        const headers = {
            'Accept': 'application/json',
            ...(options.headers || {})
        };

        if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        if (this.csrfToken) {
            headers['X-CSRF-Token'] = this.csrfToken;
        }

        try {
            this.showLoading(true);
            const response = await fetch(url, { credentials: 'same-origin', ...options, headers });
            const data = await response.json();

            if (response.status === 401 && !url.includes('/login') && !url.includes('/register')) {
                this.setUnauthenticated();
                return data;
            }

            return { ok: response.ok, status: response.status, ...data };
        } catch (err) {
            console.error('API Error:', err);
            this.toast('Erro na conexão com o servidor.', 'danger');
            return { ok: false, status: 500, message: 'Erro na conexão com o servidor.' };
        } finally {
            this.showLoading(false);
        }
    },

    // ----------------------------------------------------
    // Autenticação e Sessão
    // ----------------------------------------------------
    async checkAuth() {
        const res = await this.api('/api/auth/me');
        if (res.ok && res.data) {
            this.setAuthenticated(res.data.user, res.data.business);
        } else {
            this.setUnauthenticated();
        }
    },

    setAuthenticated(user, business) {
        this.user = user;
        this.business = business;

        document.getElementById('authSection').classList.add('d-none');
        document.getElementById('appSection').classList.remove('d-none');

        document.getElementById('navUserName').textContent = user.name;
        document.getElementById('navBusinessName').textContent = business.business_name;

        // Carrega categorias em memória para os selects
        this.fetchCategoriesCache();
        this.showPage('dashboard');
    },

    setUnauthenticated() {
        this.user = null;
        this.business = null;
        document.getElementById('appSection').classList.add('d-none');
        document.getElementById('authSection').classList.remove('d-none');
    },

    async login(email, password) {
        const res = await this.api('/api/auth/login', {
            method: 'POST',
            body: { email, password }
        });

        if (res.ok && res.data) {
            this.toast('Login realizado com sucesso!', 'success');
            this.setAuthenticated(res.data.user, res.data.business);
        } else {
            this.toast(res.message || 'Erro ao realizar login.', 'danger');
        }
    },

    async register(payload) {
        const res = await this.api('/api/auth/register', {
            method: 'POST',
            body: payload
        });

        if (res.ok && res.data) {
            this.toast('Conta criada com sucesso! Bem-vindo.', 'success');
            this.setAuthenticated(res.data.user, res.data.business);
        } else {
            let msg = res.message || 'Erro ao criar conta.';
            if (res.errors) {
                const firstKey = Object.keys(res.errors)[0];
                msg = res.errors[firstKey][0];
            }
            this.toast(msg, 'danger');
        }
    },

    async logout() {
        await this.api('/api/auth/logout', { method: 'POST' });
        this.toast('Sessão encerrada.', 'info');
        this.setUnauthenticated();
    },

    // ----------------------------------------------------
    // Navegação de Telas
    // ----------------------------------------------------
    showPage(pageName) {
        this.currentPage = pageName;

        document.querySelectorAll('.page-content').forEach(p => p.classList.add('d-none'));
        document.querySelectorAll('.navbar-nav .nav-link').forEach(l => l.classList.remove('active'));

        if (pageName === 'dashboard') {
            document.getElementById('pageDashboard').classList.remove('d-none');
            document.getElementById('navLinkDashboard').classList.add('active');
            this.loadDashboard();
        } else if (pageName === 'transactions') {
            document.getElementById('pageTransactions').classList.remove('d-none');
            document.getElementById('navLinkTransactions').classList.add('active');
            this.loadTransactions(1);
        } else if (pageName === 'categories') {
            document.getElementById('pageCategories').classList.remove('d-none');
            document.getElementById('navLinkCategories').classList.add('active');
            this.loadCategories();
        } else if (pageName === 'business') {
            document.getElementById('pageBusiness').classList.remove('d-none');
            document.getElementById('navLinkBusiness').classList.add('active');
            this.loadBusiness();
        }
    },

    // ----------------------------------------------------
    // Dashboard
    // ----------------------------------------------------
    async loadDashboard() {
        const month = document.getElementById('dashMonth').value;
        const year = document.getElementById('dashYear').value;

        const res = await this.api(`/api/dashboard?month=${month}&year=${year}`);
        if (!res.ok || !res.data) return;

        const d = res.data;
        document.getElementById('kpiIncome').textContent = this.formatCurrency(d.total_income);
        document.getElementById('kpiExpenses').textContent = this.formatCurrency(d.total_expenses);
        
        const balanceEl = document.getElementById('kpiBalance');
        balanceEl.textContent = this.formatCurrency(d.balance);
        balanceEl.className = d.balance >= 0 ? 'fw-bold text-primary mt-1 mb-0' : 'fw-bold text-danger mt-1 mb-0';

        document.getElementById('kpiPendingText').textContent = 
            `+${this.formatCurrency(d.pending_income)} / -${this.formatCurrency(d.pending_expenses)}`;
        
        const overdueBadge = document.getElementById('kpiOverdueBadge');
        overdueBadge.textContent = `${d.overdue_transactions} em atraso`;
        overdueBadge.className = d.overdue_transactions > 0 ? 'badge bg-danger' : 'badge bg-secondary';

        // Renderização dos Gráficos com Chart.js
        this.renderCharts(d);

        // Acompanhamento do Limite Anual MEI (DASN-SIMEI)
        this.loadAnnualReport(year);
    },

    async loadAnnualReport(year) {
        const res = await this.api(`/api/dashboard/annual?year=${year}`);
        if (!res.ok || !res.data) return;

        const a = res.data;
        const textEl = document.getElementById('annualRevenueText');
        const badgeEl = document.getElementById('annualStatusBadge');
        const barEl = document.getElementById('annualProgressBar');

        textEl.textContent = `Faturamento acumulado: ${this.formatCurrency(a.total_annual_revenue)} de ${this.formatCurrency(a.annual_limit)} (${a.percentage_used}%)`;

        barEl.style.width = `${Math.min(100, a.percentage_used)}%`;
        if (a.status === 'danger') {
            badgeEl.className = 'badge bg-danger';
            badgeEl.textContent = 'Limite Excedido!';
            barEl.className = 'progress-bar bg-danger';
        } else if (a.status === 'warning') {
            badgeEl.className = 'badge bg-warning text-dark';
            badgeEl.textContent = 'Atenção: Próximo ao Limite';
            barEl.className = 'progress-bar bg-warning';
        } else {
            badgeEl.className = 'badge bg-success';
            badgeEl.textContent = 'Dentro do Limite';
            barEl.className = 'progress-bar bg-success';
        }
    },

    renderCharts(data) {
        // 1. Gráfico de Barras: Comparativo Receitas vs Despesas
        const ctxBar = document.getElementById('chartIncomeExpense').getContext('2d');
        if (this.chartBar) this.chartBar.destroy();

        this.chartBar = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Receitas Pagas', 'Despesas Pagas', 'Saldo Líquido'],
                datasets: [{
                    label: 'Valores em R$',
                    data: [data.total_income, data.total_expenses, data.balance],
                    backgroundColor: ['#198754', '#dc3545', data.balance >= 0 ? '#0d6efd' : '#fd7e14'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` R$ ${Number(ctx.raw).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (v) => 'R$ ' + v
                        }
                    }
                }
            }
        });

        // 2. Gráfico de Rosca: Despesas por Categoria
        const ctxPie = document.getElementById('chartCategories').getContext('2d');
        if (this.chartPie) this.chartPie.destroy();

        const catLabels = data.expenses_by_category.map(c => c.category_name);
        const catValues = data.expenses_by_category.map(c => c.total);

        if (catValues.length === 0) {
            catLabels.push('Sem despesas registradas');
            catValues.push(1);
        }

        this.chartPie = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catValues,
                    backgroundColor: [
                        '#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545',
                        '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` R$ ${Number(ctx.raw).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`
                        }
                    }
                }
            }
        });
    },

    // ----------------------------------------------------
    // Movimentações Financeiras
    // ----------------------------------------------------
    async loadTransactions(page = 1) {
        this.currentTxPage = page;
        const search = document.getElementById('filterSearch').value;
        const type = document.getElementById('filterType').value;
        const status = document.getElementById('filterStatus').value;
        const start_date = document.getElementById('filterStartDate').value;
        const end_date = document.getElementById('filterEndDate').value;

        const params = new URLSearchParams({
            page: String(page),
            limit: '10'
        });

        if (search) params.append('search', search);
        if (type) params.append('type', type);
        if (status) params.append('status', status);
        if (start_date) params.append('start_date', start_date);
        if (end_date) params.append('end_date', end_date);

        const res = await this.api(`/api/transactions?${params.toString()}`);
        const tbody = document.getElementById('transactionsTableBody');

        if (!res.ok || !res.data) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Erro ao carregar movimentações.</td></tr>`;
            return;
        }

        const list = res.data.transactions || [];
        const pag = res.data.pagination || { total: 0, page: 1, total_pages: 1 };

        if (list.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Nenhuma movimentação encontrada para os filtros aplicados.</td></tr>`;
        } else {
            tbody.innerHTML = list.map(tx => {
                const typeBadge = tx.type === 'income' 
                    ? `<span class="badge badge-income"><i class="bi bi-arrow-up me-1"></i>Receita</span>`
                    : `<span class="badge badge-expense"><i class="bi bi-arrow-down me-1"></i>Despesa</span>`;

                let statusBadge = '';
                if (tx.status === 'paid') {
                    statusBadge = `<span class="badge badge-paid"><i class="bi bi-check2 me-1"></i>Pago</span>`;
                } else if (tx.status === 'pending') {
                    statusBadge = `<span class="badge badge-pending"><i class="bi bi-clock me-1"></i>Pendente</span>`;
                } else {
                    statusBadge = `<span class="badge badge-cancelled">Cancelado</span>`;
                }

                const quickPayBtn = tx.status === 'pending'
                    ? `<button class="btn btn-sm btn-outline-success me-1" title="Marcar como Pago" onclick="app.quickMarkPaid(${tx.id}, ${tx.version})">
                         <i class="bi bi-check-lg"></i>
                       </button>`
                    : '';

                return `
                    <tr>
                        <td class="fw-semibold">${this.formatDate(tx.due_date)}</td>
                        <td>${this.escapeHtml(tx.description)}</td>
                        <td><span class="badge bg-light text-dark border">${this.escapeHtml(tx.category_name)}</span></td>
                        <td>${typeBadge}</td>
                        <td class="fw-bold ${tx.type === 'income' ? 'text-success' : 'text-danger'}">
                            ${tx.type === 'income' ? '+' : '-'} ${this.formatCurrency(tx.amount)}
                        </td>
                        <td>${statusBadge}</td>
                        <td class="text-end">
                            ${quickPayBtn}
                            <button class="btn btn-sm btn-outline-secondary me-1" title="Editar" onclick="app.editTransaction(${tx.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="app.confirmDeleteTx(${tx.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Atualiza controles de paginação
        document.getElementById('txPaginationInfo').textContent = 
            `Exibindo página ${pag.page} de ${pag.total_pages} (${pag.total} registros no total)`;

        const pagControls = document.getElementById('txPaginationControls');
        pagControls.innerHTML = `
            <button class="btn btn-outline-secondary ${pag.page <= 1 ? 'disabled' : ''}" onclick="app.loadTransactions(${pag.page - 1})">
                <i class="bi bi-chevron-left"></i> Anterior
            </button>
            <button class="btn btn-outline-secondary ${pag.page >= pag.total_pages ? 'disabled' : ''}" onclick="app.loadTransactions(${pag.page + 1})">
                Próxima <i class="bi bi-chevron-right"></i>
            </button>
        `;
    },

    async fetchCategoriesCache() {
        const res = await this.api('/api/categories?limit=100');
        if (res.ok && res.data) {
            this.categories = res.data.categories || [];
        }
    },

    openTransactionModal(type = 'income', editTx = null) {
        document.getElementById('formTransaction').reset();
        document.getElementById('txId').value = editTx ? editTx.id : '';
        document.getElementById('txVersion').value = editTx ? editTx.version : '1';

        const modalTitle = document.getElementById('modalTxTitle');
        const modalHeader = document.getElementById('modalTxHeader');

        if (editTx) {
            modalTitle.textContent = 'Editar Movimentação';
            modalHeader.className = editTx.type === 'income' ? 'modal-header bg-success text-white' : 'modal-header bg-danger text-white';
            document.getElementById(editTx.type === 'income' ? 'txTypeIncome' : 'txTypeExpense').checked = true;
            document.getElementById('txDescription').value = editTx.description;
            document.getElementById('txAmount').value = Number(editTx.amount).toFixed(2);
            document.getElementById('txDueDate').value = editTx.due_date;
            document.getElementById('txStatus').value = editTx.status;
            if (editTx.paid_at) {
                document.getElementById('txPaidAt').value = editTx.paid_at.substring(0, 16);
            }
        } else {
            modalTitle.textContent = type === 'income' ? 'Nova Receita' : 'Nova Despesa';
            modalHeader.className = type === 'income' ? 'modal-header bg-success text-white' : 'modal-header bg-danger text-white';
            document.getElementById(type === 'income' ? 'txTypeIncome' : 'txTypeExpense').checked = true;
            document.getElementById('txDueDate').value = new Date().toISOString().substring(0, 10);
            document.getElementById('txStatus').value = 'pending';
        }

        this.onTxTypeChanged();
        if (editTx) {
            document.getElementById('txCategory').value = String(editTx.category_id);
        }
        this.onTxStatusChanged();
        this.modalTx.show();
    },

    onTxTypeChanged() {
        const type = document.querySelector('input[name="txType"]:checked')?.value || 'income';
        const modalHeader = document.getElementById('modalTxHeader');
        modalHeader.className = type === 'income' ? 'modal-header bg-success text-white' : 'modal-header bg-danger text-white';

        const catSelect = document.getElementById('txCategory');
        const filtered = this.categories.filter(c => c.type === type);

        catSelect.innerHTML = `<option value="">Selecione uma categoria...</option>` +
            filtered.map(c => `<option value="${c.id}">${this.escapeHtml(c.name)}</option>`).join('');
    },

    onTxStatusChanged() {
        const status = document.getElementById('txStatus').value;
        const groupPaidAt = document.getElementById('groupPaidAt');
        if (status === 'paid') {
            groupPaidAt.classList.remove('d-none');
            if (!document.getElementById('txPaidAt').value) {
                document.getElementById('txPaidAt').value = new Date().toISOString().substring(0, 16);
            }
        } else {
            groupPaidAt.classList.add('d-none');
            document.getElementById('txPaidAt').value = '';
        }
    },

    async saveTransaction() {
        const id = document.getElementById('txId').value;
        const version = parseInt(document.getElementById('txVersion').value || '1', 10);
        const type = document.querySelector('input[name="txType"]:checked').value;
        const description = document.getElementById('txDescription').value;
        const amount = parseFloat(document.getElementById('txAmount').value);
        const due_date = document.getElementById('txDueDate').value;
        const category_id = parseInt(document.getElementById('txCategory').value, 10);
        const status = document.getElementById('txStatus').value;
        const paid_at = document.getElementById('txPaidAt').value ? document.getElementById('txPaidAt').value.replace('T', ' ') + ':00' : null;

        const payload = { type, description, amount, due_date, category_id, status, paid_at, version };

        const url = id ? `/api/transactions/${id}` : '/api/transactions';
        const method = id ? 'PUT' : 'POST';

        const res = await this.api(url, { method, body: payload });

        if (res.ok) {
            this.toast(id ? 'Movimentação atualizada com sucesso!' : 'Movimentação criada com sucesso!', 'success');
            this.modalTx.hide();
            this.loadTransactions(this.currentTxPage);
            if (this.currentPage === 'dashboard') this.loadDashboard();
        } else {
            let msg = res.message || 'Erro ao salvar movimentação.';
            if (res.errors) {
                const firstKey = Object.keys(res.errors)[0];
                msg = res.errors[firstKey][0];
            }
            this.toast(msg, 'danger');
        }
    },

    async editTransaction(id) {
        const res = await this.api(`/api/transactions/${id}`);
        if (res.ok && res.data) {
            this.openTransactionModal(res.data.type, res.data);
        } else {
            this.toast('Erro ao carregar dados da movimentação.', 'danger');
        }
    },

    async quickMarkPaid(id, currentVersion) {
        const res = await this.api(`/api/transactions/${id}/status`, {
            method: 'PATCH',
            body: { status: 'paid', version: currentVersion }
        });

        if (res.ok) {
            this.toast('Movimentação marcada como paga!', 'success');
            this.loadTransactions(this.currentTxPage);
            if (this.currentPage === 'dashboard') this.loadDashboard();
        } else {
            this.toast(res.message || 'Erro ao atualizar status.', 'danger');
        }
    },

    confirmDeleteTx(id) {
        document.getElementById('deleteConfirmMessage').textContent = 'Tem certeza de que deseja excluir esta movimentação?';
        this.pendingDeleteAction = async () => {
            const res = await this.api(`/api/transactions/${id}`, { method: 'DELETE' });
            if (res.ok) {
                this.toast('Movimentação excluída com sucesso.', 'success');
                this.loadTransactions(this.currentTxPage);
                if (this.currentPage === 'dashboard') this.loadDashboard();
            } else {
                this.toast(res.message || 'Erro ao excluir.', 'danger');
            }
        };
        this.modalDel.show();
    },

    // ----------------------------------------------------
    // Categorias
    // ----------------------------------------------------
    async loadCategories() {
        const typeFilter = document.querySelector('input[name="catTypeFilter"]:checked')?.value || '';
        const url = typeFilter ? `/api/categories?type=${typeFilter}&limit=100` : '/api/categories?limit=100';

        const res = await this.api(url);
        const tbody = document.getElementById('categoriesTableBody');

        if (!res.ok || !res.data) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger">Erro ao carregar categorias.</td></tr>`;
            return;
        }

        const list = res.data.categories || [];
        this.categories = list;

        if (list.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">Nenhuma categoria cadastrada.</td></tr>`;
            return;
        }

        tbody.innerHTML = list.map(cat => {
            const typeBadge = cat.type === 'income'
                ? `<span class="badge badge-income"><i class="bi bi-arrow-up me-1"></i>Receita</span>`
                : `<span class="badge badge-expense"><i class="bi bi-arrow-down me-1"></i>Despesa</span>`;

            return `
                <tr>
                    <td class="fw-bold">${this.escapeHtml(cat.name)}</td>
                    <td>${typeBadge}</td>
                    <td class="text-muted small">${this.formatDate(cat.created_at)}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary me-1" title="Editar" onclick="app.editCategory(${cat.id}, '${this.escapeHtml(cat.name)}', '${cat.type}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="app.confirmDeleteCategory(${cat.id}, '${this.escapeHtml(cat.name)}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    },

    openCategoryModal(editCat = null) {
        document.getElementById('formCategory').reset();
        document.getElementById('catId').value = editCat ? editCat.id : '';
        document.getElementById('modalCatTitle').textContent = editCat ? 'Editar Categoria' : 'Nova Categoria';

        if (editCat) {
            document.getElementById('catName').value = editCat.name;
            document.getElementById('catType').value = editCat.type;
        }

        this.modalCat.show();
    },

    editCategory(id, name, type) {
        this.openCategoryModal({ id, name, type });
    },

    async saveCategory() {
        const id = document.getElementById('catId').value;
        const name = document.getElementById('catName').value;
        const type = document.getElementById('catType').value;

        const url = id ? `/api/categories/${id}` : '/api/categories';
        const method = id ? 'PUT' : 'POST';

        const res = await this.api(url, { method, body: { name, type } });

        if (res.ok) {
            this.toast(id ? 'Categoria atualizada!' : 'Categoria cadastrada com sucesso!', 'success');
            this.modalCat.hide();
            await this.fetchCategoriesCache();
            this.loadCategories();
        } else {
            let msg = res.message || 'Erro ao salvar categoria.';
            if (res.errors) {
                const firstKey = Object.keys(res.errors)[0];
                msg = res.errors[firstKey][0];
            }
            this.toast(msg, 'danger');
        }
    },

    confirmDeleteCategory(id, name) {
        document.getElementById('deleteConfirmMessage').innerHTML = 
            `Tem certeza de que deseja excluir a categoria <strong>${name}</strong>?`;
        
        this.pendingDeleteAction = async () => {
            const res = await this.api(`/api/categories/${id}`, { method: 'DELETE' });
            if (res.ok) {
                this.toast('Categoria excluída com sucesso.', 'success');
                await this.fetchCategoriesCache();
                this.loadCategories();
            } else {
                this.toast(res.message || 'Erro ao excluir categoria.', 'danger');
            }
        };
        this.modalDel.show();
    },

    // ----------------------------------------------------
    // Perfil Empresarial
    // ----------------------------------------------------
    async loadBusiness() {
        const res = await this.api('/api/business');
        if (res.ok && res.data) {
            document.getElementById('bizName').value = res.data.business_name || '';
            document.getElementById('bizCnpj').value = res.data.cnpj || '';
        }
    },

    async saveBusiness() {
        const business_name = document.getElementById('bizName').value;
        const cnpj = document.getElementById('bizCnpj').value;

        const res = await this.api('/api/business', {
            method: 'PATCH',
            body: { business_name, cnpj }
        });

        if (res.ok && res.data) {
            this.toast('Dados da empresa atualizados com sucesso!', 'success');
            this.business = res.data;
            document.getElementById('navBusinessName').textContent = res.data.business_name;
        } else {
            let msg = res.message || 'Erro ao atualizar dados.';
            if (res.errors) {
                const firstKey = Object.keys(res.errors)[0];
                msg = res.errors[firstKey][0];
            }
            this.toast(msg, 'danger');
        }
    },

    // ----------------------------------------------------
    // Utilitários de UI
    // ----------------------------------------------------
    toast(message, variant = 'primary') {
        const toastEl = document.getElementById('appToast');
        const msgEl = document.getElementById('toastMessage');

        toastEl.className = `toast align-items-center text-white bg-${variant} border-0`;
        msgEl.textContent = message;

        const t = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 });
        t.show();
    },

    showLoading(show) {
        const el = document.getElementById('loadingOverlay');
        if (show) el.classList.remove('d-none');
        else el.classList.add('d-none');
    },

    formatCurrency(val) {
        const num = Number(val) || 0;
        return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    },

    formatDate(str) {
        if (!str) return '-';
        const parts = str.substring(0, 10).split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return str;
    },

    escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    app.init();
});
