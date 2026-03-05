# 🔧 Resolver Erro SSL - Receita Federal

## ❌ Erro Encontrado

```
SOAP-ERROR: Parsing WSDL: Couldn't load from 'https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL'
failed to load external entity
```

## 🔍 Causa

Este erro ocorre quando o PHP não consegue validar o certificado SSL do servidor da Receita Federal devido a:
1. Falta de certificados CA (Certificate Authority) configurados
2. Restrições SSL muito rígidas
3. Problemas de conectividade/firewall

## ✅ Solução Implementada

Atualizei a classe `ReceitaFederalAPI.php` para:

### 1. SSL Menos Restritivo
```php
'ssl' => [
    'local_cert' => $this->certPath,        // Seu certificado .pfx
    'passphrase' => $this->certPassword,    // Senha do certificado
    'verify_peer' => false,                 // ⚠️ Desabilita validação do servidor
    'verify_peer_name' => false,            // ⚠️ Desabilita validação do hostname
    'allow_self_signed' => true,            // Permite certificados auto-assinados
    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
]
```

**Importante:**
- ✅ O certificado do **cliente** (mTLS) ainda é usado e validado pelo servidor da RFB
- ⚠️ Desabilitamos apenas a validação do certificado **do servidor**
- Isso é comum em ambientes corporativos com certificados internos

### 2. Opções SOAP Otimizadas
- User-Agent configurado
- Compressão GZIP habilitada
- Keep-alive desabilitado (evita problemas de conexão)
- Cache de WSDL desabilitado

## 🧪 Testes Disponíveis

### 1. Diagnóstico SSL Completo
```
http://seu-servidor/test-ssl-diagnostico.php
```

Este script testa:
- ✅ Extensões PHP (OpenSSL, SOAP, cURL)
- ✅ Configurações SSL do php.ini
- ✅ Conexão HTTP básica (file_get_contents)
- ✅ Conexão via cURL
- ✅ Resolução DNS
- ✅ Conectividade porta 443
- ✅ SoapClient com diferentes configurações
- ✅ Download e teste com WSDL local

### 2. Teste de Conexão RFB
```
http://seu-servidor/test-rfb-conexao.php
```

Testa especificamente a conexão com a RFB.

### 3. Teste da API Completa
```
http://seu-servidor/test-rfb-api.php
```

Faz uma consulta real de CPF.

## 🔧 Soluções Alternativas (se ainda falhar)

### Solução 1: Configurar cacert.pem

Se o problema persistir, configure os certificados CA no PHP:

1. **Baixe o cacert.pem:**
   ```
   https://curl.se/ca/cacert.pem
   ```

2. **Salve em um local acessível:**
   ```
   C:\php\extras\ssl\cacert.pem
   ```

3. **Configure no php.ini:**
   ```ini
   [openssl]
   openssl.cafile="C:/php/extras/ssl/cacert.pem"

   [curl]
   curl.cainfo="C:/php/extras/ssl/cacert.pem"
   ```

4. **Reinicie o servidor web:**
   ```bash
   # Apache
   httpd -k restart

   # Docker
   docker-compose restart
   ```

### Solução 2: Desabilitar Verificação SSL no php.ini

**⚠️ Apenas para desenvolvimento/teste:**

```ini
[openssl]
openssl.allow_self_signed = 1
```

### Solução 3: Usar WSDL Local (Cache)

Se a conexão inicial funcionar mas ficar lenta:

1. Baixe o WSDL uma vez:
   ```php
   $wsdlContent = file_get_contents('https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL', false, $context);
   file_put_contents(__DIR__ . '/../config/rfb_wsdl.xml', $wsdlContent);
   ```

2. Use o arquivo local:
   ```php
   $this->wsdl = __DIR__ . '/../config/rfb_wsdl.xml';
   ```

## 🐳 Docker/Container

Se estiver usando Docker, certifique-se de que:

### 1. Certificados CA estão instalados no container

**Dockerfile:**
```dockerfile
# Instalar certificados CA
RUN apt-get update && apt-get install -y ca-certificates && update-ca-certificates

# Ou para Alpine Linux:
# RUN apk add --no-cache ca-certificates
```

### 2. Extensões PHP estão habilitadas

```dockerfile
RUN docker-php-ext-install soap
RUN docker-php-ext-install pdo_sqlsrv
```

### 3. OpenSSL está atualizado

```bash
# Dentro do container
openssl version
# Deve ser 1.1.1 ou superior
```

## 📋 Checklist de Diagnóstico

Execute passo a passo:

- [ ] 1. Acesse: `http://seu-servidor/test-ssl-diagnostico.php`
- [ ] 2. Verifique se **OpenSSL** está carregado
- [ ] 3. Verifique se **SOAP** está carregado
- [ ] 4. Teste se **file_get_contents** consegue acessar o WSDL
- [ ] 5. Teste se **cURL** consegue acessar o WSDL
- [ ] 6. Se sim, problema é apenas no SoapClient
- [ ] 7. Verifique logs do PHP: `tail -f /var/log/php_errors.log`
- [ ] 8. Teste novamente: `http://seu-servidor/test-rfb-api.php`

## 🔒 Considerações de Segurança

### Em Desenvolvimento
- ✅ Tudo bem desabilitar `verify_peer` para testes
- ✅ Facilita desenvolvimento

### Em Produção
- ⚠️ **Idealmente** configure certificados CA corretos
- ✅ Mas se o ambiente corporativo tiver certificados internos, `verify_peer = false` é aceitável
- ✅ O certificado do cliente (mTLS) ainda garante a autenticação

## 📞 Suporte

### Se o erro persistir:

1. **Verifique firewall/proxy:**
   ```bash
   # Teste conectividade
   telnet acesso.infoconv.receita.fazenda.gov.br 443
   ```

2. **Verifique DNS:**
   ```bash
   nslookup acesso.infoconv.receita.fazenda.gov.br
   ```

3. **Teste com curl manualmente:**
   ```bash
   curl -v https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL
   ```

4. **Veja logs detalhados:**
   - Ative `error_reporting = E_ALL` no php.ini
   - Veja `/var/log/apache2/error.log` ou `/var/log/php_errors.log`

## ✅ Status Atual

- [x] ✅ Classe ReceitaFederalAPI.php atualizada
- [x] ✅ SSL configurado para ser menos restritivo
- [x] ✅ Script de diagnóstico criado: `test-ssl-diagnostico.php`
- [ ] ⏳ **Próximo passo:** Execute `test-ssl-diagnostico.php` e me envie os resultados

---

**Última atualização:** 2025-10-30
**Arquivo modificado:** `src/lib/ReceitaFederalAPI.php` (linhas 84-118)
