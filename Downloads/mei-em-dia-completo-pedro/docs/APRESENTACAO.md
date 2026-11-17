# Roteiro de Apresentação — MEI em Dia
**Projeto Final de Programação Web II**

---

## 🎯 Slide 1: Título e Visão Geral
- **Nome do Projeto:** MEI em Dia
- **Slogan:** Gestão financeira simplificada para Microempreendedores Individuais
- **Público-alvo:** MEIs, autônomos e pequenos prestadores de serviços
- **Equipe/Autor:** Ramon Braga

---

## 💡 Slide 2: O Problema
- MEIs sofrem com desorganização financeira (anotações em cadernos, WhatsApp e planilhas confusas).
- Dificuldade para saber o lucro real do mês e risco de atrasar contas por falta de lembrete visual.
- Risco fiscal de ultrapassar o limite anual de faturamento (R$ 81.000,00) sem perceber.

---

## ✨ Slide 3: A Solução
- Aplicação web responsiva, rápida e focada nas necessidades reais do MEI.
- Dashboard inteligente com indicadores em tempo real (Receitas, Despesas, Saldo Líquido e Contas Atrasadas).
- Gráficos visuais comparativos e distribuição de gastos por categoria.
- Controle de receitas e despesas com vencimentos, baixa rápida em 1 clique e exportação de extrato.

---

## 🛠️ Slide 4: Arquitetura e Stack Tecnológica
- **Backend:** PHP 8.2+ orientado a objetos, arquitetura MVC Monolítica Modular, PDO, autenticação por sessão e proteção CSRF.
- **Banco de Dados:** MySQL 8+ com integridade referencial, foreign keys compostas, CHECK constraints e concorrência otimista por versão.
- **Frontend:** HTML5, CSS3, JavaScript e Bootstrap 5.3 + Chart.js para gráficos dinâmicos.
- **Qualidade de Código:** 79 testes automatizados com PHPUnit 11 e análise estática Nível 5 com PHPStan.

---

## 🔒 Slide 5: Engenharia e Segurança (OWASP)
1. **Prevenção a SQL Injection:** 100% das consultas via Prepared Statements (`EMULATE_PREPARES = false`).
2. **Prevenção a XSS:** Escape estrito de caracteres em todo o fluxo de apresentação.
3. **Prevenção a CSRF:** Token criptográfico verificado por middleware em todas as ações de mutação.
4. **Isolamento Multi-tenant (Anti-IDOR):** Nenhuma empresa consegue acessar ou modificar dados de outra empresa.
5. **Concorrência Otimista:** Detecção de conflitos de atualização simultânea com HTTP 409.

---

## 👥 Slide 6: Feedback de Usuário Real e Melhorias
- O sistema foi testado por um MEI prestador de serviços de TI.
- **Melhoria 1 Implementada:** Exportação do extrato de movimentações em formato CSV para facilitar o envio à contabilidade.
- **Melhoria 2 Implementada:** Monitoramento em tempo real do faturamento acumulado no ano em relação ao teto do MEI (R$ 81.000,00 do DASN-SIMEI) com barra de progresso no Dashboard.

---

## 🖥️ Slide 7: Demonstração Prática (Roteiro ao Vivo)
1. **Login:** Acessar com a conta de demonstração (`pedro@email.com` / `SenhaSegura123`).
2. **Dashboard:** Mostrar os KPIs do mês atual (Novembro/2026), saldo líquido, contas em atraso e gráficos.
3. **Limite MEI:** Mostrar a barra de progresso do teto anual do MEI.
4. **Movimentações:**
   - Cadastrar uma nova receita ou despesa.
   - Usar o botão rápido para marcar uma conta pendente como paga.
   - Filtrar por status ou buscar por descrição.
   - Clicar em "Exportar CSV" e abrir o arquivo gerado.
5. **Categorias:** Mostrar o bloqueio ao tentar excluir uma categoria com movimentações vinculadas.
6. **Empresa:** Atualizar Razão Social ou CNPJ.
7. **Testes:** Executar `composer test` no terminal para demonstrar os 79 testes passando com sucesso.

---

## 🏁 Slide 8: Conclusão
- Todos os critérios de aceite das **Tarefas 0, 1, 2, 3, 4, 5, 6 e 7** foram atingidos.
- Sistema pronto para uso, seguro, testado e documentado.
