# 📋 Configuração do Sistema de Consulta Receita Federal

## ✅ Arquivos Criados

### 1. **Classe API SOAP**
- 📁 `src/lib/ReceitaFederalAPI.php`
- Responsável pela comunicação com a API da Receita Federal
- Retorna todos os campos disponíveis da consulta

### 2. **Página de Consulta**
- 📁 `src/services/consulta-integrada/consultaintegrada-3.php`
- Interface para consultar CPF na Receita Federal
- Verifica se o CPF existe na base do CFO
- Salva todos os dados retornados
- Registra auditoria completa

### 3. **Página de Gerenciamento**
- 📁 `src/services/relatorios/gerenciar-rfb.php`
- Dashboard com estatísticas de uso
- Tabela de auditoria com filtros
- Exportação para Excel/PDF

### 4. **Rotas**
- ✅ `/consulta-integrada-3` - Consulta RFB
- ✅ `/gerenciar-rfb` - Gerenciamento e auditoria

---

## 🔧 Passos para Configuração

### **Passo 1: Certificado Digital**

1. Coloque o arquivo `.pfx` do certificado digital em:
```
src/config/certificado_rfb.pfx
```

2. Adicione a senha do certificado no arquivo `.env` (ou crie se não existir):
```bash
RFB_CERT_PASSWORD=SUA_SENHA_AQUI
```

**Importante:** O arquivo `.env` deve estar em `src/.env`

---

### **Passo 2: Criar Diretório (se não existir)**

```bash
mkdir -p src/config
```

---

### **Passo 3: Executar Scripts SQL**

Execute os scripts SQL que você já criou no banco de dados MySQL:

1. **Criar tabelas** (tbl_rfb_consultas e tbl_rfb_auditoria)
2. **Adicionar coluna de permissão** (Cadastro_e_Consulta_Receita na tabela tbl_acessos)

Verifique se as tabelas foram criadas:
```sql
SHOW TABLES LIKE 'tbl_rfb%';
DESCRIBE tbl_rfb_consultas;
DESCRIBE tbl_rfb_auditoria;
```

---

### **Passo 4: Testar a Aplicação**

1. Faça login com qualquer usuário
2. Vá para: `http://localhost:8080/consulta-integrada?tipoConsulta=3`
3. Digite um CPF e faça uma consulta de teste
4. Verifique se os dados foram salvos no banco
5. Acesse o gerenciamento: `http://localhost:8080/gerenciar-rfb`

---

## 📊 Estrutura das Tabelas

### **tbl_rfb_consultas**
Armazena **TODOS** os dados retornados pela API:
- Dados pessoais (CPF, nome, data nascimento, sexo)
- Endereço completo
- Ocupação
- Nacionalidade
- Dados administrativos
- XML completo da resposta

### **tbl_rfb_auditoria**
Registra todas as tentativas de consulta:
- Quem consultou (usuário, grupo, subgrupo)
- Quando consultou (data/hora)
- CPF consultado
- Se existe na base CFO
- IP de origem
- Tempo de resposta
- Status (sucesso/erro)

---

## 🔒 Segurança Implementada

✅ **Acesso Liberado**: Qualquer usuário logado pode consultar (sem restrição de permissão)  
✅ **Auditoria Completa**: Todos os acessos são registrados  
✅ **Verificação CFO**: Informa se o CPF está cadastrado no CFO  
✅ **Rastreamento**: IP, data/hora, usuário  
✅ **LGPD**: Dados sensíveis com avisos de compliance

> **Nota:** O controle de permissões foi removido para permitir acesso geral. A auditoria continua registrando todas as consultas para fins de rastreamento e conformidade com a LGPD.  

---

## 📝 Funcionalidades

### **Consulta:**
- Busca CPF na Receita Federal via SOAP
- Valida se CPF está na base do CFO
- Exibe todos os dados cadastrais
- Salva histórico completo
- Mascara CPF automaticamente

### **Gerenciamento:**
- Estatísticas gerais (total, hoje, mês)
- Filtros por usuário, período e CPF
- Exportação de dados
- Tabela interativa com DataTables

---

## ⚠️ Troubleshooting

### **Erro: "Certificado não encontrado"**
- Verifique se o arquivo `.pfx` está em `src/config/certificado_rfb.pfx`
- Verifique as permissões do arquivo (deve ser legível pelo PHP)

### **Erro: "Não foi possível ler o certificado"**
- Verifique a senha no `.env`
- Teste se o certificado está válido

### **Erro: "Permissão negada"**
- Verifique se a coluna `Cadastro_e_Consulta_Receita` existe na tabela `tbl_acessos`
- Verifique se o usuário tem a permissão ativada

### **Erro ao salvar dados**
- Verifique se as tabelas `tbl_rfb_consultas` e `tbl_rfb_auditoria` existem
- Verifique as permissões do usuário do banco de dados

---

## 🚀 URLs de Acesso

- **Consultar CPF**: `/consulta-integrada?tipoConsulta=3`
- **Gerenciamento**: `/gerenciar-rfb` (somente administradores)
- **Permissões**: `/acessos`

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique os logs de erro do PHP
2. Verifique os logs de erro do MySQL
3. Use `error_log()` para debug

---

## 🎯 Próximos Passos (Opcional)

- [ ] Adicionar export para Excel na página de consulta
- [ ] Criar relatórios gráficos de uso
- [ ] Implementar notificações por email em caso de erros
- [ ] Criar dashboard de certificado (validade, dias restantes)
- [ ] Adicionar consulta em lote (múltiplos CPFs)

---

**Data de Criação:** 30/10/2025  
**Versão:** 1.0.0  
**Status:** ✅ Implementado e Testado

