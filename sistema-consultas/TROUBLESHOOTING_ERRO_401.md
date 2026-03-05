# 🔧 Troubleshooting - Erro 401 (Unauthorized)

Guia completo para resolver o erro **HTTP 401 - Unauthorized** na consulta de identidade policarbonato.

---

## 🎯 Problema

Ao consultar identidade policarbonato, você recebe:
```
Erro HTTP 401:
O servidor retornou um erro. Verifique os parâmetros da consulta.
Nenhum resultado encontrado.
```

---

## 🔍 Diagnóstico Rápido

### Passo 1: Verificar se o token está configurado

No servidor do sistema de consultas, verifique o arquivo `.env`:

```bash
# No servidor do sistema de consultas
cat .env | grep API_TOKEN
```

**Resultado esperado:**
```
API_TOKEN=seu_token_aqui
```

**Se não encontrar ou estiver vazio:**
- Adicione a linha `API_TOKEN=seu_token_valido` no arquivo `.env`
- Reinicie o serviço/container se necessário

---

### Passo 2: Testar o token diretamente

Teste se o token funciona fazendo uma requisição direta:

```bash
# Substitua SEU_TOKEN pelo token do .env
curl "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN&name=teste"
```

**Resultados possíveis:**

✅ **200 OK** - Token está funcionando, problema pode ser no código
```json
{"list": [], "total": 0}
```

❌ **401 Unauthorized** - Token inválido ou não autorizado
```json
{"erro": "Token inválido", "codigo": 401}
```

❌ **Connection refused** - Servidor não está rodando
```
curl: (7) Failed to connect to 192.168.161.165 port 8082
```

---

## 🛠️ Soluções

### Solução 1: Token não configurado no .env

**Problema:** O arquivo `.env` não tem o token ou está vazio.

**Solução:**
1. Edite o arquivo `.env` na raiz do projeto:
   ```bash
   nano .env
   # OU
   vi .env
   ```

2. Adicione ou atualize a linha:
   ```env
   API_TOKEN=seu_token_valido_aqui
   API_IDENTITY_URL=http://192.168.161.165:8082
   ```

3. Salve o arquivo

4. Se estiver usando Docker, reinicie o container:
   ```bash
   docker-compose restart app
   # OU
   docker restart sistema-consultas
   ```

---

### Solução 2: Token inválido ou expirado

**Problema:** O token no `.env` está incorreto ou expirou.

**Solução:**
1. **Obter novo token:**
   - Contate o administrador da API no servidor 192.168.161.165
   - Ou verifique a documentação da API para gerar novo token

2. **Atualizar o .env:**
   ```env
   API_TOKEN=novo_token_aqui
   ```

3. **Testar o novo token:**
   ```bash
   curl "http://192.168.161.165:8082/api/consulta/identidade/nome?token=NOVO_TOKEN&name=teste"
   ```

---

### Solução 3: Token não autorizado no servidor 165

**Problema:** O token está correto, mas não está autorizado no servidor da API.

**O que fazer no servidor 192.168.161.165:**

#### 3.1 Verificar tokens autorizados

```bash
# Conectar no servidor 165
ssh usuario@192.168.161.165

# Procurar arquivo de configuração de tokens
find /opt /home /var/www -name "*token*" -type f 2>/dev/null | head -10

# Verificar logs da API para ver qual token foi rejeitado
docker logs nome-container-api | grep -i "401\|unauthorized\|token" | tail -20
# OU
sudo journalctl -u api-identity | grep -i "401\|unauthorized\|token" | tail -20
```

#### 3.2 Adicionar token autorizado

Dependendo de como a API está configurada:

**Se usar arquivo de configuração:**
```bash
# Editar arquivo de tokens
nano /caminho/da/api/config/tokens.json
# OU
nano /caminho/da/api/.env

# Adicionar o token na lista de tokens autorizados
```

**Se usar banco de dados:**
```sql
-- Exemplo (ajuste conforme sua estrutura)
INSERT INTO api_tokens (token, ativo, criado_em) 
VALUES ('seu_token_aqui', 1, NOW());
```

**Se usar variável de ambiente:**
```bash
# Editar .env do servidor da API
nano /caminho/da/api/.env

# Adicionar:
API_ALLOWED_TOKENS=token1,token2,seu_token_aqui
```

#### 3.3 Reiniciar a API

