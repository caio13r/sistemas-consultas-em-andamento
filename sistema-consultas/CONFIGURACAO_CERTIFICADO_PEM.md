# 🔐 Configuração Certificado PEM - SERPRO/RFB

## 📋 Conforme Documentação Oficial SERPRO

A documentação do SERPRO é clara: **certificados digitais devem estar em formato .PEM** para PHP.

## 🎯 Solução Rápida

### Opção 1: Converter via Script PHP (Recomendado)

1. **Execute o conversor:**
   ```
   http://seu-servidor/converter-pfx-para-pem.php
   ```

2. **O script irá:**
   - ✅ Ler o arquivo `CFOORGBR.pfx`
   - ✅ Extrair certificado e chave privada
   - ✅ Criar arquivo `CFOORGBR.pem` automaticamente
   - ✅ Validar o certificado gerado

3. **Pronto!** O arquivo `.pem` será criado em `src/config/CFOORGBR.pem`

### Opção 2: Converter via OpenSSL (Linha de Comando)

#### Windows (PowerShell ou CMD):

**Gerar PEM SEM senha (recomendado):**
```bash
cd C:\Users\joao.dias\Documents\sistema-consultas\src\config
openssl pkcs12 -in CFOORGBR.pfx -out CFOORGBR.pem -nodes
```
- Digite a senha quando solicitado: `CFO166`
- Isso cria um arquivo `.pem` que **não precisa de senha** para usar

**Gerar PEM COM senha:**
```bash
openssl pkcs12 -in CFOORGBR.pfx -out CFOORGBR.pem
```
- Digite a senha do .pfx: `CFO166`
- Digite a nova senha do .pem (ou deixe em branco)

#### Linux/Docker:

```bash
cd /var/www/html/src/config
openssl pkcs12 -in CFOORGBR.pfx -out CFOORGBR.pem -nodes
```

## 📂 Estrutura de Arquivos

Depois da conversão:

```
src/config/
├── CFOORGBR.pfx        # Certificado original (manter como backup)
└── CFOORGBR.pem        # ✅ Certificado convertido (usado pela aplicação)
```

## ✅ Configuração Automática

A classe `ReceitaFederalAPI` já está configurada para:

1. **Procurar primeiro o arquivo .pem:**
   ```php
   $pemPath = __DIR__ . '/../config/CFOORGBR.pem';
   $pfxPath = __DIR__ . '/../config/CFOORGBR.pfx';
   $this->certPath = file_exists($pemPath) ? $pemPath : $pfxPath;
   ```

2. **Usar automaticamente o melhor formato disponível**

## 🧪 Testar a Configuração

### 1. Converter o certificado:
```
http://seu-servidor/converter-pfx-para-pem.php
```

### 2. Testar diagnóstico:
```
http://seu-servidor/test-ssl-diagnostico.php
```

### 3. Testar API completa:
```
http://seu-servidor/test-rfb-api.php
```

### 4. Usar na aplicação:
```
http://seu-servidor/consulta-integrada?tipoConsulta=3
```

## 📝 Formato do Arquivo PEM

O arquivo `.pem` contém:

```
-----BEGIN CERTIFICATE-----
[Base64 encoded certificate]
-----END CERTIFICATE-----
-----BEGIN RSA PRIVATE KEY-----
[Base64 encoded private key]
-----END RSA PRIVATE KEY-----
```

Pode também incluir certificados da cadeia (CA intermediários).

## 🔧 Exemplos da Documentação SERPRO

### Exemplo 1: SoapClient (nosso caso)

```php
$options = [
    'local_cert'     => $pemfile,              // ✅ Arquivo .pem
    'passphrase'     => $password,             // Senha (ou vazio se -nodes)
    'stream_context' => stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ])
];

$soapClient = new \SoapClient($soapUrl, $options);
```

### Exemplo 2: cURL + SOAP

```php
curl_setopt($ch, CURLOPT_SSLCERT, $pemfile);    // ✅ Arquivo .pem
curl_setopt($ch, CURLOPT_KEYPASSWD, $password);
```

### Exemplo 3: file_get_contents + REST

