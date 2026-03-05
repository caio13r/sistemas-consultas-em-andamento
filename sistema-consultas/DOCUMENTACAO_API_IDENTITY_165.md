# 📚 Documentação da API de Identidade - 192.168.161.165:8082

Documentação dos endpoints da API de Identidade que roda no servidor `192.168.161.165:8082`.

---

## 🔐 Autenticação

Todos os endpoints requerem autenticação via **token** passado como parâmetro na query string.

### Formato do Token
- O token deve ser passado como parâmetro `token` na URL
- Exemplo: `?token=seu_token_aqui`

### Configuração do Token
O token deve estar configurado no arquivo `.env` do sistema:
```env
API_TOKEN=seu_token_aqui
API_IDENTITY_URL=http://192.168.161.165:8082
```

---

## 📍 Endpoints Disponíveis

### 1. Consulta por Nome
Consulta identidades pelo nome completo ou parcial.

**Endpoint:** `GET /api/consulta/identidade/nome`

**Parâmetros:**
- `token` (obrigatório) - Token de autenticação
- `name` (obrigatório) - Nome completo ou parcial para busca
- `page_number` (opcional) - Número da página (padrão: 1)
- `page_amount` (opcional) - Quantidade de resultados por página (padrão: 100, máximo: 1000)

**Exemplo de Requisição:**
```bash
curl "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN&name=HUMBERTO%20FRANCO%20BUENO&page_number=1&page_amount=100"
```

**Exemplo de Resposta (Sucesso - 200):**
```json
{
  "list": [
    {
      "id": "12345",
      "nome": ["HUMBERTO FRANCO BUENO"],
      "cpf": "12345678901",
      "ar": "987654321",
      "cro": "12345",
      "categoria": "Dentista",
      "inscricao": "12345",
      "qr_code": "https://...",
      "foto": "base64_encoded_image..."
    }
  ],
  "total": 1,
  "page": 1,
  "per_page": 100
}
```

**Códigos de Resposta:**
- `200` - Sucesso
- `401` - Token inválido ou ausente
- `400` - Parâmetros inválidos
- `500` - Erro interno do servidor

---

### 2. Consulta por CPF
Consulta identidade pelo CPF.

**Endpoint:** `GET /api/consulta/identidade/cpf`

**Parâmetros:**
- `token` (obrigatório) - Token de autenticação
- `cpf` (obrigatório) - CPF para busca (apenas números)

**Exemplo de Requisição:**
```bash
curl "http://192.168.161.165:8082/api/consulta/identidade/cpf?token=SEU_TOKEN&cpf=12345678901"
```

**Exemplo de Resposta (Sucesso - 200):**
```json
{
  "identidade": {
    "id": "12345",
    "nome": ["HUMBERTO FRANCO BUENO"],
    "cpf": "12345678901",
    "ar": "987654321",
    "cro": "12345",
    "categoria": "Dentista",
    "inscricao": "12345",
    "qr_code": "https://...",
    "foto": "base64_encoded_image..."
  }
}
```

**Códigos de Resposta:**
- `200` - Sucesso
- `401` - Token inválido ou ausente
- `404` - CPF não encontrado
- `400` - CPF inválido
- `500` - Erro interno do servidor

---

### 3. Consulta por AR (Autenticação de Recebimento)
Consulta identidade pelo número do AR.

**Endpoint:** `GET /api/consulta/identidade/ar`

**Parâmetros:**
- `token` (obrigatório) - Token de autenticação
- `ar` (obrigatório) - Número do AR para busca

**Exemplo de Requisição:**
```bash
curl "http://192.168.161.165:8082/api/consulta/identidade/ar?token=SEU_TOKEN&ar=987654321"
```

**Exemplo de Resposta (Sucesso - 200):**
```json
{
  "identidade": {
    "id": "12345",
    "nome": ["HUMBERTO FRANCO BUENO"],
    "cpf": "12345678901",
    "ar": "987654321",
    "cro": "12345",
    "categoria": "Dentista",
    "inscricao": "12345",
    "qr_code": "https://...",
    "foto": "base64_encoded_image..."
  }
}
```

**Códigos de Resposta:**
- `200` - Sucesso
- `401` - Token inválido ou ausente
- `404` - AR não encontrado
- `400` - AR inválido
- `500` - Erro interno do servidor

---

## ❌ Tratamento de Erros

### Erro 401 - Unauthorized
**Causa:** Token inválido, expirado ou ausente.

