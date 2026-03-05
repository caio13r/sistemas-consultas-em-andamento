# 📋 Gestão de Carteiras Policarbonatos - Documentação Completa

## 📖 Visão Geral

O sistema de **Gestão de Carteiras Profissionais** consiste em duas páginas principais:

1. **`consultaIdentidade-24.php`** - Página de gestão geral com visão de período
2. **`consultaIdentidade-25.php`** - **NOVA!** Estabilização Diária com foco em despacho rápido

---

## 🚀 NOVA PÁGINA: Estabilização Diária (CI25)

### **Acesso:**
- URL: `/consulta-identidade-25` ou `/estabilizacao-diaria`
- Foco: **Despacho diário de CPFs** com prioridade para CD (Cirurgião Dentista)

### **Objetivo:**
Permitir que todo dia você entre, visualize os CPFs que acabaram de ser aprovados, e despache imediatamente - especialmente os **CD** que têm prazo de 5 dias para a carteira chegar.

---

## 🎯 Funcionalidades da Estabilização Diária

### **TAB 1: HOJE** (Padrão)

#### Cards de Estatísticas em Tempo Real:
| Card | Descrição |
|------|-----------|
| 📥 **Entraram Hoje** | CPFs com foto aprovada que entraram hoje |
| ✈️ **Enviados Hoje** | CPFs já despachados para impressão |
| ⏳ **Pendentes Hoje** | CPFs aguardando despacho |
| 🦷 **CD Pendentes** | Cirurgiões Dentistas pendentes (PRIORIDADE) |
| ⚠️ **Pendentes Ontem** | CPFs do dia anterior não despachados |
| 🗺️ **UFs Ativas** | Quantidade de estados com CPFs hoje |

#### Botões de Ação Rápida:
1. **🦷 Enviar CD de Hoje** - Prioridade máxima! Envia todos os Cirurgiões Dentistas
2. **✈️ Enviar Tudo de Hoje** - Envia todos os CPFs pendentes do dia
3. **⏰ Enviar Pendentes de Ontem** - Limpa atrasos do dia anterior
4. **🔄 Atualizar Dados** - Recarrega estatísticas

#### Filtro por Categoria:
- **Todos** - Mostra todos os CPFs
- **CD** - Apenas Cirurgiões Dentistas (destaque roxo)
- **Outros** - TPD, ASB, TSB, APD

#### Tabela de CPFs:
- Seleção múltipla com checkbox
- Envio individual ou em lote
- Destaque visual para CD
- Ordenação por data de entrada

---

### **TAB 2: POR DATA**

#### Date Picker:
- Selecione qualquer data específica
- Visualize CPFs daquela data
- Resumo: Entraram, Enviados, Pendentes, CD Pendentes

#### Ações:
- **Enviar CD desta Data** - Prioridade para CD
- **Enviar Tudo desta Data** - Todos os pendentes

---

### **TAB 3: BACKLOG** (Atrasos)

#### Resumo de Atrasos:
| Período | Urgência | Ação |
|---------|----------|------|
| 2 dias atrás (ontem) | 🔵 Info | Botão Enviar |
| 3 dias atrás | 🟡 Atenção | Botão Enviar |
| 5+ dias atrás | 🔴 Crítico! | Botão Enviar |

#### UFs com Maior Atraso:
- Tabela ordenada por quantidade de pendentes
- Data do registro mais antigo
- Dias de atraso
- Botão para enviar backlog da UF

#### Ações:
- **Enviar TODO o Backlog** - Limpa todos os atrasos de uma vez

---

### **TAB 4: POR UF**

#### Grid de UFs:
- Cards clicáveis para cada estado
- Contador de CPFs disponíveis
- Seleção visual

#### Ao Selecionar UF:
- Tabela com todos os CPFs daquela UF
- Botão **Enviar CD** - Prioridade
- Botão **Enviar Todos**

---

### **TAB 5: HISTÓRICO**

#### Filtros:
- Período (date range)
- UF específica

#### Resumo do Período:
- Total de envios
- CPFs enviados
- UFs atendidas

#### Lista de Envios:
- Data/hora
- Operador
- Descrição
- Quantidade
- Sucesso/Erros

#### Exportar:
- Botão para exportar CSV

---

## 📊 Fluxo de Trabalho Diário Recomendado

### **Manhã (8h-9h):**
```
1. Acesse /estabilizacao-diaria
2. Verifique se há ALERTA de pendentes de ontem
3. Se houver, clique "Despachar Agora"
4. Vá para aba "Hoje"
5. Clique "Enviar CD de Hoje" (prioridade máxima)
6. Clique "Enviar Tudo de Hoje"
```

### **Tarde (14h-15h):**
```
1. Acesse /estabilizacao-diaria
2. Atualize os dados
3. Verifique novos CPFs que entraram
4. Envie CD primeiro, depois os demais
```

### **Final do Dia (17h-18h):**
```
1. Acesse /estabilizacao-diaria
2. Verifique aba "Backlog"
3. Limpe qualquer atraso restante
4. Verifique histórico do dia
```

---

## 🔧 Rotas Disponíveis