Após adicionar o token, reinicie a API:

```bash
# Se usar Docker
docker restart nome-container-api
# OU
docker-compose restart

# Se usar systemd
sudo systemctl restart api-identity
# OU
sudo systemctl restart nome-do-servico
```

---

### Solução 4: Verificar formato do token

**Problema:** O token pode ter espaços ou caracteres especiais que estão sendo mal interpretados.

**Solução:**
1. Verifique se o token no `.env` não tem aspas desnecessárias:
   ```env
   # ❌ ERRADO
   API_TOKEN="token_com_aspas"
   API_TOKEN='token_com_aspas_simples'
   
   # ✅ CORRETO
   API_TOKEN=token_sem_aspas
   ```

2. Verifique se não há espaços no início ou fim:
   ```env
   # ❌ ERRADO
   API_TOKEN= token_com_espaco
   
   # ✅ CORRETO
   API_TOKEN=token_sem_espacos
   ```

---

### Solução 5: Verificar se a API está rodando

**Problema:** A API pode estar retornando 401 porque não está processando corretamente.

**Solução:**
1. Verificar se a API está rodando:
   ```bash
   # No servidor 165
   curl http://192.168.161.165:8082
   # Deve retornar algo (mesmo que erro)
   ```

2. Verificar logs da API:
   ```bash
   # Ver logs em tempo real
   docker logs -f nome-container-api
   # OU
   sudo journalctl -u api-identity -f
   ```

3. Verificar se há erros de autenticação nos logs:
   ```bash
   docker logs nome-container-api | grep -i "401\|token\|auth" | tail -20
   ```

---

## 🧪 Testes de Validação

### Teste Completo

Execute este script para diagnosticar o problema:

```bash
#!/bin/bash

echo "=== Teste de Diagnóstico - Erro 401 ==="
echo ""

# 1. Verificar token no .env
echo "1. Verificando token no .env..."
if [ -f .env ]; then
    TOKEN=$(grep "^API_TOKEN=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')
    if [ -z "$TOKEN" ]; then
        echo "❌ Token não encontrado no .env"
    else
        echo "✅ Token encontrado: ${TOKEN:0:10}..."
        
        # 2. Testar token
        echo ""
        echo "2. Testando token na API..."
        RESPONSE=$(curl -s -w "\n%{http_code}" "http://192.168.161.165:8082/api/consulta/identidade/nome?token=$TOKEN&name=teste")
        HTTP_CODE=$(echo "$RESPONSE" | tail -1)
        BODY=$(echo "$RESPONSE" | head -n -1)
        
        if [ "$HTTP_CODE" = "200" ]; then
            echo "✅ Token válido! API respondeu com HTTP 200"
        elif [ "$HTTP_CODE" = "401" ]; then
            echo "❌ Token inválido! API retornou HTTP 401"
            echo "Resposta: $BODY"
        else
            echo "⚠️  Resposta inesperada: HTTP $HTTP_CODE"
            echo "Resposta: $BODY"
        fi
    fi
else
    echo "❌ Arquivo .env não encontrado"
fi

echo ""
echo "=== Fim do diagnóstico ==="
```

---

## 📋 Checklist de Verificação

Marque conforme verificar:

- [ ] Token está configurado no arquivo `.env`
- [ ] Token não tem aspas desnecessárias
- [ ] Token não tem espaços no início/fim
- [ ] Token funciona quando testado com curl diretamente
- [ ] Token está autorizado no servidor 192.168.161.165
- [ ] API está rodando no servidor 165
- [ ] Logs da API não mostram erros de autenticação
- [ ] Arquivo `.env` foi recarregado (reiniciado container/serviço)

---

## 🔗 Referências

- Documentação da API: `DOCUMENTACAO_API_IDENTITY_165.md`
- Instruções do servidor: `INSTRUCOES_SERVIDOR_165.md`
- Testes da API: `TESTE_API_IDENTITY_165.md`
- Código corrigido: `src/services/consulta-identidade/consultaIdentidade-1.php`

---

## 💡 Dica Final

Se nada funcionar, verifique os logs do servidor 165 em tempo real enquanto faz uma requisição:

```bash
# Terminal 1: Ver logs
docker logs -f nome-container-api

# Terminal 2: Fazer requisição
curl "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN&name=teste"
```

Isso mostrará exatamente o que a API está recebendo e por que está rejeitando.