**Soluções:**
1. Verificar se o token está configurado no arquivo `.env`
2. Verificar se o token está correto
3. Verificar se o token está autorizado no servidor da API
4. Gerar um novo token se necessário

**Exemplo de Resposta de Erro:**
```json
{
  "erro": "Token inválido ou ausente",
  "codigo": 401
}
```

### Erro 400 - Bad Request
**Causa:** Parâmetros inválidos ou ausentes.

**Soluções:**
1. Verificar se todos os parâmetros obrigatórios foram enviados
2. Verificar o formato dos parâmetros (ex: CPF apenas números)
3. Verificar se os valores estão corretos

**Exemplo de Resposta de Erro:**
```json
{
  "erro": "Parâmetro 'name' é obrigatório",
  "codigo": 400
}
```

### Erro 404 - Not Found
**Causa:** Endpoint não encontrado ou recurso não existe.

**Soluções:**
1. Verificar se a URL do endpoint está correta
2. Verificar se o servidor está rodando
3. Verificar se o recurso (CPF, AR, etc.) existe no banco de dados

### Erro 500 - Internal Server Error
**Causa:** Erro interno no servidor da API.

**Soluções:**
1. Verificar logs do servidor 192.168.161.165:8082
2. Verificar se o banco de dados está acessível
3. Verificar recursos do servidor (memória, CPU, disco)
4. Contatar o administrador do servidor

---

## 🔧 Configuração no Servidor 165

### Verificar Token Configurado
No servidor 192.168.161.165, você pode precisar:

1. **Verificar tokens autorizados** (dependendo da implementação da API):
   ```bash
   # Se houver arquivo de configuração
   cat /caminho/da/api/config/tokens.json
   # OU
   cat /caminho/da/api/.env
   ```

2. **Verificar logs da API** para ver qual token está sendo rejeitado:
   ```bash
   # Logs do Docker (se usar container)
   docker logs nome-do-container-api | grep -i token
   
   # Logs do sistema (se usar serviço)
   sudo journalctl -u api-identity | grep -i token
   
   # Logs de aplicação
   tail -f /var/log/api-identity/error.log | grep -i 401
   ```

3. **Adicionar/Atualizar token autorizado** (se necessário):
   - Se a API usa arquivo de configuração, adicione o token lá
   - Se a API usa banco de dados, insira o token na tabela de tokens
   - Se a API usa variável de ambiente, configure no `.env` do servidor

---

## 🧪 Testes de Validação

### Teste 1: Verificar se o token está sendo enviado
```bash
# Teste com verbose para ver headers
curl -v "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN&name=teste" 2>&1 | grep -i token
```

### Teste 2: Testar com token inválido (deve retornar 401)
```bash
curl "http://192.168.161.165:8082/api/consulta/identidade/nome?token=token_invalido&name=teste"
# Deve retornar: {"erro": "Token inválido", "codigo": 401}
```

### Teste 3: Testar sem token (deve retornar 401)
```bash
curl "http://192.168.161.165:8082/api/consulta/identidade/nome?name=teste"
# Deve retornar: {"erro": "Token ausente", "codigo": 401}
```

### Teste 4: Testar com token válido
```bash
curl "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN_VALIDO&name=HUMBERTO%20FRANCO%20BUENO"
# Deve retornar dados JSON com status 200
```

---

## 📝 Notas Importantes

1. **URL Encoding**: Sempre use `urlencode()` ao passar parâmetros na URL, especialmente para nomes com espaços
2. **Timeout**: A API pode ter timeout configurado. Requisições muito grandes podem falhar
3. **Rate Limiting**: A API pode ter limite de requisições por minuto/hora
4. **HTTPS vs HTTP**: Verifique se a API usa HTTP ou HTTPS. O código atual usa HTTP
5. **CORS**: Se acessar via JavaScript do navegador, pode haver problemas de CORS. Use o proxy PHP quando necessário

---

## 🔗 Referências

- Arquivo de consulta: `src/services/consulta-identidade/consultaIdentidade-1.php`
- Proxy da API: `src/services/consulta-identidade/api-cpf-aprovados-proxy.php`
- Documento de testes: `TESTE_API_IDENTITY_165.md`
- Instruções do servidor: `INSTRUCOES_SERVIDOR_165.md`

---

## 🆘 Suporte

Se o erro 401 persistir:

1. ✅ Verificar token no arquivo `.env`
2. ✅ Verificar se o token está autorizado no servidor 165
3. ✅ Verificar logs do servidor 165
4. ✅ Testar token diretamente com curl
5. ✅ Contatar administrador da API no servidor 165