| Rota | Página | Descrição |
|------|--------|-----------|
| `/consulta-identidade-24` | CI24 | Gestão Geral de Carteiras |
| `/gestao-carteiras` | CI24 | Alias para CI24 |
| `/consulta-identidade-25` | CI25 | Estabilização Diária |
| `/estabilizacao-diaria` | CI25 | Alias para CI25 |
| `/api-cpf-aprovados` | Proxy | API para comunicação com servidor Identity |

---

## 🎨 Interface Visual

### **Cores e Significados:**
- 🔵 **Azul** - Informação geral, primário
- 🟢 **Verde** - Sucesso, enviado
- 🟡 **Amarelo/Laranja** - Atenção, pendente
- 🔴 **Vermelho** - Urgente, erro, atraso
- 🟣 **Roxo** - CD (Cirurgião Dentista) - PRIORIDADE

### **Destaques Visuais:**
- Cards com gradientes
- Badges coloridos por status
- Alerta pulsante para urgências
- Destaque especial para CD

---

## 📈 Métricas de Sucesso

### **KPIs Diários:**
1. **Taxa de Despacho Diário** = Enviados / Entraram × 100%
   - Meta: > 95%

2. **Tempo de Resposta CD** = Tempo entre aprovação e envio para CD
   - Meta: < 4 horas

3. **Backlog Zero** = Pendentes ao final do dia
   - Meta: 0 CPFs pendentes

4. **Cobertura de UFs** = UFs atendidas / UFs com CPFs
   - Meta: 100%

---

## 🔄 Processo Completo: Da Inscrição ao Despacho

```
ETAPA 1: Inscrição no CRO
    ↓ (profissional se inscreve)
    
ETAPA 2: Sincronização (4x/dia)
    ↓ (dados copiados para banco local)
    
ETAPA 3: Envio da Foto
    ↓ (profissional envia foto no sistema do CRO)
    
ETAPA 4: Aprovação da Foto pelo CRO
    ↓ (CRO analisa e aprova)
    
ETAPA 5: Sync CPFs Aprovados (3x/dia)
    ↓ (API busca CPFs aprovados de cada UF)
    
ETAPA 6: Validações (1h)
    ↓ (sistema valida dados completos)
    
ETAPA 7: Identity Ready
    ↓ (CPF disponível para despacho)
    
ETAPA 8: DESPACHO (Estabilização Diária)
    ↓ (operador envia para impressão)
    
ETAPA 9: Impressão e Envio
    ↓ (carteira impressa e enviada)
    
ETAPA 10: Entrega (5 dias úteis)
    ✅ Profissional recebe a carteira
```

---

## ⚡ Dicas para Operação Eficiente

### **Priorize CD:**
Os Cirurgiões Dentistas têm urgência maior pois precisam da carteira para exercer a profissão. Sempre envie CD primeiro!

### **Limpe Backlog:**
Não deixe CPFs acumularem. O prazo de 5 dias começa a contar da aprovação da foto.

### **Use Filtros:**
Se precisar focar em uma UF específica ou categoria, use os filtros para otimizar o trabalho.

### **Monitore Alertas:**
O sistema mostra alerta vermelho pulsante quando há pendentes de ontem. Não ignore!

### **Verifique Histórico:**
Use o histórico para acompanhar sua produtividade e identificar padrões.

---

## 🛠️ Arquitetura Técnica

### **Arquivos:**
- `consultaIdentidade-24.php` - Página de gestão geral
- `consultaIdentidade-25.php` - Estabilização diária (NOVO)
- `api-cpf-aprovados-proxy.php` - Proxy para API Identity

### **Tecnologias:**
- PHP 7.4+
- Bootstrap 5
- jQuery 3.6
- DataTables
- Flatpickr (date picker)
- Font Awesome 6

### **API Endpoints:**
- `stats` - Estatísticas gerais
- `disponiveis` - Lista CPFs disponíveis
- `detalhes` - Detalhes de um CPF
- `inserir-lote` - Enviar CPFs para impressão
- `autorizar-uf` - Autorizar toda uma UF

### **Armazenamento Local:**
- `localStorage` - Histórico de envios (últimos 100 registros)

---

## 📝 Changelog

### **v2.0 (2025-01-15)** - Estabilização Diária
- ✅ Nova página CI25 com foco em despacho diário
- ✅ Cards de estatísticas em tempo real
- ✅ Botões de ação rápida (CD, Todos, Ontem)
- ✅ Filtro por categoria (CD, Outros)
- ✅ Date picker para data específica
- ✅ Painel de backlog com urgências
- ✅ Indicadores por UF
- ✅ Histórico de envios com exportação
- ✅ Alerta visual para pendentes de ontem
- ✅ Destaque especial para CD

### **v1.0** - Gestão de Carteiras
- Visão geral com estatísticas
- Grid de UFs
- Tabela de CPFs por dia
- Inserção manual em lote
- Autorização por UF
- Consulta individual de CPF

---

**Última atualização:** 2025-01-15  
**Versão do documento:** 2.0  
**Autor:** Sistema de Consultas - CFO
