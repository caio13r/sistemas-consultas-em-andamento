# ✅ Configuração da Conexão com RFB - CONCLUÍDA

## Problema Identificado
O erro `Certificado digital não encontrado em: /var/www/html/src/lib/../config/certificado_rfb.pfx` ocorria porque:

1. O código buscava um arquivo chamado `certificado_rfb.pfx`
2. O arquivo real se chama `CFOORGBR.pfx` e já estava no diretório correto

## Alterações Realizadas

### 1. Arquivo: `src/lib/ReceitaFederalAPI.php`

**Alterações:**
- ✅ Atualizado caminho do certificado de `certificado_rfb.pfx` para `CFOORGBR.pfx`
- ✅ Adicionada função `loadEnv()` para carregar variáveis do arquivo `.env`
- ✅ A senha do certificado (`RFB_CERT_PASSWORD=CFO166`) já está configurada em `src/.env`

**Linhas modificadas:**
- Linha 29: Caminho do certificado atualizado para `CFOORGBR.pfx`
- Linha 23: Adicionada chamada para `$this->loadEnv()` no construtor
- Linhas 224-254: Nova função `loadEnv()` para carregar variáveis de ambiente

### 2. Arquivo: `src/public/test-rfb-conexao.php`

**Alteração:**
- ✅ Atualizado caminho do certificado no script de diagnóstico
- ✅ Adicionada validação do certificado com leitura de informações (titular, validade, etc.)

### 3. Novo Arquivo: `src/public/test-rfb-api.php`

**Criado novo script de teste completo que:**
- ✅ Testa a inicialização da API
- ✅ Verifica a validade do certificado
- ✅ Realiza uma consulta de CPF real na RFB
- ✅ Mostra todos os dados retornados
- ✅ Exibe o XML completo da resposta

## Como Testar

### 1. Teste de Diagnóstico (Conexão Básica)
```
http://seu-servidor/test-rfb-conexao.php
```

Este teste verifica:
- Extensões PHP necessárias (SOAP, OpenSSL)
- Conexão HTTP com o servidor da RFB
- Leitura do certificado PFX
- Validade do certificado

### 2. Teste da API (Consulta Real)
```
http://seu-servidor/test-rfb-api.php
```

Este teste:
- Inicializa a classe `ReceitaFederalAPI`
- Verifica o certificado
- Faz uma consulta real de CPF na RFB
- Mostra todos os dados retornados

### 3. Usar na Aplicação
Acesse a consulta integrada:
```
http://seu-servidor/consulta-integrada?tipoConsulta=3
```

## Configuração do Certificado

### Localização
```
src/config/CFOORGBR.pfx
```

### Senha (já configurada em src/.env)
```env
RFB_CERT_PASSWORD=CFO166
```

### Informações do Certificado (Postman)
- **Host:** acesso.infoconv.receita.fazenda.gov.br:443
- **Arquivo PFX:** Y:/02_Compartilhado/35_Sistemas_SITES/01_SITE_CFO/ssl_cfo_org_br/SERPRO/CFOORGBR.pfx
- **Senha:** CFO166

## Estrutura de Arquivos

```
sistema-consultas/
├── src/
│   ├── .env                                    # Variáveis de ambiente (senha do certificado)
│   ├── config/
│   │   └── CFOORGBR.pfx                       # Certificado digital
│   ├── lib/
│   │   └── ReceitaFederalAPI.php              # ✅ ATUALIZADO
│   ├── public/
│   │   ├── test-rfb-conexao.php               # ✅ ATUALIZADO
│   │   └── test-rfb-api.php                   # ✅ NOVO
│   └── services/
│       └── consulta-integrada/
│           └── consultaintegrada-3.php        # Usa a API
```

## Requisitos do Servidor

- ✅ PHP 7.4+
- ✅ Extensão SOAP
- ✅ Extensão OpenSSL
- ✅ Suporte a TLS 1.2+
- ✅ Permissões de leitura no certificado

## Segurança

- ✅ O certificado `.pfx` está no `.gitignore`
- ✅ A senha está no `.env` (também no `.gitignore`)
- ✅ Todas as consultas são auditadas no banco de dados
- ✅ Logs de erro são registrados automaticamente

## Exemplo de Requisição SOAP

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                  xmlns:cpf="https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/">
   <soapenv:Header/>
   <soapenv:Body>
      <cpf:ConsultarCPFPDEC8789>
         <cpf:ListaDeCPF>03990316184</cpf:ListaDeCPF>
         <cpf:CPFUsuario>03990316184</cpf:CPFUsuario>
      </cpf:ConsultarCPFPDEC8789>
   </soapenv:Body>
</soapenv:Envelope>
```

## Dados Retornados pela API

A API retorna TODOS os campos disponíveis:

- **Identificação:** CPF, Nome, Nome da Mãe, Data de Nascimento, Sexo
- **Situação:** Situação Cadastral, Ano de Óbito
- **Nacionalidade:** País de Nacionalidade, Município de Naturalidade
- **Residência:** Residente no Exterior, País Exterior
- **Ocupação:** Natureza, Ocupação Principal, Exercício
- **Endereço Completo:** Logradouro, Número, Complemento, Bairro, CEP, Município, UF
- **Contato:** DDD, Telefone
- **Administrativo:** Data de Inscrição, Data de Atualização, Unidade Administrativa
- **XML Completo:** Resposta original da RFB

## Troubleshooting

### Erro: "Certificado não encontrado"
- Verifique se o arquivo `CFOORGBR.pfx` está em `src/config/`
- Execute: `ls -la src/config/CFOORGBR.pfx`

### Erro: "Senha do certificado não configurada"
- Verifique se existe `RFB_CERT_PASSWORD=CFO166` em `src/.env`

### Erro: "Não foi possível ler o certificado"
- Verifique as permissões: `chmod 644 src/config/CFOORGBR.pfx`
- Teste a senha do certificado no script de diagnóstico

### Erro SSL/TLS
- Verifique se OpenSSL está instalado: `php -m | grep openssl`
- Teste a conexão: acesse `test-rfb-conexao.php`

## Próximos Passos

1. ✅ Execute o teste de diagnóstico: `test-rfb-conexao.php`
2. ✅ Execute o teste da API: `test-rfb-api.php`
3. ✅ Teste a consulta integrada na aplicação
4. ✅ Monitore os logs de erro e auditoria

## Suporte

- Documentação RFB: https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx
- Logs de erro: Verifique `error_log` do PHP
- Auditoria: Tabela `tbl_rfb_auditoria` no banco de dados

---

**Status:** ✅ Configuração concluída e pronta para testes
**Data:** 2025-10-30
**Ambiente:** Windows (desenvolvimento)