```php
'ssl' => [
    'local_cert'        => $pemfile,            // ✅ Arquivo .pem
    'passphrase'        => $password,
    'verify_peer'       => false,
    'verify_peer_name'  => false,
    'allow_self_signed' => true
]
```

## 🔍 Verificar se o PEM está correto

```bash
# Ver informações do certificado
openssl x509 -in CFOORGBR.pem -text -noout

# Ver data de validade
openssl x509 -in CFOORGBR.pem -noout -dates

# Testar se o PEM é válido
openssl x509 -in CFOORGBR.pem -noout
# Se não der erro, está OK!
```

## ⚠️ Diferenças PFX vs PEM

| Aspecto | .PFX (PKCS#12) | .PEM |
|---------|----------------|------|
| **Formato** | Binário | Texto (Base64) |
| **Conteúdo** | Certificado + Chave + Cadeia | Certificado + Chave (separados) |
| **Leitura** | Difícil de inspecionar | Fácil de ler/editar |
| **Windows** | ✅ Padrão | ⚠️ Precisa converter |
| **Linux/PHP** | ⚠️ Precisa converter | ✅ Padrão |
| **SERPRO/RFB** | ❌ Não suportado | ✅ **Obrigatório** |

## 🔒 Segurança

### Arquivo PEM sem senha (-nodes):
- ✅ **Vantagem:** Não precisa senha no código
- ⚠️ **Desvantagem:** Qualquer um com acesso ao arquivo pode usá-lo
- 💡 **Solução:** Proteger com permissões do filesystem

### Arquivo PEM com senha:
- ✅ **Vantagem:** Proteção adicional
- ⚠️ **Desvantagem:** Precisa armazenar senha no .env
- 💡 **Recomendação:** Use senha apenas se tiver camada extra de proteção

### Permissões Recomendadas:

```bash
# Linux/Docker
chmod 600 src/config/CFOORGBR.pem
chown www-data:www-data src/config/CFOORGBR.pem

# Windows
# Configurar via Properties > Security > Advanced
# Permitir leitura apenas para o usuário do Apache/IIS
```

## 📞 Troubleshooting

### Erro: "Unable to load certificate"
```
✗ Solução: Arquivo .pem está corrompido ou mal formatado
✓ Gere novamente com: openssl pkcs12 -in CFOORGBR.pfx -out CFOORGBR.pem -nodes
```

### Erro: "Error reading certificate"
```
✗ Solução: Senha incorreta ou arquivo não é um certificado válido
✓ Teste: openssl x509 -in CFOORGBR.pem -noout
```

### Erro: "Could not read private key"
```
✗ Solução: Chave privada não foi incluída no .pem
✓ Use -nodes ou -nocerts para incluir a chave
```

### Certificado funciona no Postman mas não no PHP:
```
✗ Causa: Postman usa .pfx, PHP precisa de .pem
✓ Solução: Converta com o script converter-pfx-para-pem.php
```

## 📚 Referências

- **Documentação SERPRO:** InfoConv - Protótipos PHP
- **OpenSSL:** https://www.openssl.org/docs/
- **PHP SoapClient:** https://www.php.net/manual/pt_BR/class.soapclient.php

## ✅ Checklist Final

- [ ] 1. Converter .pfx para .pem (via script ou openssl)
- [ ] 2. Verificar se `src/config/CFOORGBR.pem` existe
- [ ] 3. Testar com `test-ssl-diagnostico.php`
- [ ] 4. Testar API com `test-rfb-api.php`
- [ ] 5. Usar na aplicação: consulta-integrada

## 🎯 Resumo

1. ✅ **Formato correto:** .PEM (conforme SERPRO)
2. ✅ **Conversão:** Use `converter-pfx-para-pem.php`
3. ✅ **Localização:** `src/config/CFOORGBR.pem`
4. ✅ **Senha:** Configurada no `.env` ou use `-nodes` para não precisar
5. ✅ **Pronto:** Classe já detecta automaticamente

---

**Status:** ✅ Configurado conforme documentação SERPRO
**Próximo passo:** Execute o conversor e teste!
